<?php

namespace Tests;

use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests verwachten een super_admin (User::first()). Er wordt bewust
        // geen standaardaccount meer via de seeder aangemaakt.
        if (! Schema::hasTable('users') || User::query()->exists()) {
            return;
        }

        $role = Role::firstOrCreate([
            'name' => Utils::getSuperAdminName(),
            'guard_name' => 'web',
        ]);

        User::factory()->create([
            'is_approved' => true,
            'approved_at' => now(),
        ])->assignRole($role);
    }
}
