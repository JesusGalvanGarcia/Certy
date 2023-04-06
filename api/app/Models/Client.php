<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Authenticatable
{
    use SoftDeletes;
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;

    protected $table = "clients";

    protected $fillable = [
        'complete_name',
        'email',
        'email_verified_at',
        'cellphone',
        'cp',
        'age',
        'genre',
        'rfc',
        'suburb',
        'state',
        'township',
        'street',
        'street_number',
        'int_street_number',
        'password',
        'status_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function findForPassport(string $user_email): Client
    {
        return $this->where('email', $user_email)->first();
    }
}
