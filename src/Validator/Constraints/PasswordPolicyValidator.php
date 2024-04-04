<?php
//PasswordPolicyValidator.php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PasswordPolicyValidator extends ConstraintValidator
{


    public function validate($value, Constraint $constraint) : void
    {
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[-_\.]).{5,15}$/', $value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}