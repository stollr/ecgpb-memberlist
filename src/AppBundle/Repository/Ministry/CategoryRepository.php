<?php

namespace AppBundle\Repository\Ministry;

use Doctrine\ORM\EntityRepository;

/**
 * AppBundle\Repository\Ministry\CategoryRepository
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
                'responsibleAssignment', 'responsiblePerson', 'responsibleGroup'
            )
            ->leftJoin('category.ministries', 'ministry')
            ->leftJoin('ministry.responsibleAssignments', 'responsibleAssignment')
            ->leftJoin('responsibleAssignment.person', 'responsiblePerson')
            ->leftJoin('responsibleAssignment.group', 'responsibleGroup')
            ->orderBy('category.position', 'asc')
            ->addOrderBy('category.name', 'asc')
            ->addOrderBy('ministry.position', 'asc')
            ->addOrderBy('ministry.id', 'asc')
            ->getQuery()
            ->getResult()
        ;
    }
}
