<?php

namespace App\Model;

class RegistrationModel
{
    public ?string $nom = null;
    public ?string $prenom = null;
    public ?string $email = null;
    public ?string $plainPassword = null;
    public ?string $userType = null;
    public $medecin = null; // si assistant
}
