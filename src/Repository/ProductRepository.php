<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use App\Form\Object\ProductSearch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Product::class);
	}

	public function findAllOrderedByAttr(ProductSearch $data, bool $isUser = false): array
	{
		$queryBuilder = $this->createQueryBuilder('p');

		if ($isUser) {
			$queryBuilder
				->andWhere('p.isDraft = 0');
		}

		if ($data->getName() !== null) {
			$queryBuilder
				->andWhere('p.name LIKE :name')
				->setParameter('name', "%{$data->getName()}%");
		}

		if ($data->getMinPrice() !== null) {
			$queryBuilder
				->andWhere('p.price / 100 >= :min')
				->setParameter('min', $data->getMinPrice());
		}

		if ($data->getMaxPrice() !== null) {
			$queryBuilder
				->andWhere('p.price / 100 <= :max')
				->setParameter('max', $data->getMaxPrice());
		}

		if ($data->getIsAmount()) {
			$queryBuilder
				->andWhere('p.amount > 0');
		}

		if ($data->getCategory() !== null) {
			$queryBuilder
				->innerJoin('p.category', 'c')
				->andWhere('c.id = :id')
				->setParameter('id', $data->getCategory()->getId());
		}

		if ($data->getWeight() !== null || $data->getSort() !== null) {
			$queryBuilder
				->innerJoin('p.product_attr', 'a');
		}

		if ($data->getWeight() !== null) {
			$queryBuilder
				->andWhere('a.weight >= :weight')
				->setParameter('weight', $data->getWeight());
		}

		if ($data->getSort() !== null) {
			[$column, $order] = explode(':', $data->getSort());

			match ($column) {
				'AMOUNT' => $queryBuilder->addOrderBy('p.amount', $order),
				'WEIGHT' => $queryBuilder->addOrderBy('a.weight', $order),
				default => $queryBuilder->addOrderBy('p.' . strtolower($column), $order),
			};
		}

		return $queryBuilder->getQuery()->getResult();
	}

	public function findProductsInCategory($product, bool $isUser = false, ?int $limit = null): array
	{
		$queryBuilder = $this->createQueryBuilder('p');

		if ($isUser) {
			$queryBuilder
				->andWhere('p.isDraft = :draft')
				->setParameter('draft', 0);
		}

		$queryBuilder
			->andWhere('p.category = :category')
			->andWhere('p.id <> :id')
			->setParameter('category', $product->getCategory())
			->setParameter('id', $product->getId());

		if ($limit !== null) {
			$queryBuilder->setMaxResults($limit);
		}

		return $queryBuilder->getQuery()->getResult();
	}

	public function countProductsByCategory(Category $category): ?int
	{
		return $this->createQueryBuilder('p')
			->select('COUNT(p)')
			->where('p.category = :category')
			->setParameter('category', $category)
			->getQuery()
			->getSingleScalarResult();
	}

	//    /**
	//     * @return Product[] Returns an array of Product objects
	//     */
	//    public function findByExampleField($value): array
	//    {
	//        return $this->createQueryBuilder('p')
	//            ->andWhere('p.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->orderBy('p.id', 'ASC')
	//            ->setMaxResults(10)
	//            ->getQuery()
	//            ->getResult()
	//        ;
	//    }

	//    public function findOneBySomeField($value): ?Product
	//    {
	//        return $this->createQueryBuilder('p')
	//            ->andWhere('p.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->getQuery()
	//            ->getOneOrNullResult()
	//        ;
	//    }
}
