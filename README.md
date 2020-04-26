# covid

Tool that fetches official covid data, parses them and generates a prepared output. 

[![PHP Composer](https://github.com/malcolmamal/covid/workflows/PHP%20Composer/badge.svg)](https://github.com/malcolmamal/covid/actions) [![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)


## Requirements

- PHP ^7.1

## Download

Go to the [releases page](https://github.com/malcolmamal/covid/releases) and download the latest version.

## Installation

```bash
composer install
```

## Usage

```bash
php index.php download
php index.php generate
```

or

```bash
php index.php generate --download
```

You can also provide options for generation

```bash
php index.php generate --mode main|all|test
```

To include charts in the generated output

```bash
php index.php generate --with-charts
```

To change the rolling average

```bash
php index.php generate --avg week|fortnight
```

