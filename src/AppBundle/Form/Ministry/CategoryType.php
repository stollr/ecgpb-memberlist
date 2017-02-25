<?php

namespace AppBundle\Form\Ministry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
            ->add('name', TextType::class)
            ->add('responsible', EntityType::class, array(
                'class' => 'AppBundle\Entity\Person',
                'choice_label' => 'lastnameAndFirstname',
                'required' => false,
            ))
            ->add('ministries', CollectionType::class, array(
                'type' => MinistryType::class,
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
            ->add('position', IntegerType::class, array(
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
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
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
        return 'category';
    }
}
