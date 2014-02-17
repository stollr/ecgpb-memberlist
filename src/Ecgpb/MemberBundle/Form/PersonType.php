<?php

namespace Ecgpb\MemberBundle\Form;

use Doctrine\ORM\EntityRepository;
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
        if ($options['add_address_field']) {
            $builder->add('address', 'entity', array(
                'class' => 'Ecgpb\MemberBundle\Entity\Address',
                'property' => 'dropdownLabel',
                'query_builder' => function (EntityRepository $repo) {
                    return $repo->createQueryBuilder('address')
                        ->select('address')
                        ->orderBy('address.familyName', 'asc')
                    ;
                }
            ));
        }

        $builder
            ->add('lastname', 'text', array(
                'label' => 'Differing Last Name',
                'required' => false,
                'help_block' => 'Leave this field empty to use the family name of the address.',
            ))
            ->add('firstname', 'text', array(
                'label' => 'First Name',
            ))
            ->add('dob', 'date', array(
                'label' => 'Date of Birth',
                'widget' => 'single_text',
                'format' => \IntlDateFormatter::MEDIUM,
            ))
            ->add('gender', 'choice', array(
                'choices' => array('m' => 'male', 'f' => 'female'),
            ))
            ->add('mobile', 'text', array(
                'required' => false,
            ))
            ->add('email', 'text', array(
                'required' => false,
            ))
            ->add('phone2', 'text', array(
                'label' => 'Second Phone',
                'required' => false,
            ))
            ->add('phone2Label', 'text', array(
                'label' => 'Second Phone Label',
                'required' => false,
                'help_block' => 'You can enter a label for the second phone number. Enter "\\n" for line break.',
            ))
            ->add('maidenName', 'text', array(
                'label' => 'Maiden Name',
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
            'data_class' => 'Ecgpb\MemberBundle\Entity\Person',
            'add_address_field' => false,
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
