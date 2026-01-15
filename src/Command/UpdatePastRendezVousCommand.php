<?php

namespace App\Command;

use App\Repository\EtatRepository;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-past-rendez-vous',
    description: 'Change l\'état des rendez-vous confirmés passés à "réalisé"',
)]
class UpdatePastRendezVousCommand extends Command
{
    public function __construct(
        private RendezVousRepository $rendezVousRepository,
        private EtatRepository $etatRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $etatConfirme = $this->etatRepository->find(2)
            ?? $this->etatRepository->findOneBy(['libelle' => 'confirmé']);
        if (!$etatConfirme) {
            $io->error('Etat "confirme" introuvable');
            return Command::FAILURE;
        }

        $etatRealise = $this->etatRepository->find(5)
            ?? $this->etatRepository->findOneBy(['libelle' => 'réalisé'])
            ?? $this->etatRepository->findOneBy(['libelle' => 'realisé']);
        if (!$etatRealise) {
            $io->error('Etat "realise" introuvable');
            return Command::FAILURE;
        }

        $now = new \DateTime();
        $rendezVousPasses = $this->rendezVousRepository->createQueryBuilder('r')
            ->where('r.etat = :etatConfirme')
            ->andWhere('r.fin < :now')
            ->setParameter('etatConfirme', $etatConfirme)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        $count = count($rendezVousPasses);
        
        if ($count === 0) {
            $io->success('Aucun rendez-vous confirme passe a mettre a jour.');
            return Command::SUCCESS;
        }

        foreach ($rendezVousPasses as $rdv) {
            $rdv->setEtat($etatRealise);
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d rendez-vous confirme(s) passe(s) a l\'etat "realise".', $count));

        return Command::SUCCESS;
    }
}
