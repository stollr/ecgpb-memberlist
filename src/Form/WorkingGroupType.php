<?php

namespace App\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Person;
use App\Entity\WorkingGroup;

class WorkingGroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $workingGroup = $builder->getData();

        $builder
            ->add('number', IntegerType::class, [
                'label' => 'Group Number',
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Group of Women/Men',
                'choices' => [
                    'Men' => Person::GENDER_MALE,
                    'Women' => Person::GENDER_FEMALE,
                ],
                'disabled' => $workingGroup->getId() > 0,
            ])
        ;
        if ($workingGroup->getId()) {
            $builder
                ->add('leader', EntityType::class, array(
                    'class' => 'App\Entity\Person',
                    'choice_label' => 'lastnameFirstnameAndDob',
                    'required' => false,
                    'query_builder' => static function(EntityRepository $repo) use ($workingGroup) {
                        return $repo->createQueryBuilder('person')
                            ->select('person')
                            ->leftJoin('person.address', 'address')
                            ->where('person.gender = :gender')
                            ->orderBy('address.familyName')
                            ->addOrderBy('person.firstname')
                            ->setParameter('gender', $workingGroup->getGender())
                        ;
                    }
                ))
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WorkingGroup::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'working_group';
    }
}
