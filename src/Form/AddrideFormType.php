<?php

namespace App\Form;

use App\Entity\Ride;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddrideFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('km', TextType::class, [
                'label' => 'Distance *',
            ])
            ->add('average_speed', TextType::class, [
                'label' => 'Average Speed',
                'required' => false,
            ])
            ->add('ride_id', TextType::class, [
                'label' => 'Ride ID',
                'required' => false,
            ])
            ->add('date', DateType::class, [
                'label' => 'Date of ride *',
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'yyyy-MM-dd',
                'attr' => [
                    'class' => 'datepicker',
                ],
            ])
            ->add('details', TextareaType::class, [
                'label' => 'Notes',
                    'attr' => [
                    'class' => 'input-xlarge',
                    'rows' => 4,
                ],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Ride::class,
        ]);
    }
}
