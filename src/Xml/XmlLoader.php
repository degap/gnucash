<?php

namespace Degap\Gnucash\Xml;


use Degap\Gnucash\File\Unzip;

class XmlLoader
{
    public function unpack(string $filePath): string
    {
        $unzip = new Unzip($filePath);
        return $unzip->extract();
    }

    public function parse(string $filePath): array
    {
        $xmlParser = new XmlParser($filePath);
        return $xmlParser->getData();
    }
}
