<?php

namespace App\EventListener;

use App\Entity\Product;
use App\Model\CategoryModel;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;


#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Product::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Product::class)]
#[AsEntityListener(event: Events::postRemove, method: 'postRemove', entity: Product::class)]
final readonly class ProductListener
{
	public function __construct(
		private CategoryModel $categoryModel,
	)
	{
	}

	public function postPersist(Product $product, PostPersistEventArgs $eventArgs): void
	{
		$entity = $eventArgs->getObject();

		if (!$entity instanceof Product) {
			return;
		}

		if ($product->getCategory()) {
			$this->categoryModel->updateCountProducts($entity->getCategory());
		}
	}

	public function postUpdate(Product $product, PostUpdateEventArgs $eventArgs): void
	{
		$entity = $eventArgs->getObject();
		$uow = $eventArgs->getObjectManager()->getUnitOfWork();

		if (!$entity instanceof Product) {
			return;
		}

		$changes = $uow->getEntityChangeSet($entity);

		if (array_key_exists('category', $changes)) {
			$oldCategory = $changes['category'][0];

			if ($oldCategory) {
				$this->categoryModel->updateCountProducts($oldCategory);
			}
		}

		if ($product->getCategory()) {
			$this->categoryModel->updateCountProducts($entity->getCategory());
		}
	}

	public function postRemove(Product $product, PostRemoveEventArgs $eventArgs): void
	{
		$entity = $eventArgs->getObject();

		if (!$entity instanceof Product) {
			return;
		}

		if ($product->getCategory()) {
			$this->categoryModel->updateCountProducts($entity->getCategory());
		}
	}
}



