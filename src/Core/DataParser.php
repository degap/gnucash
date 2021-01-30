<?php

namespace Degap\Gnucash\Core;

class DataParser
{
    private const ACCOUNT          = 'account';
    private const BOOK             = 'book';
    private const COMMODITY        = 'commodity';
    private const COMMODITY_SCU    = 'commodity-scu';
    private const COUNT_DATA       = 'count-data';
    private const CURRENCY         = 'currency';
    private const DATE_ENTERED     = 'date-entered';
    private const DATE_POSTED      = 'date-posted';
    private const DESCRIPTION      = 'description';
    private const GNC_V2           = 'gnc-v2';
    private const GUID             = 'guid';
    private const ID               = 'id';
    private const NAME             = 'name';
    private const PARENT           = 'parent';
    private const QUANTITY         = 'quantity';
    private const RECONCILED_STATE = 'reconciled-state';
    private const SPACE            = 'space';
    private const SPLIT            = 'split';
    private const SPLITS           = 'splits';
    private const TRANSACTION      = 'transaction';
    private const TYPE             = 'type';
    private const VALUE            = 'value';

    private const PARSED_ACCOUNT     = 'accounts';
    private const PARSED_COMMODITY   = 'commodities';
    private const PARSED_COUNT       = 'counts';
    private const PARSED_TRANSACTION = 'transactions';

    private array $book = [];

    public function parseBooks(array $data): array
    {
        $bookCount = (int)($data[self::GNC_V2][self::COUNT_DATA][self::VALUE] ?? 0);
        $books = [];
        if ($bookCount === 1) {
            $this->book = $data[self::GNC_V2][self::BOOK];
            $books[] = $this->parseBook();
        } else {
            for ($i = 0; $i < $bookCount; $i++) {
                $this->book = $data[self::GNC_V2][self::BOOK][$i];
                $books[] = $this->parseBook();
            }
        }
        return $books;
    }

    private function parseBook(): array
    {
        $bookParsed = [];
        $this->parseGuid($bookParsed, $this->book);
        $this->parseCountData($bookParsed, $this->book);
        $this->parseCommodity($bookParsed, $this->book);
        $this->parseAccounts($bookParsed, $this->book);
        $this->parseTransactions($bookParsed, $this->book);
        return $bookParsed;
    }

    private function parseTypeValueItem(array &$parsed, array $root, $parsedKey = null): void
    {
        $this->parseItem($parsed, $root, self::TYPE, self::VALUE, $parsedKey);
    }

    private function parseSpaceIdItem(array &$parsed, array $root, $parsedKey = null): void
    {
        $this->parseItem($parsed, $root, self::SPACE, self::ID, $parsedKey);
    }

    private function parseDateItem(array &$parsed, array $root, string $parsedKey): void
    {
        $parsed[$parsedKey] = $root[$parsedKey]['date'];
    }

    private function parseItem(array &$parsed, array $item, $keyName, $valueName, $parsedKey = null): void
    {
        $idType = strtolower($item[$keyName] ?? '');
        $idValue = $item[$valueName] ?? '';
        if ($idType) {
            $parsed[$parsedKey ?: $idType] = is_numeric($idValue) ? (int) $idValue : $idValue;
        }
    }

    private function parseGuid(array &$parsed, array $root, $key = self::ID): void
    {
        $this->parseTypeValueItem($parsed, $root[$key] ?? [], $key);
    }

    private function parseCountData(array &$parsed, array $root): void
    {
        $countData = $root[self::COUNT_DATA] ?? [];
        $parsed[self::PARSED_COUNT] = [];
        foreach ($countData as $countDataItem) {
            $this->parseTypeValueItem($parsed[self::PARSED_COUNT], $countDataItem);
        }
    }

    private function parseCommodity(array &$parsed, array $root): void
    {
        $commodity = $root[self::COMMODITY] ?? [];
        if (!isset($commodity[0])) {
            $commodity = [$commodity];
        }
        $parsed[self::PARSED_COMMODITY] = [];
        foreach ($commodity as $commodityItem) {
            if ($commodityItem[self::ID] === 'template') {
                continue;
            }
            $this->parseSpaceIdItem($parsed[self::PARSED_COMMODITY], $commodityItem);
        }
    }

    private function parseAccounts(array &$parsed, array $root): void
    {
        $accounts = $root[self::ACCOUNT] ?? [];
        $parsed[self::PARSED_ACCOUNT] = [];
        foreach ($accounts as $accountItem) {
            $accountData = [];
            $this->parseGuid($accountData, $accountItem);
            if (isset($accountItem[self::PARENT])) {
                $this->parseGuid($accountData, $accountItem, self::PARENT);
            }
            $this->parseCommodity($accountData, $accountItem);
            $accountData[self::NAME] = $accountItem[self::NAME] ?? '';
            $accountData[self::TYPE] = $accountItem[self::TYPE] ?? '';
            $accountData[self::DESCRIPTION] = $accountItem[self::DESCRIPTION] ?? '';
            $accountData[self::COMMODITY_SCU] = $accountItem[self::COMMODITY_SCU] ?? '';

            $parsed[self::PARSED_ACCOUNT][] = $accountData;
        }
    }

    private function parseTransactions(array &$parsed, array $root): void
    {
        $transactions = $root[self::TRANSACTION] ?? [];
        $parsed[self::PARSED_TRANSACTION] = [];
        foreach ($transactions as $transactionItem) {
            $transactionData = [];
            $this->parseGuid($transactionData, $transactionItem);
            $this->parseSpaceIdItem($transactionData, $transactionItem[self::CURRENCY]);
            $this->parseDateItem($transactionData, $transactionItem, self::DATE_ENTERED);
            $this->parseDateItem($transactionData, $transactionItem, self::DATE_POSTED);
            $transactionData[self::DESCRIPTION] = $transactionItem[self::DESCRIPTION] ?? '';

            $transactionData[self::SPLITS] = [];
            foreach ($transactionItem[self::SPLITS][self::SPLIT] ?? [] as $splitItem) {
                $splitData = [];
                $this->parseSplit($splitData, $splitItem);
                $transactionData[self::SPLITS][] = $splitData;
            }
            $parsed[self::PARSED_TRANSACTION][] = $transactionData;
        }
    }

    private function parseSplit(array &$parsed, array $root): void
    {
        $this->parseGuid($parsed, $root);
        $parsed[self::RECONCILED_STATE] = $root[self::RECONCILED_STATE] ?? '';
        $this->parseAmount($parsed, $root, self::VALUE);
        $this->parseAmount($parsed, $root, self::QUANTITY);
        $this->parseGuid($parsed, $root, self::ACCOUNT);
    }

    private function parseAmount(array &$parsed, array $root, $key): void
    {
        $expression = $root[$key] ?? '';
        $parts = explode('/', $expression);
        $divisible = (int)$parts[0];
        $divisor = (int)$parts[1];
        if ($divisor !== 0) {
            $parsed[$key] = $divisible / $divisor;
        }
    }
}
