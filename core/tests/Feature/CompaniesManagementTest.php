<?php

namespace Tests\Feature;

use App\Http\Middleware\TelecallerMiddleware;
use App\Models\TenantSetup;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Configuration > Companies: Super Admin only; a company is saved only when its own database answers. */
class CompaniesManagementTest extends TestCase
{
    use DatabaseTransactions;

    private const COMPANY_DB = 'lt_leadger_tenant_test';

    private User $admin;
    private array $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TelecallerMiddleware::class);
        \Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1');
        $c = config('database.connections.mysql');
        (new \PDO("mysql:host={$c['host']};port={$c['port']}", $c['username'], $c['password']))
            ->exec('CREATE DATABASE IF NOT EXISTS `' . self::COMPANY_DB . '`');   // own connection: no implicit commit here
        $this->db = ['db_host' => $c['host'], 'db_name' => self::COMPANY_DB, 'db_user' => $c['username'], 'db_password' => (string)$c['password']];

        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        config(['tenancy.super_admin_emails' => [strtolower($this->admin->email)], 'tenancy.encrypt_passwords' => false, 'tenancy.manage_only' => false]);
        Cache::store('tenancy')->flush();
    }

    private function form(array $over = []): array
    {
        return array_merge(['subdomain' => 'acme-test.leadger.in'], $this->db, $over);
    }

    public function test_only_a_listed_super_admin_can_open_companies()
    {
        $other = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->where('id', '!=', $this->admin->id)->first()
            ?? User::where('id', '!=', $this->admin->id)->firstOrFail();
        $this->actingAs($other)->get(route('companies'))->assertRedirect(route('NoPermission'));
        $this->actingAs($other)->getJson(route('companiesCheck', 1))->assertForbidden();

        $this->actingAs($this->admin)->get(route('companies'))->assertOk()->assertSee('Add company');
        $this->actingAs($this->admin)->get(route('callingSettings'))->assertSee(route('companies'), false);   // menu entry
        $this->actingAs($other)->get(route('callingSettings'))->assertDontSee(route('companies'), false);
    }

    public function test_a_company_is_added_only_when_its_database_answers_and_the_change_is_audited()
    {
        $this->actingAs($this->admin)->post(route('companiesStore'), $this->form(['db_password' => 'wrong-password']))
            ->assertSessionHasErrors('db_name');
        $this->assertSame(0, TenantSetup::where('subdomain', 'acme-test.leadger.in')->count());

        $this->actingAs($this->admin)->post(route('companiesStore'), $this->form())->assertRedirect(route('companies'));
        $t = TenantSetup::where('subdomain', 'acme-test.leadger.in')->firstOrFail();
        $this->assertSame([self::COMPANY_DB, 'acme-test'], [$t->db_name, $t->shortName()]);
        $this->assertSame($this->db['db_password'], DB::table('tenants')->where('id', $t->id)->value('db_password'),
            'stored as plain text while another installation reads the list');
        $audit = (string)@file_get_contents(storage_path('logs/audit-' . now()->format('Y-m-d') . '.log'));
        $this->assertStringContainsString('[Companies] Company created', $audit);
        $this->assertStringContainsString('acme-test.leadger.in', $audit);
        $this->assertStringNotContainsString('"db_password":', $audit, 'field names only, never secret values');
    }

    public function test_editing_keeps_the_saved_password_and_validates_the_address()
    {
        $t = TenantSetup::create($this->form());
        $this->actingAs($this->admin)->put(route('companiesUpdate', $t->id), $this->form(['subdomain' => 'acme-renamed.leadger.in', 'db_password' => '']))
            ->assertRedirect(route('companies'));
        $this->assertSame('acme-renamed.leadger.in', $t->fresh()->subdomain);
        $this->assertSame($this->db['db_password'], $t->fresh()->db_password);

        $this->actingAs($this->admin)->put(route('companiesUpdate', $t->id), $this->form(['subdomain' => 'https://bad address/']))
            ->assertSessionHasErrors('subdomain');
        TenantSetup::create($this->form(['subdomain' => 'taken.leadger.in']));
        $this->actingAs($this->admin)->put(route('companiesUpdate', $t->id), $this->form(['subdomain' => 'taken.leadger.in']))
            ->assertSessionHasErrors('subdomain');
    }

    public function test_the_companies_list_has_exactly_the_eight_columns()
    {
        $this->assertSame(['created_at', 'db_host', 'db_name', 'db_password', 'db_user', 'id', 'subdomain', 'updated_at'],
            collect(\Illuminate\Support\Facades\Schema::connection(Tenancy::registry())->getColumnListing('tenants'))->sort()->values()->all());
        $this->actingAs($this->admin)->get(route('companiesCreate'))->assertOk()
            ->assertDontSee('name="status"', false)->assertDontSee('name="name"', false)->assertSee('name="subdomain"', false);
    }

    public function test_live_check_and_connection_test_read_the_company_database()
    {
        $t = TenantSetup::create($this->form());
        $this->actingAs($this->admin)->getJson(route('companiesCheck', $t->id))->assertOk()
            ->assertJsonPath('ok', true)->assertJsonStructure(['users', 'leads', 'calls', 'last_call', 'pending_migrations']);

        $this->actingAs($this->admin)->postJson(route('companiesTestConnection'), ['id' => $t->id] + $this->db + ['db_password' => ''])
            ->assertOk()->assertJsonPath('ok', true);
        $bad = $this->actingAs($this->admin)->postJson(route('companiesTestConnection'), ['db_password' => 'nope'] + $this->db)
            ->assertOk()->assertJsonPath('ok', false)->json('error');
        $this->assertStringNotContainsString('nope', (string)$bad, 'the password is never echoed');
    }

    public function test_manage_only_never_serves_or_processes_the_listed_companies()
    {
        TenantSetup::create($this->form());
        config(['tenancy.manage_only' => true]);

        $this->assertNull(Tenancy::findTenant('acme-test.leadger.in'), 'the host is served by the other installation');
        $this->assertSame([Tenancy::central()], Tenancy::companies(), 'no scheduled tasks for listed companies');
        $this->artisan('tenants:migrate')->assertExitCode(1);
        $this->artisan('tenants:encrypt-passwords', ['--force' => true])->assertExitCode(1);
        $this->actingAs($this->admin)->get(route('companies'))->assertOk()->assertSee('served by another LeadGER installation');
    }

    public function test_the_companies_list_can_live_in_another_database()
    {
        config(['tenancy.database' => null]);
        $this->assertSame('mysql', Tenancy::registry());
        config(['tenancy.database' => env('DB_DATABASE')]);
        $this->assertSame('mysql', Tenancy::registry());
        config(['tenancy.database' => 'tenants_leadger']);
        $this->assertSame('tenancy', Tenancy::registry());
        $this->assertSame('tenancy', (new TenantSetup())->getConnectionName());
        config(['tenancy.database' => null]);
    }
}
