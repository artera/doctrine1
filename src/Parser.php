<?php

namespace Doctrine1;

abstract class Parser
{
    abstract public function loadData(string $path): array;

    abstract public function dumpData(array $array, ?string $path = null, ?string $charset = null): int|string|null;

    /**
     * Get instance of the specified parser
     */
    public static function getParser(string $type): Parser
    {
        /** @phpstan-var class-string<Parser> $class */
        $class = __NAMESPACE__ . '\\Parser\\' . ucfirst($type);
        return new $class;
    }

    /**
     * Interface for loading and parsing data from a file
     *
     * @param  string $path
     * @param  string $type
     * @return array
     * @author Jonathan H. Wage
     */
    public static function load(string $path, string $type = 'xml'): array
    {
        $parser = self::getParser($type);
        return (array) $parser->loadData($path);
    }

    /**
     * Interface for pulling and dumping data to a file
     *
     * @param  array  $array
     * @param  string $path
     * @param  string $type
     * @param  string $charset The charset of the data being dumped
     * @return int|string|null
     * @author Jonathan H. Wage
     */
    public static function dump($array, $type = 'xml', $path = null, $charset = null)
    {
        $parser = self::getParser($type);

        return $parser->dumpData($array, $path, $charset);
    }

    /**
     * Get contents whether it is the path to a file file or a string of txt.
     * Either should allow php code in it.
     */
    public function doLoad(string $path): string
    {
        ob_start();
        if (!file_exists($path)) {
            $contents = $path;
            $path     = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dparser_' . microtime();

            file_put_contents($path, $contents);
        }

        include $path;

        // Fix #1569. Need to check if it's still all valid
        $contents = ob_get_clean() ?: '';

        return $contents;
    }

    public function doDump(string $data, ?string $path = null): int|string|null
    {
        if ($path !== null) {
            $res = file_put_contents($path, $data);
            return $res === false ? null : $res;
        } else {
            return $data;
        }
    }
}
