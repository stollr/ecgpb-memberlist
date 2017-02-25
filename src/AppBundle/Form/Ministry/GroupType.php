<?php

namespace AppBundle\Form\Ministry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class GroupType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class)
            ->add('persons', CollectionType::class, array(
                'type' => EntityType::class,
                'label' => false,
                'required' => false,
                'prototype' => true,
                'allow_add' => true,
                'by_reference' => false,
                'widget_add_btn' => array('label' => 'Add Person'),
                'allow_delete' => true,
                'horizontal_input_wrapper_class' => 'clearfix',
                'options' => array(
                    'label' => false,
                    'class' => 'AppBundle\Entity\Person',
                    'choice_label' => 'lastnameAndFirstname',
                    'required' => false,
                    'csrf_protection' => $options['csrf_protection'],
                )
            ))
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            unset($data['id']);
            if (isset($data['persons']) && is_array($data['persons'])) {
                foreach ($data['persons'] as $index => $person) {
                    if (!$person) {
                        unset($data['persons'][$index]);
                        continue;
                    }
                    $data['persons'][$index] = $person['id'];
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
            'data_class' => 'AppBundle\Entity\Ministry\Group'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'ecgpb_memberbundle_ministry_group';
    }
}
