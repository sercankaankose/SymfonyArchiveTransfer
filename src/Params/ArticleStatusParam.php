<?php

namespace App\Params;

use App\Entity\User;
use Symfony\Component\Security\Http\Authenticator\AccessTokenAuthenticator;

class ArticleStatusParam{
    const WAITING = 'waiting';
    const EDIT_REQUIRED = 'EditRequired';
    const ERROR= 'Error';


}