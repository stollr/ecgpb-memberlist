<?php

namespace Ecgpb\MemberBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Ecgpb\MemberBundle\Form\Ministry\ResponsibleAssignmentType;

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
            ->add('position', 'integer', array(
                'required' => false,
            ))
//            ->add('category', 'entity', array(
//                'class' => 'Ecgpb\MemberBundle\Entity\Ministry\Category',
//                'property' => 'name',
//                'required' => false,
//            ))
            ->add('responsibleAssignments', 'collection', array(
                'type' => new ResponsibleAssignmentType(),
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
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Ecgpb\MemberBundle\Entity\Ministry'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'ecgpb_memberbundle_ministry';
    }
}
