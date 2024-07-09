<?php
namespace App\Params;

use App\Entity\User;
use Symfony\Component\Security\Http\Authenticator\AccessTokenAuthenticator;

class JournalStatusParam{
    const WAITING = '000';
    const TRANSFERSTAGE = '001';
    const TRANSFERRED = '002';
    const ERROR= '003';
    const ARCHIVECHECKED= '004';

}