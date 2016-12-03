<?php

namespace Ecgpb\MemberBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Ecgpb\MemberBundle\Repository\AddressRepository
 *
 * @author naitsirch
 */
class AddressRepository extends EntityRepository
{
    public function getListFilterQb(array $filter = array())
    {
        $qb = $this->createQueryBuilder('address')
            ->select('address', 'person')
            ->leftJoin('address.persons', 'person')
        ;

        if (isset($filter['term']) && '' !== trim($filter['term'])) {
            $words = explode(' ', trim($filter['term']));
            $attributes = array('address.familyName', 'person.firstname', 'person.email');

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
}
