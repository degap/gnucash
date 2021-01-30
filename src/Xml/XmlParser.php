<?php


namespace Degap\Gnucash\Xml;


use Degap\Gnucash\Exception\FileNotFoundException;
use XMLReader;

class XmlParser
{
    /**
     * @var string
     */
    private $pathToXml;

    /**
     * Parser constructor.
     *
     * @param $pathToXml
     */
    public function __construct($pathToXml)
    {
        $this->pathToXml = $pathToXml;
        if (!file_exists($this->pathToXml)) {
            throw new FileNotFoundException('File not found: ' . $this->pathToXml);
        }
    }

    public function getData(): array
    {
        /** @var XMLReader $reader */
        $reader = XMLReader::open($this->pathToXml);
        $xmlData = $this->parseAll($reader);
        $reader->close();
        return $xmlData;
    }

    private function parseAll(XMLReader $reader): array
    {
        $xmlData = [];
        while ($reader->read()) {
            $name = $reader->localName;
            $type = $reader->nodeType;
            if ($type === XMLReader::ELEMENT) {
                $xmlData[$name] = $this->readItem($reader, $name);
            }
        }
        return $xmlData;
    }

    /**
     * @param XMLReader $reader
     * @param string $itemName
     * @param array $attributes
     * @return mixed
     */
    private function readItem(XMLReader $reader, string $itemName, $attributes = [])
    {
        $xmlData = null;
        while ($reader->read()) {
            $name = $reader->localName;
            $type = $reader->nodeType;
            $isEmpty = $reader->isEmptyElement;
            if ($type === XMLReader::SIGNIFICANT_WHITESPACE) {
                continue;
            }
            if ($isEmpty) {
                $xmlData[$name] = '';
                continue;
            }
            if ($type === XMLReader::ELEMENT) {
                $attributes = [];
                if ($attributeCount = $reader->attributeCount) {
                    for ($index = 0; $index <= $attributeCount; $index++) {
                        $reader->moveToAttributeNo($index);
                        if ($value = $reader->value) {
                            $attributes[$reader->localName] = $value;
                        }
                    }
                }
                if (isset($xmlData[$name])) {
                    if (is_array($xmlData[$name])) {
                        $key = key($xmlData[$name]);
                        if (!is_int($key)) {
                            $elem = $xmlData[$name];
                            $xmlData[$name] = [];
                            $xmlData[$name][] = $elem;
                        }
                        $xmlData[$name][] = $this->readItem($reader, $name, $attributes);
                    } else {
                        $elem = $xmlData[$name];
                        $xmlData[$name] = [];
                        $xmlData[$name][] = $elem;
                        $xmlData[$name][] = $this->readItem($reader, $name, $attributes);
                    }
                } else {
                    $xmlData[$name] = $this->readItem($reader, $name, $attributes);
                }
            }

            if ($type === XMLReader::TEXT) {
                if (!empty($attributes)) {
                    $xmlData = $attributes;
                    $xmlData['value'] = $reader->readString();
                } else {
                    $xmlData = $reader->readString();
                }

            }
            if ($type === XMLReader::END_ELEMENT && $name === $itemName) {
                break;
            }
        }
        return $xmlData;
    }

    public function clean()
    {
        if (file_exists($this->pathToXml)) {
            unlink($this->pathToXml);
        }
    }
}
