<?php

namespace App\Repository\Ministry;

use App\Entity\Ministry\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * App\Repository\Ministry\CategoryRepository
 *
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find(mixed $id)
 * @method Category|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method Category[] findAll()
 * @method Category[] findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    /**
     * @return Category[]
     */
    public function findAllForListing(): array
    {
        return $this->createQueryBuilder('category')
            ->select(
                'category', 'ministry', 'person'
            )
            ->leftJoin('category.ministries', 'ministry')
            ->leftJoin('ministry.responsibles', 'person')
            ->orderBy('category.position', 'asc')
            ->addOrderBy('category.name', 'asc')
            ->addOrderBy('ministry.position', 'asc')
            ->addOrderBy('ministry.id', 'asc')
            ->getQuery()
            ->getResult()
        ;
    }
}
