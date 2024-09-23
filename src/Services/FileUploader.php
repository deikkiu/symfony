<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
	public function __construct(
		protected SluggerInterface $slugger,
		protected string           $targetDirectory
	)
	{
	}

	public function upload(UploadedFile $file): string
	{
		$originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
		$safeFilename = $this->slugger->slug($originalFilename);

		$fileName = $this->existFile($safeFilename, $file->guessExtension());

		try {
			$file->move($this->getTargetDirectory(), $fileName);
		} catch (FileException $e) {
			throw new FileException(sprintf('File upload error: %s', $e->getMessage()));
		}

		return $fileName;
	}

	public function getTargetDirectory(): string
	{
		return $this->targetDirectory;
	}

	public function existFile(string $fileName, string $extension): string
	{
		$fullPathFile = $this->getTargetDirectory() . $fileName . '.' . $extension;

		if (file_exists($fullPathFile)) {
			return $fileName . '.' . $extension;
		}

		return $fileName . '-' . uniqid() . '.' . $extension;
	}
}