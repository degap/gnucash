<?php

namespace unit;

use Codeception\Test\Unit;
use Degap\Gnucash\Core\DataParser;
use Degap\Gnucash\Xml\XmlLoader;
use UnitTester;

class ParseXmlTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testParseXml(): void
    {
        $xmlParser = new XmlLoader();
        $extractedPath = $xmlParser->unpack(__DIR__ . '/../_data/simple.gnucash');
        $result = $xmlParser->parse($extractedPath);
        $dataParser = new DataParser();
        $books = $dataParser->parseBooks($result);
        verify($books)->notEmpty();
        verify($books)->arrayCount(1);
        $book = $books[0];
        verify($book)->notEmpty();
        verify($book)->arrayHasKey('id');
        verify($book)->arrayHasKey('counts');
        verify($book)->arrayHasKey('commodities');
        verify($book)->arrayHasKey('accounts');
        verify($book)->arrayHasKey('transactions');
        verify($book['commodities'])->arrayCount($book['counts']['commodity']);
        verify($book['accounts'])->arrayCount($book['counts']['account']);
        verify($book['transactions'])->arrayCount($book['counts']['transaction']);
    }
}
