<?php

namespace App\Params;

use App\Entity\User;
use Symfony\Component\Security\Http\Authenticator\AccessTokenAuthenticator;

class RoleParam
{
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_EDITOR = 'ROLE_EDITOR';
    const ROLE_OPERATOR = 'ROLE_OPERATOR';
    const ROLE_SYSTEM_OPERATOR = 'ROLE_SYSTEM_OPERATOR';

    const ROLE_ADMIN_ID = 1;
    const ROLE_EDITOR_ID = 2;
    const ROLE_OPERATOR_ID = 3;
    const ROLE_SYSTEM_OPERATOR_ID = 4;

}
