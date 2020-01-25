<?php

namespace App\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MinistryType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('position', IntegerType::class, array(
                'required' => false,
            ))
            ->add('responsibles', CollectionType::class, array(
                'entry_type' => EntityType::class,
                'label' => false,
                'prototype' => true,
                'allow_add' => true,
                'by_reference' => false,
                'widget_add_btn' => array('label' => 'Add Responsible'),
                'allow_delete' => true,
                'horizontal_input_wrapper_class' => 'clearfix',
                'entry_options' => array(
                    'label' => false,
                    'class' => \App\Entity\Person::class,
                )
            ))
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) {
            $data = $event->getData();
            unset($data['id']);
            if (isset($data['responsibles']) && is_array($data['responsibles'])) {
                foreach ($data['responsibles'] as $index => $personData) {
                    $data['responsibles'][$index] = $personData['id'];
                }
            }
            $event->setData($data);
        });
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Ministry'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'ministry';
    }
}
