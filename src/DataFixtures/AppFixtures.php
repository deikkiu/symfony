<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductAttr;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
	private UserPasswordHasherInterface $hasher;

	public function __construct(UserPasswordHasherInterface $hasher)
	{
		$this->hasher = $hasher;
	}

	public function load(ObjectManager $manager): void
    {
	    $categories = [];
		$users = [];

	    $user = new User();
	    $user->setEmail("user@mail.com");
	    $password = $this->hasher->hashPassword($user, '123456');
	    $user->setPassword($password);

	    $manager->persist($user);

	    $user = new User();
	    $user->setEmail("manager1@mail.com");
	    $user->setRoles(['ROLE_MANAGER']);
	    $password = $this->hasher->hashPassword($user, '123456');
	    $user->setPassword($password);

	    $manager->persist($user);

	    $users[] = $user;

	    $user = new User();
	    $user->setEmail("manager2@mail.com");
	    $user->setRoles(['ROLE_MANAGER']);
	    $password = $this->hasher->hashPassword($user, '123456');
	    $user->setPassword($password);

	    $manager->persist($user);

	    $users[] = $user;

	    $user = new User();
	    $user->setEmail("manager3@mail.com");
	    $user->setRoles(['ROLE_MANAGER']);
	    $password = $this->hasher->hashPassword($user, '123456');
	    $user->setPassword($password);

	    $manager->persist($user);

	    $users[] = $user;

	    $user = new User();
	    $user->setEmail("admin@mail.com");
	    $user->setRoles(['ROLE_ADMIN']);
	    $password = $this->hasher->hashPassword($user, '123456');
	    $user->setPassword($password);

	    $manager->persist($user);
	    $manager->flush();

	    $users[] = $user;

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
			$product->setUser($users[mt_rand(0, count($users) - 1)]);

			$productAttr = new ProductAttr();
			$productAttr->setWeight(mt_rand(200, 5000));

			$product->setProductAttr($productAttr);

		    $manager->persist($product);
	    }

	    $manager->flush();
    }
}
