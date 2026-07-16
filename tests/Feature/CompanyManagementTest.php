<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'user', 'guard_name' => 'web']);
    }

    public function test_superadmin_can_view_company_index(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $response = $this->actingAs($superadmin)->get(route('companies.index'));

        $response->assertOk();
    }

    public function test_admin_can_view_their_company_dashboard(): void
    {
        $company = Company::create([
            'name' => 'Studio One',
            'slug' => 'studio-one',
            'status' => 'approved',
        ]);

        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('dashboard.admin'));

        $response->assertOk();
    }

    public function test_admin_cannot_access_other_company_resources(): void
    {
        $companyA = Company::create([
            'name' => 'Studio A',
            'slug' => 'studio-a',
            'status' => 'approved',
        ]);
        $companyB = Company::create([
            'name' => 'Studio B',
            'slug' => 'studio-b',
            'status' => 'approved',
        ]);

        $admin = User::factory()->create(['company_id' => $companyA->id]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('companies.index'));

        $response->assertForbidden();
    }

    public function test_admin_cannot_view_or_update_another_company(): void
    {
        $companyA = Company::create([
            'name' => 'Studio A',
            'slug' => 'studio-a',
            'status' => 'approved',
        ]);
        $companyB = Company::create([
            'name' => 'Studio B',
            'slug' => 'studio-b',
            'status' => 'approved',
        ]);

        $admin = User::factory()->create(['company_id' => $companyA->id]);
        $admin->assignRole('admin');

        $this->assertFalse($admin->can('view', $companyB));
        $this->assertFalse($admin->can('update', $companyB));
    }
}
