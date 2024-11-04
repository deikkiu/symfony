<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\ImportProductsMessage;
use App\Service\ProductImporter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ImportProductsMessageHandler
{
	public function __construct(
		private ProductImporter $importer,
		private Filesystem      $filesystem,
		private LoggerInterface $logger,

	)
	{
	}

	public function __invoke(ImportProductsMessage $message): void
	{
		$userId = $message->getUserId();
		$filePath = $message->getFilePath();

		$this->importer->import($filePath, $userId);
		$this->filesystem->remove($filePath);

		// @TODO: create a table for imports
		$messages = $this->importer->getImportMessages();

		foreach ($messages as $message) {
			$message['type'] === 'success'
				? $this->logger->info($message['message'])
				: $this->logger->error($message['message']);
		}
	}
}
