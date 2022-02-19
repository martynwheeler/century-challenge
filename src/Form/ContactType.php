<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Full Name *',
                'attr' => ['placeholder' => 'Full Name *'],
                'required' => true,
            ])
            ->add('fromEmail', EmailType::class, [
                'label' => 'Email Address *',
                'attr' => ['placeholder' => 'Email Address *'],
                'required' => true,
                'constraints' => [new Assert\Email([
                    'message' => 'The email "{{ value }}" is not a valid email.',
                ])],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message *',
                'attr' => ['placeholder' => 'Message *', 'style' => 'height: 200px'],
                'required' => true,
            ])
        ;
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
