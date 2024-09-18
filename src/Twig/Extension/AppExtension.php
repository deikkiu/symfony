<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
	public function getFilters(): array
	{
		return [
			new TwigFilter('price', [$this, 'formatPrice']),
		];
	}

	public function getFunctions(): array
	{
		return [
			new TwigFunction('volume', [$this, 'formatVolume']),
		];
	}

	public function formatPrice(int $number): string
	{
		$price = number_format($number / 100, 2, '.', ',');
		return $price . '$';
	}

	public function formatVolume(?int $length, ?int $width, ?int $height): string
	{
		$length = $length ?? 0;
		$width = $width ?? 0;
		$height = $height ?? 0;

		return "{$length} x {$width} x {$height} sm";
	}
}
