<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Form\Ministry\ResponsibleAssignmentType;

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
//            ->add('category', EntityType::class, array(
//                'class' => 'AppBundle\Entity\Ministry\Category',
//                'EntityType' => 'name',
//                'required' => false,
//            ))
            ->add('responsibleAssignments', CollectionType::class, array(
                'type' => ResponsibleAssignmentType::class,
                'label' => false,
                'prototype' => true,
                'allow_add' => true,
                'by_reference' => false,
                'widget_add_btn' => array('label' => 'Add Responsible'),
                'allow_delete' => true,
                'horizontal_input_wrapper_class' => 'clearfix',
                'options' => array(
                    'label' => false,
                    'csrf_protection' => $options['csrf_protection'],
                )
            ))
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) {
            $data = $event->getData();
            unset($data['id']);
            if (isset($data['responsibleAssignments']) && is_array($data['responsibleAssignments'])) {
                foreach ($data['responsibleAssignments'] as $index => $assignmentData) {
                    if (empty($assignmentData['group']['id']) && empty($assignmentData['person']['id'])) {
                        unset($data['responsibleAssignments'][$index]);
                    }
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
            'data_class' => 'AppBundle\Entity\Ministry'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'ministry';
    }
}
