<?php

namespace App\Form;

use App\Entity\Ride;
use App\Service\StravaAPI;
use App\Service\KomootAPI;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class AddrideFormType extends AbstractType
{
    public function __construct(private Security $security, private StravaAPI $strava_api, private KomootAPI $komoot_api)
    {
        $this->user = $this->security->getUser();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();

            //Switch depending on provider
            switch ($this->user->getPreferredProvider()) {
                case 'komoot':
                    $athleteActivities = $this->komoot_api->getAthleteActivitiesThisMonth($this->user);
                    break;
    
                case 'strava':
                    $athleteActivities = $this->strava_api->getAthleteActivitiesThisMonth($this->user);
                    break;
    
                default:
                   $athleteActivities = null;
            }

            if (!$athleteActivities){
                return;
            }

            $form->add('ride', ChoiceType::class, [
                    'choices' => $athleteActivities,
                    'choice_label' => function(?Ride $ride) {
                        return "Ride {$ride->getRideId()} on ({$ride->getDate()->format('d-m-Y')}) of {$ride->getKm()} km";
                    },
                    'label' => 'Select a recent century ride from the dropdown menu:',
                    'expanded' => false,
                    'multiple' => false,
                ]
            );
        });
        
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $ride = $event->getData()['ride'];
            $ride->setUser($this->user);
 
            //check ride data stream
            $realRide = false;
            switch ($this->user->getPreferredProvider()) {
                case 'komoot':
                    //request and analyse ride stream
                    $checkRideStream = $this->komoot_api->processRideStream($this->user, $ride->getRideId(), $ride->getDate());
                    $ride->setClubRide($checkRideStream['isClubride']);
                    $realRide = $checkRideStream['isRealride'];
                    break;
                case 'strava':
                    //request and analyse ride stream
                    $checkRideStream = $this->strava_api->processRideStream($this->user, $ride->getRideId(), $ride->getDate());
                    $ride->setClubRide($checkRideStream['isClubride']);
                    $realRide = $checkRideStream['isRealride'];
                    break;
            }

            //modify event data
            if($realRide){
                $event->setData(['ride' => $ride]);
            } else {
                $event->setData(['ride' => null]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }
}