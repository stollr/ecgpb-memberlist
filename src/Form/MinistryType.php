<?php

namespace App\Form;

use App\Entity\Person;
use App\Entity\Ministry;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MinistryType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('position', IntegerType::class, [
                'required' => false,
            ])
            ->add('responsibles', CollectionType::class, [
                'entry_type' => EntityType::class,
                'label' => 'Responsibles',
                'label_attr' => ['class' => 'pt-0'],
                'prototype' => true,
                'prototype_name' => '__responsibles__',
                'allow_add' => true,
                'by_reference' => false,
                'allow_delete' => true,
                'entry_options' => [
                    'label' => false,
                    'class' => Person::class,
                    'choice_label' => 'getLastnameFirstnameAndDob',
                    'row_attr' => ['class' => 'd-flex'],
                    'attr' => ['class' => 'mr-2'],
                    'query_builder' => static function (EntityRepository $repo) {
                        return $repo->createQueryBuilder('person')
                            ->select('person', 'address')
                            ->join('person.address', 'address')
                            ->orderBy('address.familyName', 'ASC')
                            ->addOrderBy('person.firstname', 'ASC')
                        ;
                    },
                ]
            ])
        ;

//        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) {
//            $data = $event->getData();
//            unset($data['id']);
//            if (isset($data['responsibles']) && is_array($data['responsibles'])) {
//                foreach ($data['responsibles'] as $index => $personData) {
//                    $data['responsibles'][$index] = $personData['id'];
//                }
//            }
//            $event->setData($data);
//        });
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ministry::class
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return 'ministry';
    }
}
