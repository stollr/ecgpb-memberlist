<?php

namespace Ecgpb\MemberBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Ecgpb\MemberBundle\Entity\Person;

/**
 * Ecgpb\MemberBundle\Repository\PersonRepository
 *
 * @author naitsirch
 */
class PersonRepository extends EntityRepository
{
    private static $nameCache;

    public function isNameUnique(Person $person)
    {
        if (empty(self::$nameCache)) {
            $qb = $this->createQueryBuilder('person')
                ->select('person.firstname', 'address.familyName')
                ->join('person.address', 'address')
            ;
            foreach ($qb->getQuery()->getResult() as $row) {
                $key = $row['familyName'] . ',' . $row['firstname'];
                if (!isset(self::$nameCache[$key])) {
                    self::$nameCache[$key] = 0;
                }
                self::$nameCache[$key]++;
            }
        }
        $key = $person->getAddress()->getFamilyName() . ',' . $person->getFirstname();
        return isset(self::$nameCache[$key]) && self::$nameCache[$key] <= 1;
    }

    public function findAllForMinistryListing()
    {
        $qb = $this->createQueryBuilder('person')
            ->select('person', 'address')
            ->join('person.address', 'address')
        ;
        return $qb->getQuery()->getResult();
    }

    public function findAllForBirthdayList()
    {
        $qb = $this->createQueryBuilder('person')
            ->select('person', 'address')
            ->join('person.address', 'address')
        ;
        return $qb->getQuery()->getResult();
    }
}
