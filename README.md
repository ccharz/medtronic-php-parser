# Medtronic PHP Parser

With this library you can parse data from export files available in the medtronic carelink center

### Requirements

- **[PHP 8.3+](https://php.net/releases/)**

### Installation

Use [Composer](https://getcomposer.org) to install this library into your project:

```bash
composer require "ccharz/medtronic-php-parser"
```

## Basal Rates, Carb Ratio factors, Target Blood Sugar and Correction Factors

> **Important:** Currently only exports in **german** with units in **mg/dl** are supported

### Usage

```php
use Ccharz\MedtronicParser\ProfileParser;

$filepath = 'path/to/your/medtronic-profile.pdf';

$result = (new ProfileParser($filepath, 'de', 'Europe/Vienna'))->parse();

var_dump($result);
```

## CSV Data

### Usage

To get all data from the csv use the following code

```php
use Ccharz\MedtronicParser\ProfileParser;
$filepath = 'path/to/your/medtronic-export.csv';

$importer = new CsvParser($filepath, 'Europe/Vienna');

$lines = [];

$importer->parse(function (
    string $type,
    array $values
) use (&$lines) {
    $lines[$type][] = $values;
});

var_dump($lines);
```

## Testing

For privacy reasons the test data is not included in the repository

## License

This library is an open-sourced software licensed under the [MIT license](LICENSE).
