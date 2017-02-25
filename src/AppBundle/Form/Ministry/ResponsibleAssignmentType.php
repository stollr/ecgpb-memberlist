<?php

namespace AppBundle\Form\Ministry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResponsibleAssignmentType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
//            ->add('ministry')
            ->add('person', EntityType::class, array(
                'class' => 'AppBundle\Entity\Person',
                'choice_label' => 'lastnameAndFirstname',
                'required' => false,
            ))
            ->add('group', EntityType::class, array(
                'class' => 'AppBundle\Entity\Ministry\Group',
                'choice_label' => 'name',
                'required' => false,
            ))
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) {
            $data = $event->getData();
            unset($data['id']);
            if (isset($data['person']['id'])) {
                $data['person'] = $data['person']['id'];
            }
            if (isset($data['group']['id'])) {
                $data['group'] = $data['group']['id'];
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
            'data_class' => 'AppBundle\Entity\Ministry\ResponsibleAssignment'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'responsibleassignment';
    }
}
