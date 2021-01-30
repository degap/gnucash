<?php


namespace Degap\Gnucash\File;


use Degap\Gnucash\Exception\EmptyArgumentException;
use Degap\Gnucash\Exception\FileNotExistedException;
use Degap\Gnucash\Exception\ZipException;

class Unzip
{
    private string $zipFilePath;

    /**
     * Unzip constructor.
     *
     * @param $zipFilePath
     */
    public function __construct($zipFilePath)
    {
        $this->zipFilePath = $zipFilePath ?? realpath($zipFilePath);
    }

    public function extract(): string
    {
        $this->validFile();
        $extractedFilePath = $this->getXmlPath();

        try {
            $result = copy('compress.zlib://' . $this->zipFilePath, $extractedFilePath);
        } catch (\Throwable $e) {
            throw new ZipException($e->getMessage());
        }
        if (!$result) {
            throw new ZipException(sprintf('Error unzip file %s', $this->zipFilePath));
        }
        return $extractedFilePath;
    }

    private function validFile(): void
    {
        if (empty($this->zipFilePath)) {
            $msg = sprintf('Argument zipFilePath is empty');
            throw new EmptyArgumentException($msg);
        }
        if (!file_exists($this->zipFilePath)) {
            $msg = sprintf('File %s not existed', $this->zipFilePath);
            throw new FileNotExistedException($msg);
        }
    }

    public function getXmlPath(): string
    {
        return $this->zipFilePath . '.xml';
    }
}
