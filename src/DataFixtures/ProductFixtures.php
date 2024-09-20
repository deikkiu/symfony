<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductAttr;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
	public function load(ObjectManager $manager): void
    {
	    $categories = [];

	    for ($i = 1; $i <= 5; $i++) {
		    $category = new Category();
		    $category->setName('Category-' . $i);
		    $category->setProductCount(0);

		    $categories[] = $category;

		    $manager->persist($category);
	    }

	    for ($i = 1; $i <= 20; $i++) {
		    $product = new Product();
		    $product->setName('product '.$i);
		    $product->setPrice(mt_rand(100, 1000000));
			$product->setAmount(mt_rand(0, 50));
			$product->setCreatedAt(new \DateTime('now', new \DateTimeZone('Asia/Almaty')));
			$product->setUpdatedAt(new \DateTime('now', new \DateTimeZone('Asia/Almaty')));

			$product->setCategory($categories[mt_rand(0, count($categories) - 1)]);

			$productAttr = new ProductAttr();
			$productAttr->setWeight(mt_rand(200, 5000));

			$product->setProductAttr($productAttr);

		    $manager->persist($product);
	    }

	    $manager->flush();
    }
}
