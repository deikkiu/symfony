<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class ProductType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('name', TextType::class, [
				'label' => 'Name*',
			])
			->add('category', EntityType::class, [
				'class' => Category::class,
				'choice_label' => 'name',
				'placeholder' => 'Choose a category',
				'label' => 'Category*',
			])
			->add('colors', CollectionType::class, [
				'entry_type' => ColorType::class,
				'entry_options' => ['label' => false],
				'allow_add' => true,
				'allow_delete' => true,
				'by_reference' => false,
				'label' => false
			])
			->add('price', IntegerType::class, [
				'label' => 'Price*',
				'help' => 'The price is calculated in cents'
			])
			->add('amount', IntegerType::class, [
				'label' => 'Amount*',
			])
			->add('descr', TextareaType::class, [
				'label' => 'Description',
				'required' => false,
			])
			->add('imagePath', FileType::class, [
				'label' => 'Photo of product',
				'required' => false,
				'mapped' => false,
				'constraints' => [
					new Image([
						'maxSize' => '2M',
						'mimeTypes' => [
							'image/*'
						],
						'mimeTypesMessage' => 'Please upload a valid image [png, jpg, jpeg, webp]',
					])
				]

			])
			->add('product_attr', ProductAttrType::class, [
				'label' => 'Product Attribute'
			])
			->add('submit', SubmitType::class);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => Product::class,
		]);
	}
}
