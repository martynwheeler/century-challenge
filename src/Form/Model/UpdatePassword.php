<?php

// src/Form/Model/UpdatePassword.php

namespace App\Form\Model;

use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;

class UpdatePassword
{
    #[SecurityAssert\UserPassword(
        message: 'Wrong value for your current password',
    )]
    protected string $oldPassword;

    public function getOldPassword(): string
    {
        return $this->oldPassword;
    }

    public function setOldPassword($oldPassword): self
    {
        $this->oldPassword = $oldPassword;
        return $this;
    }
}
