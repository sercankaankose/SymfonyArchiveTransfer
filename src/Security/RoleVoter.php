<?php

namespace App\Security;

use App\Params\ROLE_PARAM;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RoleVoter extends Voter {
    protected function supports($attribute, $subject): bool {
        return $attribute === 'roles';
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool {
        $user = $token->getUser();

        if (!$user) {
            return false;
        }

        $userRoles = $user->getRoles();


        if (in_array(ROLE_PARAM::ROLE_ADMIN, $userRoles)) {
            return true;
        }

        return false;
    }

}
