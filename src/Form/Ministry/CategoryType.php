<?php

namespace App\Form\Ministry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\MinistryType;

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
                'class' => 'App\Entity\Person',
                'choice_label' => 'lastnameAndFirstname',
                'required' => false,
            ))
            ->add('ministries', CollectionType::class, array(
                'entry_type' => MinistryType::class,
                'label' => false,
                'prototype' => true,
                'allow_add' => true,
                'by_reference' => false,
                'widget_add_btn' => array('label' => 'Add Ministry'),
                'allow_delete' => true,
                'horizontal_input_wrapper_class' => 'clearfix',
                'entry_options' => array(
                    'label' => false,
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

            $event->setData($data);
        });
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Ministry\Category'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'category';
    }
}
