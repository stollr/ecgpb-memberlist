<?php

namespace App\Form;

use App\Entity\Address;
use App\Form\PersonType;
use libphonenumber\PhoneNumberFormat;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * App\Form\AddressType
 */
class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('namePrefix', TextType::class, [
                'label' => 'Name prefix',
                'required' => false,
                'attr' => [
                    'placeholder' => 'von, van, etc.',
                ]
            ])
            ->add('familyName', TextType::class, array(
                'label' => 'Family Name',
            ))
            ->add('phone', PhoneNumberType::class, [
                'required' => false,
                'default_region' => 'DE',
                'format' => PhoneNumberFormat::NATIONAL,
            ])
            ->add('street', TextType::class, array(
                'required' => false,
            ))
            ->add('zip', TextType::class, array(
                'required' => false,
            ))
            ->add('city', TextType::class, array(
                'required' => false,
            ))
            ->add('persons', CollectionType::class, array(
                'entry_type' => PersonType::class,
                'label_attr' => ['style' => 'font-size: 0px'],
                'prototype' => true,
                'allow_add' => true,
                'by_reference' => false,
                'allow_delete' => true,
                'entry_options' => array(
                    'label' => false,
                )
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'address';
    }
}
