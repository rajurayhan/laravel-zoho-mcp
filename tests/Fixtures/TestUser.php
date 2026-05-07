<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

final class TestUser extends Authenticatable
{
    protected $table = 'users';

    /**
     * @var list<string>
     */
    protected $fillable = ['name', 'email', 'password'];

    /**
     * @var array<string, string>
     */
    protected $hidden = ['password', 'remember_token'];
}
