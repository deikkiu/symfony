<?php

namespace App\Form\Object;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

class ProductImport
{
	#[Assert\NotBlank(message: 'Please upload a csv file')]
	#[Assert\File(maxSize: '1M', mimeTypes: ['text/csv'], mimeTypesMessage: 'Please upload a valid CSV file.')]
	private ?File $file = null;

	public function getFile(): ?File
	{
		return $this->file;
	}

	public function setFile(?File $file): void
	{
		$this->file = $file;
	}
}