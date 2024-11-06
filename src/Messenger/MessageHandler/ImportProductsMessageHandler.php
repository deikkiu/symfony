<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\ImportProductsMessage;
use App\Service\ProductImporter;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(fromTransport: 'async')]
final readonly class ImportProductsMessageHandler
{
	public function __construct(
		private ProductImporter $importer
	)
	{
	}

	public function __invoke(ImportProductsMessage $message): void
	{
		$userId = $message->getUserId();
		$filePath = $message->getFilePath();
		$importSlug = $message->getImportSlug();

		$this->importer->import($filePath, $userId, $importSlug);
	}
}
