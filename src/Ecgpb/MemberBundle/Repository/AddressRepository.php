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
    public function getListFilterQb($term, $personIdsWithoutPhoto = null)
    {
        $qb = $this->createQueryBuilder('address')
            ->select('address', 'person')
            ->leftJoin('address.persons', 'person')
            ->orderBy('address.familyName', 'asc')
            ->addOrderBy('person.dob', 'asc')
        ;

        if ('' !== trim($term)) {
            $words = explode(' ', trim($term));
            $attributes = array('address.familyName', 'person.firstname');

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

        if (is_array($personIdsWithoutPhoto) && count($personIdsWithoutPhoto) > 0) {
            $qb->andWhere('person.id IN (:person_ids_without_photo)');
            $qb->setParameter('person_ids_without_photo', $personIdsWithoutPhoto);
        }

        return $qb;
    }
}
