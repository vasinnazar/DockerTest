<?php

namespace App\Services;

use App\User;
use Illuminate\Support\Facades\Auth;

class PrivateDataService
{
    public static function formatPhone(User $user, $phone)
    {
        return $user->hasPermission('show.phones') ? $phone :
            substr_replace($phone, '*****', 2, 5);
    }
}