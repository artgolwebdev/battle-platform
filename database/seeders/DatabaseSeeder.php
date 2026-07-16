<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);

         $superadmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
        ]);
        $superadmin->assignRole('superadmin');

        $company = Company::create([
            'name' => 'Daniella Demo Co',
            'slug' => 'daniella-demo-co',
            'status' => 'approved',
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
        ]);
        $admin->assignRole('admin');
        $company->update(['owner_admin_id' => $admin->id]);

        $user = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('user');


         $this->call([
            DemoSeeder::class,
        ]);

    }
}
