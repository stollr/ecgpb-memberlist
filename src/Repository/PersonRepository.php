<?php

namespace App\Repository;

use App\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * App\Repository\PersonRepository
 *
 * @author naitsirch
 */
class PersonRepository extends ServiceEntityRepository
{
    private static $nameCache;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Person::class);
    }

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

    /**
     * Find a person by lastname, firstname and date of birth. If none is found
     * null is returned.
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneOrNullByLastnameFirstnameAndDob(string $lastname, string $firstname, \DateTimeInterface $dob): ?Person
    {
        $qb = $this->createQueryBuilder('person')
            ->join('person.address', 'address')
            ->andWhere('person.lastname = :lastname OR address.familyName = :lastname')
            ->andWhere('person.firstname = :firstname')
            ->andWhere('person.dob = :dob')
            ->setParameter('lastname', $lastname)
            ->setParameter('firstname', $firstname)
            ->setParameter('dob', $dob->format('Y-m-d'))
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findAllForMinistryListing()
    {
        $qb = $this->createQueryBuilder('person')
            ->select('person', 'address')
            ->join('person.address', 'address')
            ->orderBy('address.familyName', 'ASC')
            ->addOrderBy('person.firstname', 'ASC')
        ;
        return $qb->getQuery()->getResult();
    }

    public function findAllForBirthdayList()
    {
        $qb = $this->createQueryBuilder('person')
            ->select('person', 'address')
            ->join('person.address', 'address')
            ->orderBy('person.dob')
        ;

        $persons = $qb->getQuery()->getResult();

        usort($persons, function (Person $a, Person $b) {
            if ($a->getDob()->format('md') == $b->getDob()->format('md')) {
                return (int) $a->getDob()->format('Y') >= (int) $b->getDob()->format('Y');
            }
            return (int) $a->getDob()->format('md') >= (int) $b->getDob()->format('md');
        });

        return $persons;
    }

    /**
     * Returns all persons who are (or will become) at least 65 years old (in this year).
     * @return Person[]
     */
    public function findSeniors()
    {
        $maxDate = new \DateTime();
        $maxDate->setDate((int) $maxDate->format('Y'), 1, 1);
        $maxDate->setTime(0, 0, 0);
        $maxDate->modify('-64 year');

        $qb = $this->createQueryBuilder('person')
            ->select('person', 'address')
            ->join('person.address', 'address')
            ->where('person.dob < :max_date')
            ->orderBy('person.dob')
            ->setParameter('max_date', $maxDate)
        ;

        $persons = $qb->getQuery()->getResult();

        usort($persons, function (Person $a, Person $b) {
            if ($a->getDob()->format('md') == $b->getDob()->format('md')) {
                return (int) $a->getDob()->format('Y') >= (int) $b->getDob()->format('Y');
            }
            return (int) $a->getDob()->format('md') >= (int) $b->getDob()->format('md');
        });

        return $persons;
    }

    /**
     * @return array|Person[]
     */
    public function findPersonsToBeAssignedToWorkingGroup()
    {
        $minimumAge = new \DateTime();
        $minimumAge->modify('-65 year');

        $dql = 'SELECT person, address
                FROM App\Entity\Person person
                JOIN person.address address
                LEFT JOIN person.leaderOf leadingWorkingGroup
                WHERE (person.workingGroup IS NULL AND leadingWorkingGroup.id IS NULL)
                AND person.workerStatus = :depending
                AND person.dob > :minimum_age
                ORDER By address.familyName, person.firstname'
        ;

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameter('depending', Person::WORKER_STATUS_DEPENDING);
        $query->setParameter('minimum_age', $minimumAge->format('Y-m-d'));

        return $query->getResult();
    }

    /**
     * Find persons that are under 65 years old but unable to work.
     *
     * @return array|Person[]
     */
    public function findPersonsUnableToWork()
    {
        $minimumAge = new \DateTime();
        $minimumAge->modify('-65 year');

        $dql = 'SELECT person, address
                FROM App\Entity\Person person
                JOIN person.address address
                WHERE person.workerStatus != :depending
                AND person.dob > :minimum_age
                ORDER By address.familyName, person.firstname'
        ;

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameter('depending', Person::WORKER_STATUS_DEPENDING);
        $query->setParameter('minimum_age', $minimumAge->format('Y-m-d'));

        return $query->getResult();
    }

    /**
     * Find all persons which are not contained in the passed array.
     *
     * @param Person[]|int[] $persons
     * @return Person[]
     */
    public function findAllNotIn(array $persons): array
    {
        $ids = [];

        foreach ($persons as $person) {
            $ids[] = $person instanceof Person ? $person->getId() : $person;
        }

        $dql = 'SELECT person
                FROM App\Entity\Person person
                WHERE person.id NOT IN (:ids)'
        ;

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameter('ids', $ids);

        return $query->getResult();
    }

    public function getAllEmailAdresses()
    {
        $dql = 'SELECT person.email FROM App\Entity\Person person WHERE person.email IS NOT NULL';
        $query = $this->getEntityManager()->createQuery($dql);

        $emails = array();
        foreach ($query->getResult() as $row) {
            $emails[] = $row['email'];
        }

        return $emails;
    }

    public function remove(Person $person, bool $flushImmediately = false): void
    {
        $this->getEntityManager()->remove($person);

        if ($flushImmediately) {
            $this->getEntityManager()->flush();
        }
    }

    public function add(Person $person, bool $flushImmediately = false): void
    {
        $this->getEntityManager()->persist($person);

        if ($flushImmediately) {
            $this->getEntityManager()->flush();
        }
    }
}
