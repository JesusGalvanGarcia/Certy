<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use App\Models\UserLog;
use Illuminate\Support\ServiceProvider;

class UserService extends ServiceProvider
{
    static function checkUser($user_id)
    {

        $user = Client::where('status_id', 1)->find($user_id);

        return $user;
    }

    static function log($user_id, $module, $description, $type)
    {

        UserLog::create([
            'user_id' => $user_id,
            'module' => $module,
            'description' => $description,
            'type' => $type
        ]);

        return true;
    }
}
