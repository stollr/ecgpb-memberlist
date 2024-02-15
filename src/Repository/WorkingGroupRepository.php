<?php

namespace App\Repository;

use App\Entity\Person;
use App\Entity\WorkingGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * App\Repository\WorkingGroupRepository
 *
 * @extends ServiceEntityRepository<WorkingGroup>
 */
class WorkingGroupRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly int $ageLimit,
    ) {
        parent::__construct($registry, WorkingGroup::class);
    }

    /**
     * @return WorkingGroup[]
     */
    public function findAllForListing(): array
    {
        $dobOffset = new \DateTimeImmutable("-{$this->ageLimit} years");

        $dql = 'SELECT workingGroup, leader ' .
               'FROM App\Entity\WorkingGroup workingGroup ' .
               'LEFT JOIN workingGroup.leader leader ' .
               'LEFT JOIN workingGroup.persons  person ' .
               'WHERE person.workerStatus = :depending ' .
               'AND person.dob > :dobOffset ' .
               'ORDER BY workingGroup.gender, workingGroup.number'
        ;
        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameter('depending', Person::WORKER_STATUS_UNTIL_AGE_LIMIT);
        $query->setParameter('dobOffset', $dobOffset->format('Y-m-d'));

        return $query->getResult();
    }

    /**
     * @return WorkingGroup[]
     */
    public function findAllForMemberPdf(): array
    {
        $dobOffset = new \DateTimeImmutable("-{$this->ageLimit} years");

        $dql = 'SELECT workingGroup, person, leader ' .
               'FROM App\Entity\WorkingGroup workingGroup ' .
               'JOIN workingGroup.persons person ' .
               'JOIN person.address address ' .
               'LEFT JOIN workingGroup.leader leader ' .
               'WHERE person.workerStatus = :depending ' .
               'AND person.dob > :dobOffset ' .
               'ORDER BY workingGroup.gender, workingGroup.number, address.familyName, person.firstname'
        ;
        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameter('depending', Person::WORKER_STATUS_UNTIL_AGE_LIMIT);
        $query->setParameter('dobOffset', $dobOffset->format('Y-m-d'));

        return $query->getResult();
    }
}
