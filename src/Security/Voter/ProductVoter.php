<?php

namespace App\Security\Voter;

use App\Entity\Product;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ProductVoter extends Voter
{
	public const EDIT = 'EDIT';
	public const DELETE = 'DELETE';
	public const CREATE = 'CREATE';
	public const SHOW = 'SHOW';

	public function __construct(
		private readonly Security $security,
	)
	{
	}

	protected function supports(string $attribute, mixed $subject): bool
	{
		// replace with your own logic
		// https://symfony.com/doc/current/security/voters.html
		return in_array($attribute, [self::EDIT, self::DELETE, self::CREATE, self::SHOW]) && $subject instanceof \App\Entity\Product;
	}

	protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
	{
		$user = $token->getUser();
		$product = $subject;

		if (!$user instanceof User) {
			return false;
		}

		if ($this->security->isGranted('ROLE_ADMIN')) {
			return true;
		}

		return match ($attribute) {
			self::EDIT, self::DELETE => $this->getAccess($product, $user),
			self::CREATE => $this->canCreate($user),
			self::SHOW => $this->canShow($user),
			default => false,
		};
	}

	private function getAccess(Product $product, User $user): bool
	{
		return $product->getUser() === $user;
	}

	private function canCreate(User $user): bool
	{
		return in_array('ROLE_MANAGER', $user->getRoles());
	}

	private function canShow(User $user): bool
	{
		return !in_array('ROLE_USER', $user->getRoles());
	}
}
