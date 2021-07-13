<?php

namespace App\Form\Ministry;

use App\Entity\Ministry\Category;
use App\Entity\Person;
use App\Form\MinistryType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('position', IntegerType::class, [
                'required' => false,
            ])
            ->add('name', TextType::class)
            ->add('responsible', EntityType::class, [
                'label' => 'Person responsible',
                'class' => Person::class,
                'choice_label' => 'getLastnameFirstnameAndDob',
                'required' => false,
                'query_builder' => static function (EntityRepository $repo) {
                    return $repo->createQueryBuilder('person')
                        ->select('person', 'address')
                        ->join('person.address', 'address')
                        ->orderBy('address.familyName', 'ASC')
                        ->addOrderBy('person.firstname', 'ASC')
                    ;
                },
            ])
            ->add('ministries', CollectionType::class, [
                'entry_type' => MinistryType::class,
                'label' => false,
                'prototype' => true,
                'allow_add' => true,
                'by_reference' => false,
                'allow_delete' => true,
                'entry_options' => [
                    'label' => false,
                ],
            ])
        ;

//        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) {
//            $data = $event->getData();
//            unset($data['id']);
//            if (isset($data['responsible']['id'])) {
//                $data['responsible'] = $data['responsible']['id'];
//            }
//
//            $event->setData($data);
//        });
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return 'category';
    }
}
