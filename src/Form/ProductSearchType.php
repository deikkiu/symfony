<?php

namespace App\Form;

use App\Entity\Category;
use App\Form\Object\ProductSearch;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductSearchType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('name')
			->add('category', EntityType::class, [
				'class' => Category::class,
				'choice_label' => 'name',
				'required' => false,
				'placeholder' => 'No category',
			])
			->add('minPrice', IntegerType::class, [
				'required' => false,
				'empty_data' => null,
			])
			->add('maxPrice', IntegerType::class, [
				'required' => false,
				'empty_data' => null,
			])
			->add('isAmount', CheckboxType::class, [
				'required' => false,
			])
			->add('weight', IntegerType::class, [
				'required' => false,
				'empty_data' => null,
			])
			->add('sort', ChoiceType::class, [
				'choices' => [
					'No sorting' => null,
					'Sort by amount' => 'AMOUNT:DESC',
					'Sort by price' => [
						'Low to high' => 'PRICE:ASC',
						'High to low' => 'PRICE:DESC',
					],
					'Sort by weight' => [
						'Low to high' => 'WEIGHT:ASC',
						'High to low' => 'WEIGHT:DESC',
					],
				],
			])
			->add('submit', SubmitType::class);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => ProductSearch::class
		]);
	}
}
