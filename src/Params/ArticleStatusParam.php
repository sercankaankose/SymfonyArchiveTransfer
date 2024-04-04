<?php

namespace App\Params;

use App\Entity\User;
use Symfony\Component\Security\Http\Authenticator\AccessTokenAuthenticator;

class ArticleStatusParam{
    const WAITING = '000';
    const EDIT_REQUIRED = '001';
    const EDITED = '002';
    const ERROR= '003';


}