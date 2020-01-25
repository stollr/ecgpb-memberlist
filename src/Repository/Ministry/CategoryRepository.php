<?php

namespace App\Repository\Ministry;

use Doctrine\ORM\EntityRepository;

/**
 * App\Repository\Ministry\CategoryRepository
 *
 * @author naitsirch
 */
class CategoryRepository extends EntityRepository
{
    public function findAllForListing()
    {
        return $this->createQueryBuilder('category')
            ->select(
                'category', 'ministry', 'person'
            )
            ->leftJoin('category.ministries', 'ministry')
            ->leftJoin('ministry.responsibles', 'person')
            ->orderBy('category.position', 'asc')
            ->addOrderBy('category.name', 'asc')
            ->addOrderBy('ministry.position', 'asc')
            ->addOrderBy('ministry.id', 'asc')
            ->getQuery()
            ->getResult()
        ;
    }
}
