# Degap/Gnucash

[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.txt)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.1-8892BF.svg?style=flat-square)](https://php.net/)

Gnucash file parser

## Usage

```php
$xmlParser = new \Degap\Gnucash\XmlLoader();
$dataParser = new \Degap\Gnucash\DataParser();

$extractedPath = $xmlParser->unpack('/path_to/my.gnucash'); // unpack to /path_to/my.xml
$books = $dataParser->parseBooks($xmlParser->parse($extractedPath));

unlink($extractedPath);
```
