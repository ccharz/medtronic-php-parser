<?php

namespace Ccharz\MedtronicParser\Tests;

use Ccharz\MedtronicParser\ProfileParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ProfileParserTest extends TestCase
{
    use MatchesSnapshots;

    public static function basicParsingProvider(): array
    {
        $files = glob(__DIR__.'/samples/profiles/*.pdf');

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
        $importer = new ProfileParser($filepath, 'de');

        $result = $importer->parse();

        $this->assertMatchesJsonSnapshot($result);
    }
}
