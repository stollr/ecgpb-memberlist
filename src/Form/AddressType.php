<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Form\PersonType;
use AppBundle\Entity\Address;

/**
 * AppBundle\Form\AddressType
 */
class AddressType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('familyName', TextType::class, array(
                'label' => 'Family Name',
            ))
            ->add('phone', TextType::class, array(
                'required' => false,
            ))
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
                'widget_add_btn' => array('label' => 'Add Person'),
                'allow_delete' => true,
                'horizontal_input_wrapper_class' => 'clearfix',
                'entry_options' => array(
                    'label' => false,
                    'horizontal_input_wrapper_class' => 'col-md-10',
                )
            ))
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Address::class,
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'address';
    }
}
