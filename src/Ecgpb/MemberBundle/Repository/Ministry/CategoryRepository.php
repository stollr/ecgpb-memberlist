<?php

namespace Ecgpb\MemberBundle\Repository\Ministry;

use Doctrine\ORM\EntityRepository;

/**
 * Ecgpb\MemberBundle\Repository\Ministry\CategoryRepository
 *
 * @author naitsirch
 */
class CategoryRepository extends EntityRepository
{
    public function findAllForListing()
    {
        return $this->createQueryBuilder('category')
            ->select(
                'category', 'ministry',
                'contactAssignment', 'contactPerson', 'contactGroup',
                'responsibleAssignment', 'responsiblePerson', 'responsibleGroup'
            )
            ->leftJoin('category.ministries', 'ministry')
            ->leftJoin('ministry.contactAssignments', 'contactAssignment')
            ->leftJoin('contactAssignment.person', 'contactPerson')
            ->leftJoin('contactAssignment.group', 'contactGroup')
            ->leftJoin('ministry.responsibleAssignments', 'responsibleAssignment')
            ->leftJoin('responsibleAssignment.person', 'responsiblePerson')
            ->leftJoin('responsibleAssignment.group', 'responsibleGroup')
            ->getQuery()
            ->getResult()
        ;
    }
}
