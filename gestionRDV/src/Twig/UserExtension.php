<?php

namespace App\Twig;

use App\Entity\Medecin;
use App\Entity\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UserExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_medecin', [$this, 'isMedecin']),
        ];
    }

    public function isMedecin($user): bool
    {
        return $user instanceof Medecin;
    }
}
