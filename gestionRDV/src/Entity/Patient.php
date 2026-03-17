<?php

namespace App\Entity;

use App\Repository\PatientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PatientRepository::class)]
class Patient extends User
{
    /**
     * @var Collection<int, RendezVous>
     */
    #[ORM\OneToMany(targetEntity: RendezVous::class, mappedBy: 'patient', orphanRemoval: true)]
    private Collection $lesRendezVous;

    public function __construct()
    {
        $this->lesRendezVous = new ArrayCollection();
    }

    /**
     * @return Collection<int, RendezVous>
     */
    public function getLesRendezVous(): Collection
    {
        return $this->lesRendezVous;
    }

    public function addLesRendezVou(RendezVous $rendezVous): static
    {
        if (!$this->lesRendezVous->contains($rendezVous)) {
            $this->lesRendezVous->add($rendezVous);
            $rendezVous->setPatient($this);
        }

        return $this;
    }

    public function removeLesRendezVou(RendezVous $rendezVous): static
    {
        if ($this->lesRendezVous->removeElement($rendezVous)) {
            if ($rendezVous->getPatient() === $this) {
                $rendezVous->setPatient(null);
            }
        }

        return $this;
    }

    public function getRoles(): array
    {
        $roles = parent::getRoles();
        $roles[] = 'ROLE_PATIENT';

        return array_unique($roles);
    }
}
