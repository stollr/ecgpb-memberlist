<?php

namespace AppBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Entity\Address;
use AppBundle\Entity\Person;

class PersonType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['add_address_field']) {
            $builder->add('address', EntityType::class, array(
                'class' => Address::class,
                'choice_label' => 'dropdownLabel',
                'query_builder' => function (EntityRepository $repo) {
                    return $repo->createQueryBuilder('address')
                        ->select('address')
                        ->orderBy('address.familyName', 'asc')
                    ;
                }
            ));
        }

        $builder
            ->add('lastname', TextType::class, array(
                'label' => 'Differing Last Name',
                'required' => false,
                'help_block' => 'Leave this field empty to use the family name of the address.',
            ))
            ->add('firstname', TextType::class, array(
                'label' => 'First Name',
            ))
            ->add('dob', DateType::class, array(
                'label' => 'Date of Birth',
                'widget' => 'single_text',
                'format' => \IntlDateFormatter::MEDIUM,
            ))
            ->add('gender', ChoiceType::class, array(
                'choices' => array('m' => 'male', 'f' => 'female'),
            ))
            ->add('mobile', TextType::class, array(
                'required' => false,
            ))
            ->add('email', TextType::class, array(
                'required' => false,
            ))
            ->add('phone2', TextType::class, array(
                'label' => 'Second Phone',
                'required' => false,
            ))
            ->add('phone2Label', TextType::class, array(
                'label' => 'Second Phone Label',
                'required' => false,
                'help_block' => 'You can enter a label for the second phone number. Enter "\\n" for line break.',
            ))
            ->add('maidenName', TextType::class, array(
                'label' => 'Maiden Name',
                'required' => false,
            ))
            ->add('workingGroup', EntityType::class, array(
                'class' => 'AppBundle\Entity\WorkingGroup',
                'choice_label' => 'displayName',
                'label' => 'Working Group',
                'required' => false,
            ))
            ->add('workerStatus', ChoiceType::class, array(
                'choices' => Person::getAllWorkerStatus(),
                'empty_data' => Person::WORKER_STATUS_DEPENDING,
                'placeholder' => 'Depending on Age (< 60)',
                'label' => 'Able to work',
                'required' => false,
            ))
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Person::class,
            'add_address_field' => false,
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'person';
    }
}
