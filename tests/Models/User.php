<?php

namespace Bkfdev\FacebookPixel\Tests\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
    ];
}
