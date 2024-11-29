<?php

namespace Ccharz\MedtronicParser;

class ProfileParser
{
    protected array $index;

    protected array $content;

    protected array $locales_in_index = [
        'de' => [
            'basal_rates' => 'I.E./h;Zeit',
            'correction_factor' => 'K-Faktor;Zeit',
            'target_blood_sugar' => ';Hoch',
            'carb_ratio_factor' => 'Verhältnis;Zeit',
            'insulin_action_in_minutes' => 'Wirkdauer aktiv. Insul.;(h:mm)',
        ],
    ];

    public function __construct(protected readonly string $filepath, protected readonly string $locale)
    {
    }

    protected function parseTable(int $start, int $lines, int $columns = 3): array
    {
        $output = [];

        for ($i = 0; $i < $lines; $i++) {
            $line = [];

            for ($j = 0; $j < $columns; $j++) {
                $line[] = $this->content[$start - ($i * $columns) - $j];
            }

            $output[] = $line;
        }

        return $output;
    }

    protected function parseFile(string $file): void
    {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($file);
        $data = $pdf->getPages()[1]->getDataTm();

        $this->index = [];
        $this->content = [];

        foreach ($data as $object_index => $object) {
            $text = $object[1];

            if (! in_array($text, ['<>', '--']) && ! is_numeric($text)) {
                $this->index[
                    $data[$object_index][1].';'
                    .$data[$object_index + 1][1]
                ][] = $object_index;
            }

            $this->content[$object_index] = $text;
        }
    }

    protected function toFloat(string $number): float
    {
        return floatval(str_replace(',', '.', trim($number)));
    }

    protected function toTime(string $time): string
    {
        if (strlen($time) < 5) {
            $time = explode(':', $time);

            return sprintf('%02d:%02d', $time[0], $time[1]);
        }

        return $time;
    }

    private function getIndex(string $type): array
    {
        return $this->index[
            $this->locales_in_index[$this->locale][$type]
        ];
    }

    protected function getBasalRates(): array
    {
        $basal_rates = [];
        $basal_rate_indexes = array_reverse($this->getIndex('basal_rates'));
        foreach ($basal_rate_indexes as $basal_rate_index => $content_index) {

            $lines = $this->parseTable($content_index - 2, 29, 3);

            foreach ($lines as $line) {
                if ($line[0] === '<>' && $line[1] === '<>' && $line[2] === '<>') {
                    continue;
                }

                if ($line[0] === '--' && $line[1] === '--' && $line[2] === '<>') {
                    continue;
                }

                $basal_rates[$basal_rate_index][$this->toTime($line[0])] = $this->toFloat($line[1]);
            }
        }

        return $basal_rates;
    }

    protected function getCorrectionFactor(): array
    {
        $correction_factor_index = $this->getIndex('correction_factor')[0];

        $lines = $this->parseTable($correction_factor_index - 2, 8, 3);

        $correction_factor = [];
        foreach ($lines as $line) {
            if ($line[0] === '<>' && $line[1] === '<>' && $line[2] === '<>') {
                break;
            }

            $correction_factor[$this->toTime($line[0])] = $this->toFloat(trim($line[1]));
        }

        return $correction_factor;
    }

    protected function getTargetBloodSugar(): array
    {
        $correction_factor_index = $this->getIndex('target_blood_sugar')[0] - 1;

        $lines = $this->parseTable($correction_factor_index, 8, 4);

        $target_blood_sugar = [];
        foreach ($lines as $line) {
            if ($line[0] === '<>' && $line[1] === '<>' && $line[2] === '<>' && $line[3] === '<>') {
                break;
            }

            $target_blood_sugar[$this->toTime($line[0])] = [
                $this->toFloat($line[1]), $this->toFloat($line[2])];
        }

        return $target_blood_sugar;
    }

    protected function getCarbRatioFactor(): array
    {
        $carb_ratio_factor_index = $this->getIndex('carb_ratio_factor')[0] - 2;

        $lines = $this->parseTable($carb_ratio_factor_index, 8, 3);

        $carb_ratio_factor = [];

        foreach ($lines as $line) {
            if ($line[0] === '<>' && $line[1] === '<>' && $line[2] === '<>') {
                break;
            }

            $carb_ratio_factor[$this->toTime($line[0])] = $this->toFloat($line[1]);
        }

        return $carb_ratio_factor;
    }

    protected function getMinutesOfInsulinAction(): int
    {
        $insulin_action_index = $this->getIndex('insulin_action_in_minutes')[0];

        $line = $this->parseTable($insulin_action_index, 1, 2)[0];

        if ($seperator = strpos($line[1], ':') > 0) {
            $hours = intval(substr($line[1], 0, $seperator));
            $minutes = intval(substr($line[1], $seperator + 1)) + ($hours * 60);
        } else {
            $minutes = intval($line[1]);
        }

        return $minutes;
    }

    public function parse(): array
    {
        $this->parseFile($this->filepath);

        return [
            'basal_rates' => $this->getBasalRates(),
            'correction_factor' => $this->getCorrectionFactor(),
            'target_blood_sugar' => $this->getTargetBloodSugar(),
            'carb_ratio_factor' => $this->getCarbRatioFactor(),
            'insulin_action_in_minutes' => $this->getMinutesOfInsulinAction(),
        ];
    }
}
