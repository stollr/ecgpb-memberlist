<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * AppBundle\Repository\WorkingGroupRepository
 *
 * @author naitsirch
 */
class WorkingGroupRepository extends EntityRepository
{
    public function findAllForListing()
    {
        return $this->createQueryBuilder('workingGroup')
            ->select('workingGroup', 'leader', 'person')
            ->leftJoin('workingGroup.leader', 'leader')
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
