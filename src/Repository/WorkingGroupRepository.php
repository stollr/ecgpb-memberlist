<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\Person;

/**
 * App\Repository\WorkingGroupRepository
 *
 * @author naitsirch
 */
class WorkingGroupRepository extends EntityRepository
{
    public function findAllForListing()
    {
        $minimumAge = new \DateTime();
        $minimumAge->modify('-65 year');

        $dql = 'SELECT workingGroup, leader, person ' .
               'FROM App\Entity\WorkingGroup workingGroup ' .
               'LEFT JOIN workingGroup.leader leader ' .
               'LEFT JOIN workingGroup.persons  person ' .
               'WHERE person.workerStatus = :depending ' .
               'AND person.dob > :minimumAge ' .
               'ORDER BY workingGroup.gender, workingGroup.number'
        ;
        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameter('depending', Person::WORKER_STATUS_DEPENDING);
        $query->setParameter('minimumAge', $minimumAge->format('Y-m-d'));

        return $query->getResult();
    }

    public function findAllForMemberPdf()
    {
        $minimumAge = new \DateTime();
        $minimumAge->modify('-65 year');

        $dql = 'SELECT workingGroup, person, leader ' .
               'FROM App\Entity\WorkingGroup workingGroup ' .
               'JOIN workingGroup.persons person ' .
               'JOIN person.address address ' .
               'LEFT JOIN workingGroup.leader leader ' .
               'WHERE person.workerStatus = :depending ' .
               'AND person.dob > :minimumAge ' .
               'ORDER BY workingGroup.gender, workingGroup.number, address.familyName, person.firstname'
        ;
        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameter('depending', Person::WORKER_STATUS_DEPENDING);
        $query->setParameter('minimumAge', $minimumAge->format('Y-m-d'));

        return $query->getResult();
    }
}
