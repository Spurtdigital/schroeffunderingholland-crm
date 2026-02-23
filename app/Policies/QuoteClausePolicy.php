<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\QuoteClause;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuoteClausePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:QuoteClause');
    }

    public function view(AuthUser $authUser, QuoteClause $quoteClause): bool
    {
        return $authUser->can('View:QuoteClause');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:QuoteClause');
    }

    public function update(AuthUser $authUser, QuoteClause $quoteClause): bool
    {
        return $authUser->can('Update:QuoteClause');
    }

    public function delete(AuthUser $authUser, QuoteClause $quoteClause): bool
    {
        return $authUser->can('Delete:QuoteClause');
    }

    public function restore(AuthUser $authUser, QuoteClause $quoteClause): bool
    {
        return $authUser->can('Restore:QuoteClause');
    }

    public function forceDelete(AuthUser $authUser, QuoteClause $quoteClause): bool
    {
        return $authUser->can('ForceDelete:QuoteClause');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:QuoteClause');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:QuoteClause');
    }

    public function replicate(AuthUser $authUser, QuoteClause $quoteClause): bool
    {
        return $authUser->can('Replicate:QuoteClause');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:QuoteClause');
    }

}