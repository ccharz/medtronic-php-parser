<?php

namespace Ccharz\MedtronicParser\Tests;

use Ccharz\MedtronicParser\CsvParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class CsvParserTest extends TestCase
{
    use MatchesSnapshots;

    public static function basicParsingProvider(): array
    {
        $files = glob(__DIR__.'/samples/exports/*.csv');

        return array_map(
            fn (string $filepath) => [
                $filepath,
            ],
            $files
        );
    }

    #[DataProvider('basicParsingProvider')]
    public function test_basic_parsing(string $filepath): void
    {
        $importer = new CsvParser($filepath, 'Europe/Vienna');

        $lines = [];
        $importer->parse(function (
            string $type,
            array $values
        ) use (&$lines) {
            $lines[$type][] = $values;
        });

        $this->assertMatchesJsonSnapshot($lines['pump'] ?? []);
        $this->assertMatchesJsonSnapshot($lines['sensor'] ?? []);
        $this->assertMatchesJsonSnapshot($lines['auto'] ?? []);
    }
}
