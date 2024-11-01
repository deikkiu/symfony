<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\ImportProductsMessage;
use App\Service\ProductImporter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ImportProductsMessageHandler
{
	public function __construct(
		private ProductImporter $importer,
		private Filesystem      $filesystem,
	)
	{
	}

	public function __invoke(ImportProductsMessage $message): void
	{
		$filePath = $message->getFilePath();
		$this->importer->import($filePath);
		$this->filesystem->remove($filePath);
	}
}
