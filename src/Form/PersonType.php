<?php

namespace App\Form;

use App\Entity\Address;
use App\Entity\Person;
use App\Entity\WorkingGroup;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PersonType extends AbstractType
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

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
                'choices' => array(
                    'male' => Person::GENDER_MALE,
                    'female' => Person::GENDER_FEMALE
                ),
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
                'class' => WorkingGroup::class,
                'choice_label' => function ($workingGroup) {
                    return $workingGroup->getDisplayName($this->translator);
                },
                'label' => 'Working Group',
                'required' => false,
            ))
            ->add('workerStatus', ChoiceType::class, array(
                'choices' => array_flip(Person::getAllWorkerStatus()),
                'label' => 'Able to work',
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
