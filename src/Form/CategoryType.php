<?php

namespace App\Form;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$category = $options['category'];

		$builder
			->add('name')
			->add('parent', EntityType::class, [
				'class' => Category::class,
				'query_builder' => function (CategoryRepository $er) use ($category) {
					$query = $er->createQueryBuilder('c');

					if ($category->getId()) {
						$query->where('c.id <> :id')
							->setParameter('id', $category->getId());
					}

					return $query;
				},
				'choice_label' => 'name',
				'required' => false,
				'placeholder' => 'Its parent category',
			])
			->add('submit', SubmitType::class);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => Category::class,
			'category' => null,
		]);
	}
}
