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

	public function upload(UploadedFile $file, string $folder = ''): string
	{
		$directory = $this->getTargetDirectory() . $folder;

		$this->createFolder($directory);

		$originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
		$safeFilename = $this->slugger->slug($originalFilename);

		$fileName = $this->existFile($directory, $safeFilename, $file->guessExtension());

		try {
			$file->move($directory, $fileName);
		} catch (FileException $e) {
			throw new FileException(sprintf('File upload error: %s', $e->getMessage()));
		}

		return $folder . '/' . $fileName;
	}

	public function getTargetDirectory(): string
	{
		return $this->targetDirectory;
	}

	public function createFolder(string $directory): void
	{
		if (is_dir($directory)) {
			return;
		}

		mkdir($directory);
	}

	public function existFile(string $directory, string $fileName, string $extension): string
	{
		$fullPathFile = $directory . '/' . $fileName . '.' . $extension;

		if (file_exists($fullPathFile)) {
			return $fileName . '.' . $extension;
		}

		return $fileName . '-' . uniqid() . '.' . $extension;
	}
}