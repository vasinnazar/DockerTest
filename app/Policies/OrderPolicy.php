<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\User;
use App\Order;

class OrderPolicy
{
//    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
    public function getAllOrders(User $user, Order $order)
    {
        return $user->id!=5;
    }
}
