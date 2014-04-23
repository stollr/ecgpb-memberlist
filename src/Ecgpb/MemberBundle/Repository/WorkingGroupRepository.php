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
            ->leftJoin('workingGroup.persons', 'person')
            ->orderBy('workingGroup.gender')
            ->addOrderBy('workingGroup.number')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAllForMemberPdf()
    {
        return $this->createQueryBuilder('workingGroup')
            ->select('workingGroup', 'person', 'leader')
            ->join('workingGroup.persons', 'person')
            ->join('person.address', 'address')
            ->leftJoin('workingGroup.leader', 'leader')
            ->orderBy('workingGroup.gender')
            ->addOrderBy('workingGroup.number')
            ->addOrderBy('address.familyName')
            ->addOrderBy('person.firstname')
            ->getQuery()
            ->getResult()
        ;
    }
}
