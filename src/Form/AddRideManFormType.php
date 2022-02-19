<?php

namespace App\Form;

use App\Entity\Ride;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AddRideManFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('km', NumberType::class, [
                'label' => 'Distance *',
                'constraints' => [new Assert\GreaterThanOrEqual(100)],
            ])
            ->add('average_speed', NumberType::class, [
                'label' => 'Average Speed',
                'required' => false,
                'scale' => 2,
            ])
            ->add('date', DateType::class, [
                'label' => 'Date of ride *',
                'widget' => 'single_text',
                'constraints' => [new Assert\LessThanOrEqual([
                    'value' => 'today',
                    'message' => 'The ride date cannot be in the future.',
                ])],
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ride::class,
        ]);
    }
}
