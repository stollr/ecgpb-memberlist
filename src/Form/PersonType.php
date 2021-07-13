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
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
            $builder->add('address', EntityType::class, [
                'class' => Address::class,
                'choice_label' => 'dropdownLabel',
                'query_builder' => function (EntityRepository $repo) {
                    return $repo->createQueryBuilder('address')
                        ->select('address')
                        ->orderBy('address.familyName', 'asc')
                    ;
                }
            ]);
        }

        $builder
            ->add('lastname', TextType::class, [
                'label' => 'Differing Last Name',
                'required' => false,
                'help' => 'Leave this field empty to use the family name of the address.',
            ])
            ->add('firstname', TextType::class, [
                'label' => 'First Name',
            ])
            ->add('dob', DateType::class, [
                'label' => 'Date of Birth',
                'widget' => 'single_text',
                'format' => \IntlDateFormatter::MEDIUM,
            ])
            ->add('gender', ChoiceType::class, [
                'choices' => [
                    'male' => Person::GENDER_MALE,
                    'female' => Person::GENDER_FEMALE
                ],
            ])
            ->add('mobile', TextType::class, [
                'required' => false,
            ])
            ->add('email', TextType::class, [
                'label' => 'E-mail',
                'required' => false,
            ])
            ->add('phone2', TextType::class, [
                'label' => 'Second Phone',
                'required' => false,
            ])
            ->add('phone2Label', TextType::class, [
                'label' => 'Second Phone Label',
                'required' => false,
                'help' => 'You can enter a label for the second phone number. Enter "\\n" for line break.',
            ])
            ->add('maidenName', TextType::class, [
                'label' => 'Maiden Name',
                'required' => false,
            ])
            ->add('workingGroup', EntityType::class, [
                'class' => WorkingGroup::class,
                'choice_label' => function ($workingGroup) {
                    return $workingGroup->getDisplayName($this->translator);
                },
                'label' => 'Working Group',
                'required' => false,
            ])
            ->add('workerStatus', ChoiceType::class, [
                'choices' => array_flip(Person::getAllWorkerStatus()),
                'label' => 'Able to work',
            ])
            ->add('notice', TextareaType::class, [
                'required' => false,
            ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Person::class,
            'add_address_field' => false,
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'person';
    }
}
