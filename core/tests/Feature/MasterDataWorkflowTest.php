<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Admin create / duplicate / update workflows for master data, through the real routes.
 * Proves data is actually saved (or refused) in the database. Rolled back afterwards.
 */
class MasterDataWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->admin = User::whereIn('permissions_id', DB::table('permissions')->where('name', 'Admin')->pluck('id'))->firstOrFail();
    }

    public static function modules(): array
    {
        return [
            'sources'        => ['sources', 'admin/sources/store', 'admin/sources/{id}/update', []],
            'segments'       => ['segments', 'admin/segments/store', 'admin/segments/{id}/update', []],
            'statuses'       => ['statuses', 'admin/statuses/store', 'admin/statuses/{id}/update', []],
            'substatuses'    => ['substatuses', 'admin/substatuses/store', 'admin/substatuses/{id}/update', ['status_id' => 'FIRST_STATUS']],
            'products'       => ['product_services', 'admin/productServices/store', 'admin/productServices/{id}/update', []],
            'client types'   => ['client_types', 'admin/clientTypes/store', 'admin/clientTypes/{id}/update', []],
            'work locations' => ['work_locations', 'admin/workLocations/store', 'admin/workLocations/{id}/update', ['lat' => '17.385', 'long' => '78.486', 'radius' => '150']],
            'shifts'         => ['shifts', 'admin/shifts/store', 'admin/shifts/{id}/update', ['start_time' => '09:00', 'end_time' => '18:00']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('modules')]
    public function test_admin_create_duplicate_update($table, $storeUri, $updateUri, $extra)
    {
        if (($extra['status_id'] ?? null) === 'FIRST_STATUS') {
            $extra['status_id'] = DB::table('statuses')->value('id');
        }
        $name = 'WF Test ' . uniqid();

        // create -> saved with the submitted name
        $this->actingAs($this->admin)->post('/' . $storeUri, ['name' => $name] + $extra);
        $row = DB::table($table)->where('name', $name)->first();
        $this->assertNotNull($row, "Admin create did not save a row in {$table}");

        // duplicate name -> refused
        $count = DB::table($table)->where('name', $name)->count();
        $this->actingAs($this->admin)->post('/' . $storeUri, ['name' => $name] + $extra);
        $this->assertSame($count, DB::table($table)->where('name', $name)->count(), "Duplicate name was accepted in {$table}");

        // update -> new name saved on the same record, nothing else renamed
        $newName = $name . ' Edited';
        $this->actingAs($this->admin)->post('/' . str_replace('{id}', $row->id, $updateUri), ['name' => $newName] + $extra);
        $this->assertSame($newName, DB::table($table)->where('id', $row->id)->value('name'), "Admin update did not save on {$table} #{$row->id}");
        $this->assertSame(1, DB::table($table)->where('name', $newName)->count());
    }

    /** Regression: client type uniqueness was checked against the products table. */
    public function test_client_type_may_share_a_product_name()
    {
        $shared = 'WF Shared ' . uniqid();
        $tpl = (array)DB::table('product_services')->first();
        unset($tpl['id']);
        $tpl['name'] = $shared;
        DB::table('product_services')->insert($tpl);

        $this->actingAs($this->admin)->post('/admin/clientTypes/store', ['name' => $shared]);
        $this->assertTrue(DB::table('client_types')->where('name', $shared)->exists(), 'Client type with a product\'s name was rejected');
    }
}
