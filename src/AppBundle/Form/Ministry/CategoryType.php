<?php

namespace AppBundle\Form\Ministry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use AppBundle\Form\MinistryType;

class CategoryType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text')
            ->add('responsible', 'entity', array(
                'class' => 'AppBundle\Entity\Person',
                'property' => 'lastnameAndFirstname',
                'required' => false,
            ))
            ->add('ministries', 'collection', array(
                'type' => new MinistryType(),
                'label' => false,
                'prototype' => true,
                'allow_add' => true,
                'by_reference' => false,
                'widget_add_btn' => array('label' => 'Add Ministry'),
                'allow_delete' => true,
                'horizontal_input_wrapper_class' => 'clearfix',
                'options' => array(
                    'label' => false,
                    'csrf_protection' => $options['csrf_protection'],
                )
            ))
            ->add('position', 'integer', array(
                'required' => false,
            ))
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) {
            $data = $event->getData();
            unset($data['id']);
            if (isset($data['responsible']['id'])) {
                $data['responsible'] = $data['responsible']['id'];
            }
            if (isset($data['ministries']) && is_array($data['ministries'])) {
                foreach ($data['ministries'] as $index => $ministryData) {
                    if (empty($ministryData['name'])) {
                        unset($data['ministries'][$index]);
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
            'data_class' => 'AppBundle\Entity\Ministry\Category'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'ecgpb_memberbundle_ministry_category';
    }
}
