<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\QuoteAssumption;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuoteAssumptionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:QuoteAssumption');
    }

    public function view(AuthUser $authUser, QuoteAssumption $quoteAssumption): bool
    {
        return $authUser->can('View:QuoteAssumption');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:QuoteAssumption');
    }

    public function update(AuthUser $authUser, QuoteAssumption $quoteAssumption): bool
    {
        return $authUser->can('Update:QuoteAssumption');
    }

    public function delete(AuthUser $authUser, QuoteAssumption $quoteAssumption): bool
    {
        return $authUser->can('Delete:QuoteAssumption');
    }

    public function restore(AuthUser $authUser, QuoteAssumption $quoteAssumption): bool
    {
        return $authUser->can('Restore:QuoteAssumption');
    }

    public function forceDelete(AuthUser $authUser, QuoteAssumption $quoteAssumption): bool
    {
        return $authUser->can('ForceDelete:QuoteAssumption');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:QuoteAssumption');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:QuoteAssumption');
    }

    public function replicate(AuthUser $authUser, QuoteAssumption $quoteAssumption): bool
    {
        return $authUser->can('Replicate:QuoteAssumption');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:QuoteAssumption');
    }

}