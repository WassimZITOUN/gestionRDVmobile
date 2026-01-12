<?php

namespace App\Form;

use App\Entity\DisponibiliteRecurrente;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DisponibiliteRecurrenteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('jourSemaine', ChoiceType::class, [
                'label' => 'Jour de la semaine',
                'choices' => [
                    'Lundi' => 1,
                    'Mardi' => 2,
                    'Mercredi' => 3,
                    'Jeudi' => 4,
                    'Vendredi' => 5,
                    'Samedi' => 6,
                    'Dimanche' => 7,
                ],
                'placeholder' => 'Choisir un jour',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('heureDebut', TimeType::class, [
                'label' => 'Heure de début',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('heureFin', TimeType::class, [
                'label' => 'Heure de fin',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('dureeRdvMinutes', ChoiceType::class, [
                'label' => 'Durée d\'un rendez-vous',
                'choices' => [
                    '30 minutes' => 30,
                    '45 minutes' => 45,
                    '1 heure' => 60,
                    '1h30' => 90,
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Plage active',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'label_attr' => [
                    'class' => 'form-check-label',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DisponibiliteRecurrente::class,
        ]);
    }
}
