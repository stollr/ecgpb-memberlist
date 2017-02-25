<?php

namespace AppBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Form\PersonType;
use AppBundle\Entity\WorkingGroup;

class WorkingGroupType extends AbstractType
{
    private $workingGroup;

    public function __construct(WorkingGroup $workingGroup)
    {
        $this->workingGroup = $workingGroup;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $workingGroup = $this->workingGroup;
        $builder
            ->add('number', IntegerType::class, array(
                'label' => 'Group Number',
            ))
            ->add('gender', ChoiceType::class, array(
                'label' => 'Group of Women/Men',
                'choices' => array('m' => 'Men', 'f' => 'Women'),
                'read_only' => $workingGroup->getId() > 0,
            ))
        ;
        if ($workingGroup->getId()) {
            $builder
                ->add('leader', EntityType::class, array(
                    'class' => 'AppBundle\Entity\Person',
                    'choice_label' => 'lastnameFirstnameAndDob',
                    'required' => false,
                    'query_builder' => function(EntityRepository $repo) use ($workingGroup) {
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
                ->add('persons', CollectionType::class, array(
                    'type' => 'entity',
                    'label' => 'Persons',
                    'prototype' => true,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'widget_add_btn' => array('label' => 'Add Person'),
                    'widget_form_group' => true,
                    'options' => array(
                        'label' => false,
                        'class' => 'AppBundle\Entity\Person',
                        'choice_label' => 'lastnameFirstnameAndDob',
                        'query_builder' => function(EntityRepository $repo) use ($workingGroup) {
                            return $repo->createQueryBuilder('person')
                                ->select('person')
                                ->leftJoin('person.address', 'address')
                                ->where('person.gender = :gender')
                                ->orderBy('person.workingGroup')
                                ->addOrderBy('address.familyName')
                                ->addOrderBy('person.firstname')
                                ->setParameter('gender', $workingGroup->getGender())
                            ;
                        },
                        'group_by' => 'optgroupLabelInWorkingGroupDropdown',
                    )
                ))
            ;
        }
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\WorkingGroup'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'workinggroup';
    }
}
