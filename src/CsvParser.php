<?php

namespace Ccharz\MedtronicParser;

use Closure;
use DateTime;
use DateTimeZone;
use SplFileObject;

class CsvParser
{
    public function __construct(protected readonly string $filepath, protected readonly string $timezone) {}

    public function parse(Closure $callback): void
    {
        $file = new SplFileObject($this->filepath);
        $file->setFlags(SplFileObject::DROP_NEW_LINE);

        $floatval_keys = [
            'Basal Rate (U/h)' => true,
            'Bolus Volume Selected (U)' => true,
            'Bolus Volume Delivered (U)' => true,
            'ISIG Value' => true,
            'Final Bolus Estimate' => true,
            'BWZ Unabsorbed Insulin Total (U)' => true,
            'BWZ Estimate (U)' => true,
            'BWZ Carb Ratio (U/Ex)' => true,
            'BWZ Carb Input (exchanges)' => true,
            'BWZ Carb Input (grams)' => true,
            'BWZ Carb Ratio (g/U)' => true,
            'BWZ Correction Estimate (U)' => true,
            'BWZ Food Estimate (U)' => true,
            'BWZ Active Insulin (U)' => true,
            'Prime Volume Delivered (U)' => true,
        ];

        $intval_keys = [
            'Bolus Number' => true,
            'Sensor Glucose (mg/dL)' => true,
            'BG Reading (mg/dL)' => true,
            'BWZ Target High BG (mg/dL)' => true,
            'BWZ Target Low BG (mg/dL)' => true,
            'BWZ Insulin Sensitivity (mg/dL/U)' => true,
            'BWZ BG/SG Input (mg/dL)' => true,
        ];

        $header = null;
        $data = null;
        $data_type = null;
        while (! $file->eof()) {
            $line = ($file->fgets());

            if (empty($line)) {
                $header = null;
                $data = null;

                continue;
            }

            if (! $data) {
                if (substr($line, 0, 7) !== '-------') {
                    continue;
                }

                $data = $line;
                $data_type = match (true) {
                    strpos($line, 'Aggregated Auto Insulin Data') !== false => 'auto',
                    strpos($line, 'Pump;') !== false => 'pump',
                    strpos($line, 'Sensor;') !== false => 'sensor',
                    default => exit($data)
                };

                continue;
            }

            $line = explode(';', $line);

            if (! $header) {
                $header = $line;

                continue;
            }

            $original_value = array_combine(
                $header,
                $line
            );

            $values = array_filter($original_value);

            if (! isset($values['Date']) || ! isset($values['Time']) || ! isset($values['Index'])) {
                throw new \Exception('Invalid line');
            }

            unset($values['Index']);

            if (isset($values['Date']) && isset($values['Time'])) {
                if (count($values) === 2) {
                    continue;
                }

                $values['datetime'] = new DateTime($values['Date'] . ' ' . $values['Time'], new DateTimeZone($this->timezone));

                unset($values['Date']);
                unset($values['Time']);
            }

            foreach ($values as $key => $value) {
                if (isset($floatval_keys[$key])) {
                    $values[$key] = floatval(str_replace(',', '.', $value));
                } elseif (isset($intval_keys[$key])) {
                    $values[$key] = intval($value);
                }
            }

            call_user_func_array(
                $callback,
                [
                    $data_type,
                    $values,
                    $data,
                    $original_value,
                ]
            );
        }
    }
}
