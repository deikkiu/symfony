<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('name')
			->add('category', EntityType::class, [
				'class' => Category::class,
				'choice_label' => 'name',
				'placeholder' => 'Choose a category',
			])
			->add('colors', CollectionType::class, [
				'entry_type' => ColorType::class,
				'entry_options' => ['label' => false],
				'allow_add' => true,
				'by_reference' => false,
				'allow_delete' => true
			])
			->add('price')
			->add('amount')
			->add('descr')
			->add('product_attr', ProductAttrType::class)
			->add('submit', SubmitType::class);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => Product::class,
		]);
	}
}
