<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Category::class);
	}

	public function countAllProductsByCategory(Category $category): int
	{
		return $this->createQueryBuilder('c')
			->select('COUNT(p.id)')
			->leftJoin('c.products', 'p')
			->where('c.id = :id')
			->setParameter('id', $category->getId())
			->getQuery()
			->getSingleScalarResult();
	}

	public function countPublishedProductsByCategory(Category $category): int
	{
		return $this->createQueryBuilder('c')
			->select('COUNT(p.id)')
			->leftJoin('c.products', 'p')
			->where('p.isDraft = 0')
			->andWhere('c.id = :id')
			->setParameter('id', $category->getId())
			->getQuery()
			->getSingleScalarResult();
	}

	public function findFirst(Category $category): ?Category
	{
		return $this->createQueryBuilder('c')
			->select('c')
			->where('c.id <> :id')
			->setParameter('id', $category->getId())
			->andWhere('c.parent IS NULL')
			->setMaxResults(1)
			->getQuery()
			->getOneOrNullResult();
	}

	public function countSubcategories(Category $category): ?int
	{
		return $this->createQueryBuilder('c')
			->select('COUNT(c.id)')
			->where('c.parent = :id')
			->setParameter('id', $category->getId())
			->getQuery()
			->getSingleScalarResult();
	}

	//    /**
	//     * @return Category[] Returns an array of Category objects
	//     */
	//    public function findByExampleField($value): array
	//    {
	//        return $this->createQueryBuilder('c')
	//            ->andWhere('c.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->orderBy('c.id', 'ASC')
	//            ->setMaxResults(10)
	//            ->getQuery()
	//            ->getResult()
	//        ;
	//    }

	//    public function findOneBySomeField($value): ?Category
	//    {
	//        return $this->createQueryBuilder('c')
	//            ->andWhere('c.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->getQuery()
	//            ->getOneOrNullResult()
	//        ;
	//    }
}
