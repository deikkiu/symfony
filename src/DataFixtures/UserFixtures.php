<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
	private UserPasswordHasherInterface $hasher;

	public function __construct(UserPasswordHasherInterface $hasher)
	{
		$this->hasher = $hasher;
	}

    public function load(ObjectManager $manager): void
    {
	    $user = new User();
	    $user->setEmail("user@mail.com");
	    $password = $this->hasher->hashPassword($user, '123456');
	    $user->setPassword($password);

	    $manager->persist($user);

	    $user = new User();
	    $user->setEmail("manager@mail.com");
		$user->setRoles(['ROLE_MANAGER']);
	    $password = $this->hasher->hashPassword($user, '123456');
	    $user->setPassword($password);

	    $manager->persist($user);

	    $user = new User();
	    $user->setEmail("admin@mail.com");
	    $user->setRoles(['ROLE_ADMIN']);
	    $password = $this->hasher->hashPassword($user, '123456');
	    $user->setPassword($password);

		$manager->persist($user);
		$manager->flush();
    }
}
