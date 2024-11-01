<?php

namespace App\Messenger\Message;

final readonly class ImportProductsMessage
{
	public function __construct(private string $filePath)
	{
	}

	public function getFilePath(): string
	{
		return $this->filePath;
	}
}
