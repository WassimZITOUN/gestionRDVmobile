<?php
namespace App\Enum;

enum Etat: string
{
    case DEMANDE  = 'demandé';
    case CONFIRME = 'confirmé';
    case ANNULE   = 'annulé';
    case REFUSE   = 'refusé';
    case DONE     = 'fait';

    public function label(): string
    {
        return match($this) {
            self::DEMANDE  => 'demandé',
            self::CONFIRME => 'confirmé',
            self::ANNULE   => 'annulé',
            self::REFUSE   => 'refusé',
            self::DONE     => 'fait',
        };
    }

    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->label()] = $case->value;
        }
        return $choices;
    }
    public function toString(): string
    {
        return $this->value;
    }
}