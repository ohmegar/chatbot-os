<?php

namespace App\Models;

use Vizir\KeycloakWebGuard\Models\KeycloakUser;

class Keycloak extends KeycloakUser
{
    /**
     * Attributes we retrieve from Profile
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'emp_id'
    ];

    public function getKey()
    {
        return $this->emp_id;
    }
}
