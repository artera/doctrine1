<?php

namespace Doctrine1;

/**
 * Doctrine inflector has static methods for inflecting text
 *
 * The methods in these classes are from several different sources collected
 * across several different php projects and several different authors. The
 * original author names and emails are not known
 */
abstract class Inflector
{
    /**
     * Convert word in to the format for a Doctrine table name. Converts 'ModelName' to 'model_name'
     *
     * @param  string $word Word to tableize
     * @return string $word  Tableized word
     */
    public static function tableize($word)
    {
        $tableized = preg_replace('~(?<=\\w)([A-Z])~u', '_$1', $word);

        if ($tableized === null) {
            throw new \RuntimeException(sprintf(
                'preg_replace returned null for value "%s"',
                $word
            ));
        }

        return mb_strtolower($tableized);
    }

    /**
     * Convert a word in to the format for a Doctrine class name. Converts 'table_name' to 'TableName'
     *
     * @param  string $word Word to classify
     * @return string $word  Classified word
     */
    public static function classify($word)
    {
        static $cache = [];

        if (!isset($cache[$word])) {
            $word         = str_replace('$', '', $word);
            $classify     = preg_replace_callback('~(_?)(?:[-_ ]+)([\w])~u', fn ($m) => $m[1] . mb_strtoupper($m[2]), ucfirst($word));
            $cache[$word] = $classify;
        }
        return $cache[$word];
    }
}
