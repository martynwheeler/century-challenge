<?php

namespace App\Form;

use App\Entity\User;
use App\Service\KomootAPI;
use App\Service\StravaAPI;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class EditProfileFormType extends AbstractType
{
    public function __construct(
        private Security $security,
        private KomootAPI $komoot_api,
        private StravaAPI $strava_api,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            /** @var User $user */
            $user = $this->security->getUser();
            $komootAthlete = $this->komoot_api->getAthlete($user);
            $stravaAthlete = $this->strava_api->getAthlete($user);

            if ($stravaAthlete) {
                $form->add('stravaID', TextType::class, [
                    'label' => "Strava Athlete ID for {$stravaAthlete['firstname']} {$stravaAthlete['lastname']}",
                    'required' => false,
                    'data' => $stravaAthlete['id'],
                    'attr' => [ 'readonly' => true ],
                ]);
            }
            if ($komootAthlete) {
                $form->add('komootID', TextType::class, [
                     'label' => "Komoot ID for {$komootAthlete['display_name']}",
                     'required' => false,
                     'data' => $komootAthlete['username'],
                     'attr' => [ 'readonly' => true ],
                ]);
            }
            if ($komootAthlete && $stravaAthlete) {
                $form->add('preferredProvider', ChoiceType::class, [
                     'label' => 'Default source for ride data',
                     'choices'  => [
                         'Strava' => 'strava',
                         'Komoot' => 'komoot',
                     ],
                     'multiple' => false,
                     'expanded' => true,
                ]);
            }
        });

        /** @var User $user */
        $user = $this->security->getUser();
        $builder
            ->add('username', TextType::class, [
                'label' => 'Username',
                'data' => $user->getUserIdentifier(),
                'disabled' => true,
            ])
            ->add('email', TextType::class, [
                'label' => 'Email address *',
                'data' => $user->getEmail(),
            ])
            ->add('forename', TextType::class, [
                'label' => 'First Name(s) *',
                'data' => $user->getForeName(),
            ])
            ->add('surname', TextType::class, [
                'label' => 'Surname *',
                'data' => $user->getSurName(),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class
        ]);
    }
}
