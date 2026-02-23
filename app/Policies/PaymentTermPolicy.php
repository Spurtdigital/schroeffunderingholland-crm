<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PaymentTerm;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentTermPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PaymentTerm');
    }

    public function view(AuthUser $authUser, PaymentTerm $paymentTerm): bool
    {
        return $authUser->can('View:PaymentTerm');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PaymentTerm');
    }

    public function update(AuthUser $authUser, PaymentTerm $paymentTerm): bool
    {
        return $authUser->can('Update:PaymentTerm');
    }

    public function delete(AuthUser $authUser, PaymentTerm $paymentTerm): bool
    {
        return $authUser->can('Delete:PaymentTerm');
    }

    public function restore(AuthUser $authUser, PaymentTerm $paymentTerm): bool
    {
        return $authUser->can('Restore:PaymentTerm');
    }

    public function forceDelete(AuthUser $authUser, PaymentTerm $paymentTerm): bool
    {
        return $authUser->can('ForceDelete:PaymentTerm');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PaymentTerm');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PaymentTerm');
    }

    public function replicate(AuthUser $authUser, PaymentTerm $paymentTerm): bool
    {
        return $authUser->can('Replicate:PaymentTerm');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PaymentTerm');
    }

}