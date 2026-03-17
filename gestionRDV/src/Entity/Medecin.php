<?php

namespace App\Entity;

use App\Repository\MedecinRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MedecinRepository::class)]
class Medecin extends User
{
    /**
     * @var Collection<int, Assistant>
     */
    #[ORM\OneToMany(targetEntity: Assistant::class, mappedBy: 'medecin')]
    private Collection $assistants;

    /**
     * @var Collection<int, RendezVous>
     */
    #[ORM\OneToMany(targetEntity: RendezVous::class, mappedBy: 'medecin')]
    private Collection $lesRendezVous;

    /**
     * @var Collection<int, DisponibiliteRecurrente>
     */
    #[ORM\OneToMany(targetEntity: DisponibiliteRecurrente::class, mappedBy: 'medecin', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $disponibilitesRecurrentes;

    public function __construct()
    {
        $this->assistants = new ArrayCollection();
        $this->lesRendezVous = new ArrayCollection();
        $this->disponibilitesRecurrentes = new ArrayCollection();
    }

    /**
     * @return Collection<int, Assistant>
     */
    public function getAssistants(): Collection
    {
        return $this->assistants;
    }

    public function addAssistant(Assistant $assistant): static
    {
        if (!$this->assistants->contains($assistant)) {
            $this->assistants->add($assistant);
            $assistant->setMedecin($this);
        }

        return $this;
    }

    public function removeAssistant(Assistant $assistant): static
    {
        if ($this->assistants->removeElement($assistant)) {
            // set the owning side to null (unless already changed)
            if ($assistant->getMedecin() === $this) {
                $assistant->setMedecin(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, RendezVous>
     */
    public function getLesRendezVous(): Collection
    {
        return $this->lesRendezVous;
    }

    public function addLesRendezVou(RendezVous $lesRendezVou): static
    {
        if (!$this->lesRendezVous->contains($lesRendezVou)) {
            $this->lesRendezVous->add($lesRendezVou);
            $lesRendezVou->setMedecin($this);
        }

        return $this;
    }

    public function removeLesRendezVou(RendezVous $lesRendezVou): static
    {
        if ($this->lesRendezVous->removeElement($lesRendezVou)) {
            // set the owning side to null (unless already changed)
            if ($lesRendezVou->getMedecin() === $this) {
                $lesRendezVou->setMedecin(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DisponibiliteRecurrente>
     */
    public function getDisponibilitesRecurrentes(): Collection
    {
        return $this->disponibilitesRecurrentes;
    }

    public function addDisponibiliteRecurrente(DisponibiliteRecurrente $disponibilite): static
    {
        if (!$this->disponibilitesRecurrentes->contains($disponibilite)) {
            $this->disponibilitesRecurrentes->add($disponibilite);
            $disponibilite->setMedecin($this);
        }

        return $this;
    }

    public function removeDisponibiliteRecurrente(DisponibiliteRecurrente $disponibilite): static
    {
        if ($this->disponibilitesRecurrentes->removeElement($disponibilite)) {
            if ($disponibilite->getMedecin() === $this) {
                $disponibilite->setMedecin(null);
            }
        }

        return $this;
    }

    public function getRoles(): array
    {
        $roles = parent::getRoles();
        $roles[] = 'ROLE_MEDECIN';

        return array_unique($roles);
    }
}
