<?php

use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

function superAdminRole(): Role
{
    return Role::firstOrCreate([
        'name' => Utils::getSuperAdminName(),
        'guard_name' => 'web',
    ]);
}

it('weigert niet-goedgekeurde gebruikers toegang tot het paneel', function () {
    $user = User::factory()->create(['is_approved' => false]);
    $user->assignRole(superAdminRole());

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('laat goedgekeurde super_admins toe', function () {
    $user = User::factory()->create([
        'is_approved' => true,
        'approved_at' => now(),
    ]);
    $user->assignRole(superAdminRole());

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
});

it('configureert app-authenticatie als tweede factor', function () {
    $providers = Filament::getPanel('admin')->getMultiFactorAuthenticationProviders();

    expect(array_keys($providers))->toContain('app');
});
