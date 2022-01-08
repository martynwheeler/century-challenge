<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
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
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message *',
                'attr' => ['placeholder' => 'Message *', 'style' => 'height: 200px'],
                'required' => true,
            ])
        ;
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
