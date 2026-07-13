<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $freePlan = Plan::where('slug', 'free')->first();
        $proPlan  = Plan::where('slug', 'pro')->first();

        // Admin user
        User::updateOrCreate(
            ['email' => 'admin@amazonlistingbuilder.com'],
            [
                'name'              => 'Admin User',
                'password'          => Hash::make('Admin@1234'),
                'role'              => 'admin',
                'status'            => 'active',
                'plan_id'           => $proPlan?->id,
                'email_verified_at' => now(),
            ]
        );

        // Demo user
        User::updateOrCreate(
            ['email' => 'demo@amazonlistingbuilder.com'],
            [
                'name'                  => 'Demo Seller',
                'password'              => Hash::make('Demo@1234'),
                'company_name'          => 'Demo Store',
                'role'                  => 'user',
                'status'                => 'active',
                'plan_id'               => $freePlan?->id,
                'email_verified_at'     => now(),
                'default_brand'         => 'PrimeCraft',
                'default_manufacturer'  => 'PrimeCraft Industries',
            ]
        );

        $this->command?->info('✓ Users seeded');
        $this->command?->info('  Admin: admin@amazonlistingbuilder.com / Admin@1234');
        $this->command?->info('  Demo:  demo@amazonlistingbuilder.com / Demo@1234');
    }
}
