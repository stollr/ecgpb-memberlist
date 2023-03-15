<?php

namespace App\Repository;

use App\Entity\Address;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * App\Repository\AddressRepository
 *
 * @author naitsirch
 */
class AddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }

    public function getListFilterQb(array $filter = array())
    {
        $qb = $this->createQueryBuilder('address')
            ->select('address', 'person')
            ->leftJoin('address.persons', 'person')
        ;

        if (isset($filter['term']) && '' !== trim($filter['term'])) {
            $words = preg_split('/\s+/', trim($filter['term']));
            $attributes = array('address.familyName', 'person.firstname', 'person.email', 'person.mobile', 'address.street');

            $andExpr = new \Doctrine\ORM\Query\Expr\Andx();
            foreach ($words as $wordIndex => $word) {
                $orExpr = new \Doctrine\ORM\Query\Expr\Orx();
                foreach ($attributes as $attribute) {
                    $orExpr->add($attribute . ' LIKE :word_' . $wordIndex);
                }
                $andExpr->add($orExpr);
                $qb->setParameter('word_' . $wordIndex, '%' . $word . '%');
            }
            $qb->andWhere($andExpr);
        }

        if (!empty($filter['has-email'])) {
            $qb->andWhere('person.email IS NOT NULL');
            $qb->andWhere("person.email != ''");
        }

        if (isset($filter['no-photo']) && is_array($filter['no-photo']) && count($filter['no-photo']) > 0) {
            $qb->andWhere('person.id IN (:person_ids_without_photo)');
            $qb->setParameter('person_ids_without_photo', $filter['no-photo']);
        }

        return $qb;
    }

    public function add(Address $address, bool $flushImmediately = false): void
    {
        $this->getEntityManager()->persist($address);

        if ($flushImmediately) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Address $address, bool $flushImmediately = false): void
    {
        $this->getEntityManager()->remove($address);

        if ($flushImmediately) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all log entries of the address.
     *
     * @return \Gedmo\Loggable\Entity\LogEntry[]
     */
    public function findLogEntries(Address $address): array
    {
        return $this->getEntityManager()
            ->getRepository(\Gedmo\Loggable\Entity\LogEntry::class)
            ->getLogEntries($address);
    }

    /**
     * Find all log entries of the address' persons.
     *
     * @return array<int, \Gedmo\Loggable\Entity\LogEntry[]>
     */
    public function findPersonsLogEntries(Address $address): array
    {
        $repo = $this->getEntityManager()->getRepository(\Gedmo\Loggable\Entity\LogEntry::class);
        $logs = [];

        foreach ($address->getPersons() as $person) {
            $logs[$person->getId()] = [
                'person' => $person,
                'logs' => $repo->getLogEntries($person),
            ];
        }

        return $logs;
    }
}
