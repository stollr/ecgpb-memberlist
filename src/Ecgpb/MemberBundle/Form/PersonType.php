<?php

namespace Ecgpb\MemberBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PersonType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname')
            ->add('birthDate', 'date', array(
                'widget' => 'single_text',
            ))
            ->add('mobile', 'text', array(
                'required' => false,
            ))
            ->add('email', 'text', array(
                'required' => false,
            ))
            ->add('phone2', 'text', array(
                'required' => false,
            ))
            ->add('maidenName', 'text', array(
                'required' => false,
            ))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Ecgpb\MemberBundle\Entity\Person'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'ecgpb_memberbundle_person';
    }
}
