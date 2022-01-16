<?php

namespace App\Form;

use App\Entity\User;
use GuzzleHttp\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class EditProfileFormType extends AbstractType
{
    public function __construct(Security $security)
    {
        $this->user = $security->getUser();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Username',
                'data' => $this->user->getUsername(),
                'disabled' => true,
            ])
            ->add('email', TextType::class, [
                'label' => 'Email address *',
                'data' => $this->user->getEmail(),
            ])
            ->add('forename', TextType::class, [
                'label' => 'First Name(s) *',
                'data' => $this->user->getForeName(),
            ])
            ->add('surname', TextType::class, [
                'label' => 'Surname *',
                'data' => $this->user->getSurName(),
            ])
        ;
        if ($options['stravaAthlete']) {
            //Might want to check db and current strava id match?
            $builder
                ->add('stravaID', TextType::class, [
                    'label' => 'Strava Athlete ID for '.$options['stravaAthlete']['firstname'].' '.$options['stravaAthlete']['lastname'],
                    'required' => false,
                    'data' => $options['stravaAthlete']['id'],
//                    'disabled' => true,
                    'attr'=> [ 'readonly' => true ],
                ])
            ;
        }

        if ($options['komootAthlete']) {
            //Might want to check db and current komoot id match?
            $builder
                ->add('komootID', TextType::class, [
                    'label' => 'Komoot ID for '.$options['komootAthlete']['display_name'],
                    'required' => false,
                    'data' => $options['komootAthlete']['username'],
//                    'disabled' => true,
                    'attr'=> [ 'readonly' => true ],
                ])
            ;
        }

        if ($options['komootAthlete'] && $options['stravaAthlete']) {
            $builder
                ->add('preferredProvider', ChoiceType::class, [
                    'label' => 'Default source for ride data',
                    'choices'  => [
                        'Strava' => 'strava',
                        'Komoot' => 'komoot',
                    ],
                    'multiple' => false,
                    'expanded' => true,
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'komootAthlete' => null,
            'stravaAthlete' => null,
        ]);
    }
}
