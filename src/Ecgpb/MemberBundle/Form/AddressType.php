<?php

namespace Ecgpb\MemberBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Ecgpb\MemberBundle\Form\PersonType;

/**
 * Ecgpb\MemberBundle\Form\AddressType
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
            ->add('familyName')
            ->add('phone')
            ->add('street')
            ->add('zip')
            ->add('city')
            ->add('persons', 'collection', array(
                'type' => new PersonType(),
            ))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Ecgpb\MemberBundle\Entity\Address'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'ecgpb_memberbundle_address';
    }
}
