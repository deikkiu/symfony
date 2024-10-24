<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
	public function __construct(
		protected SluggerInterface $slugger,
		protected Filesystem       $filesystem,
		protected string           $uploadsDirectory
	)
	{
	}

	public function upload(UploadedFile $file, string $folder = ''): string
	{
		$directory = $this->getTargetDirectory() . $folder;
		$this->filesystem->mkdir($directory);

		$fileName = $this->existFile($directory, uniqid() . '.' . $file->guessExtension());

		try {
			$file->move($directory, $fileName);
		} catch (FileException $e) {
			throw new FileException(sprintf('File upload error: %s', $e->getMessage()));
		}

		return $folder . '/' . $fileName;
	}

	public function uploadAndDumpFile(string $content, string $extension, string $folder = ''): string
	{
		$directory = $this->getTargetDirectory() . $folder;
		$this->filesystem->mkdir($directory);

		$fileName = $this->existFile($directory, uniqid() . '.' . $extension);

		try {
			$this->filesystem->dumpFile($directory . '/' . $fileName, $content);
		} catch (FileException $e) {
			throw new FileException(sprintf('File upload error: %s', $e->getMessage()));
		}

		return $folder . '/' . $fileName;
	}

	private function getTargetDirectory(): string
	{
		return $this->uploadsDirectory;
	}

	private function existFile(string $directory, string $fileName): string
	{
		$fullPathFile = $directory . '/' . $fileName;

		if ($this->filesystem->exists($fullPathFile)) {
			return uniqid() . '-' . $fileName;
		}

		return $fileName;
	}
}