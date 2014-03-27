<?php

namespace Ecgpb\MemberBundle\Repository\Ministry;

use Doctrine\ORM\EntityRepository;

/**
 * Ecgpb\MemberBundle\Repository\Ministry\GroupRepository
 *
 * @author naitsirch
 */
class GroupRepository extends EntityRepository
{
    public function findAllForListing()
    {
        return $this->createQueryBuilder('ministryGroup')
            ->select('ministryGroup', 'person')
            ->leftJoin('ministryGroup.persons', 'person')
            ->getQuery()
            ->getResult()
        ;
    }
}
