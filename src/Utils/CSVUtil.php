<?php

namespace App\Utils;

class CSVUtil
{
	public static function readCSV(string $filePath, array &$columns): array
	{
		$rows = [];

		if (($handle = fopen($filePath, "r")) !== false) {
			while (($data = fgetcsv($handle, 1000, ",")) !== false) {
				$row = array_slice($data, 0, 11);
				$rows[] = $row;

				foreach ($row as $index => $value) {
					if (!empty($value) && $index < count($columns)) {
						$columns[$index]['show'] = true;
					}
				}
			}

			fclose($handle);
		}

		return $rows;
	}
}