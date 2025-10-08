<?php

namespace App\Policies;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentMethodPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any payment methods.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the payment method.
     */
    public function view(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->id === $paymentMethod->user_id;
    }

    /**
     * Determine whether the user can create payment methods.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the payment method.
     */
    public function update(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->id === $paymentMethod->user_id;
    }

    /**
     * Determine whether the user can delete the payment method.
     */
    public function delete(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->id === $paymentMethod->user_id;
    }
}