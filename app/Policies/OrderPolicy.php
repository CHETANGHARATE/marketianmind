<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine whether the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStudent() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        // Students can only view their own orders; Admins can view any order
        return $user->id === $order->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can create orders.
     */
    public function create(User $user): bool
    {
        return $user->isStudent();
    }

    /**
     * Determine whether the user can update the order.
     * Orders cannot be modified by students.
     */
    public function update(User $user, Order $order): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the order.
     * Orders cannot be deleted by students.
     */
    public function delete(User $user, Order $order): bool
    {
        return false;
    }
}
