<?php

namespace App\Messenger\Message;

final readonly class ImportProductsMessage
{
	public function __construct(
		private string $filePath,
		private int    $userId,
		private string $importSlug,
	)
	{
	}

	public function getFilePath(): string
	{
		return $this->filePath;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getImportSlug(): string
	{
		return $this->importSlug;
	}
}
