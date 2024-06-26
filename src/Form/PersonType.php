<?php

namespace App\Form;

use App\Entity\Address;
use App\Entity\Person;
use App\Entity\WorkingGroup;
use Doctrine\ORM\EntityRepository;
use libphonenumber\PhoneNumberFormat;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PersonType extends AbstractType
{
    public function __construct(
        private TranslatorInterface $translator,
        private int $workingGroupAgeLimit,
    ) {
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $workerStatusChoices = [];

        foreach (Person::getAllWorkerStatus() as $status => $label) {
            $label = $status === Person::WORKER_STATUS_UNTIL_AGE_LIMIT
                ? $this->translator->trans('Until age limit (%ageLimit% years)', [
                    '%ageLimit%' => $this->workingGroupAgeLimit,
                ])
                : $label;
            $workerStatusChoices[$label] = $status;
        }

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
                'required' => $builder->getData()?->getDob() !== null,
            ])
            ->add('gender', ChoiceType::class, [
                'choices' => [
                    'male' => Person::GENDER_MALE,
                    'female' => Person::GENDER_FEMALE
                ],
            ])
            ->add('mobile', PhoneNumberType::class, [
                'required' => false,
                'default_region' => 'DE',
                'format' => PhoneNumberFormat::INTERNATIONAL,
            ])
            ->add('email', TextType::class, [
                'label' => 'E-mail',
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
                'choices' => $workerStatusChoices,
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
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Person::class,
            'add_address_field' => false,
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'person';
    }
}
