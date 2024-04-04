<?php
// src/Validator/Constraints/PasswordPolicy.php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */

class PasswordPolicy extends Constraint
{
    public $message = 'Şifreniz en az 1 harf en az 1 sayı ,1 harf ve 1 tane ".","-","_" karakterlerinden birini içermelidir.';
}
