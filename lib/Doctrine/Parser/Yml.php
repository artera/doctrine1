<?php

use Symfony\Component\Yaml\Yaml;

class Doctrine_Parser_Yml extends Doctrine_Parser
{
    public function dumpData(array $array, ?string $path = null, ?string $charset = null): int|string|null
    {
        try {
            $data = Yaml::dump($array, 6);

            return $this->doDump($data, $path);
        } catch (Throwable $e) {
            // rethrow the exceptions
            $rethrowed_exception = new Doctrine_Parser_Exception($e->getMessage(), $e->getCode());

            throw $rethrowed_exception;
        }
    }

    public function loadData(string $path): array
    {
        try {
            /*
             * I still use the doLoad method even if Yaml can load yml from a file
             * since this way Doctrine can handle file on it own.
             */
            $contents = $this->doLoad($path);

            $array = Yaml::parse($contents);

            return $array;
        } catch (Throwable $e) {
            // rethrow the exceptions
            $rethrowed_exception = new Doctrine_Parser_Exception($e->getMessage(), $e->getCode());

            throw $rethrowed_exception;
        }
    }
}
