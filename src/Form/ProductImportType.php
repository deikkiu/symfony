<?php

namespace App\Form;

use App\Form\Dto\ProductImportDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductImportType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('file', FileType::class, [
				'required' => true,
				'label' => 'Choose a file',
				'help' => 'Format: csv'
			])
			->add('import', SubmitType::class);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => ProductImportDto::class
		]);
	}
}
