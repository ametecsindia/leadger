<?php

namespace Tests\Feature;

use App\Http\Middleware\TelecallerMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Options -> Delete on Sources and Products & Services: permission, in-use records kept, others deleted. */
class SourceProductDeleteTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TelecallerMiddleware::class);
        URL::forceRootUrl('http://127.0.0.1');   // "localhost" is a tenants-table company on its own connection
        $this->admin = User::whereHas('permissionsGroup', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
    }

    private function source(): int
    {
        return DB::table('sources')->insertGetId(['name' => uniqid('Del Source '), 'created_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function product(): int
    {
        return DB::table('product_services')->insertGetId(['name' => uniqid('Del Product '), 'created_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_an_unused_source_is_deleted()
    {
        $id = $this->source();
        $this->actingAs($this->admin)->deleteJson(route('sourcesDestroy', $id))->assertOk()->assertJson(['success' => true]);
        $this->assertFalse(DB::table('sources')->where('id', $id)->exists());
    }

    public function test_a_source_used_by_a_campaign_is_kept()
    {
        $id = $this->source();
        $other = $this->source();
        DB::table('campaigns')->insert(['name' => uniqid('Del Campaign '), 'source_id' => $id, 'segment_id' => 0, 'team_id' => '1', 'status' => '1',
            'created_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->admin)->deleteJson(route('sourcesDestroy', $id))->assertStatus(422)
            ->assertJson(['success' => false])->assertJsonFragment(['message' => 'This source cannot be deleted: it is used by 1 campaign. Change their source first.']);
        $this->assertTrue(DB::table('sources')->where('id', $id)->exists());
        $this->assertTrue(DB::table('sources')->where('id', $other)->exists(), 'unrelated records untouched');
    }

    public function test_an_unused_product_is_deleted_and_one_on_a_lead_is_kept()
    {
        $free = $this->product();
        $used = $this->product();
        DB::table('leads')->insert(['name' => 'Del Lead', 'phonenumber' => '9000000099', 'product_service' => "3, {$used},7", 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->admin)->deleteJson(route('productServicesDestroy', $free))->assertOk()->assertJson(['success' => true]);
        $this->assertFalse(DB::table('product_services')->where('id', $free)->exists());

        $this->deleteJson(route('productServicesDestroy', $used))->assertStatus(422)->assertJsonFragment(['message' => 'This product cannot be deleted: it is selected on 1 lead. Remove it from those leads first.']);
        $this->assertTrue(DB::table('product_services')->where('id', $used)->exists());
    }

    public function test_users_without_the_delete_permission_cannot_delete()
    {
        $source = $this->source();
        $product = $this->product();
        $role = DB::table('permissions')->where('name', 'Telecaller')->first();
        DB::table('permissions')->where('id', $role->id)->update(['delete_status' => 0]);
        $tele = User::where('permissions_id', $role->id)->firstOrFail();

        $this->actingAs($tele)->deleteJson(route('sourcesDestroy', $source))->assertForbidden();
        $this->deleteJson(route('productServicesDestroy', $product))->assertForbidden();
        $this->assertTrue(DB::table('sources')->where('id', $source)->exists());
        $this->assertTrue(DB::table('product_services')->where('id', $product)->exists());
    }

    public function test_a_missing_record_answers_not_found()
    {
        $this->actingAs($this->admin)->deleteJson(route('sourcesDestroy', 999999999))->assertNotFound();
        $this->deleteJson(route('productServicesDestroy', 999999999))->assertNotFound();
    }

    public function test_the_list_pages_render_the_menu_with_edit_and_delete()
    {
        $this->source();
        $this->actingAs($this->admin)->get(route('sources'))->assertOk()
            ->assertSee('lgRowMenu.toggle(event, this)', false)->assertSee('lgDeleteSource(this)', false)->assertSee('id="lgRowDeleteModal"', false);
        $this->get(route('productServices'))->assertOk()
            ->assertSee('lgDeleteProduct(item)', false)->assertSee('var LG_CAN_DELETE = true', false);
        $this->get(route('segments'))->assertOk()->assertSee('id="lgRowDeleteModal"', false);
    }
}
