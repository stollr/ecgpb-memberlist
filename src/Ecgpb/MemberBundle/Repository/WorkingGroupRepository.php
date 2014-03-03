<?php

namespace Ecgpb\MemberBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Ecgpb\MemberBundle\Repository\WorkingGroupRepository
 *
 * @author naitsirch
 */
class WorkingGroupRepository extends EntityRepository
{
    public function findAllForListing()
    {
        return $this->createQueryBuilder('workingGroup')
            ->select('workingGroup', 'person')
            ->join('workingGroup.persons', 'person')
            ->orderBy('workingGroup.gender')
            ->addOrderBy('workingGroup.number')
            ->getQuery()
            ->getResult()
        ;
    }
}
