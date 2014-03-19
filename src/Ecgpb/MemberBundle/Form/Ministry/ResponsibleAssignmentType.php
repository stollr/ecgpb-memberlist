<?php

namespace Ecgpb\MemberBundle\Form\Ministry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Ecgpb\MemberBundle\Form\Ministry\ContactAssignmentType;

class ResponsibleAssignmentType extends ContactAssignmentType
{
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Ecgpb\MemberBundle\Entity\Ministry\ResponsibleAssignment'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'responsibleassignment';
    }
}
