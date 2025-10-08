<?php

namespace App\Policies;

use App\Models\ScheduledPayment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ScheduledPaymentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any scheduled payments.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the scheduled payment.
     */
    public function view(User $user, ScheduledPayment $scheduledPayment): bool
    {
        return $user->id === $scheduledPayment->user_id;
    }

    /**
     * Determine whether the user can create scheduled payments.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the scheduled payment.
     */
    public function update(User $user, ScheduledPayment $scheduledPayment): bool
    {
        return $user->id === $scheduledPayment->user_id;
    }

    /**
     * Determine whether the user can delete the scheduled payment.
     */
    public function delete(User $user, ScheduledPayment $scheduledPayment): bool
    {
        return $user->id === $scheduledPayment->user_id;
    }
}