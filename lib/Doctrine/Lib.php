<?php

class Doctrine_Lib
{
    // Code from symfony sfToolkit class. See LICENSE
    // code from php at moechofe dot com (array_merge comment on php.net)
    /**
     * arrayDeepMerge
     *
     * array arrayDeepMerge ( array array1 [, array array2 [, array ...]] )
     *
     * Like array_merge
     *
     *  arrayDeepMerge() merges the elements of one or more arrays together so
     * that the values of one are appended to the end of the previous one. It
     * returns the resulting array.
     *  If the input arrays have the same string keys, then the later value for
     * that key will overwrite the previous one. If, however, the arrays contain
     * numeric keys, the later value will not overwrite the original value, but
     * will be appended.
     *  If only one array is given and the array is numerically indexed, the keys
     * get reindexed in a continuous way.
     *
     * Different from array_merge
     *  If string keys have arrays for values, these arrays will merge recursively.
     */
    public static function arrayDeepMerge(array $first, ?array $second = null, array ...$rest): array
    {
        if ($second === null) {
            return $first;
        }

        if (empty($rest)) {
            foreach (array_unique(array_merge(array_keys($first), array_keys($second))) as $key) {
                $isKey0 = array_key_exists($key, $first);
                $isKey1 = array_key_exists($key, $second);

                if ($isKey0 && $isKey1 && is_array($first[$key]) && is_array($second[$key])) {
                    $rest[$key] = self::arrayDeepMerge($first[$key], $second[$key]);
                } elseif ($isKey0 && $isKey1) {
                    $rest[$key] = $second[$key];
                } elseif (!$isKey1) {
                    $rest[$key] = $first[$key];
                } else {
                    $rest[$key] = $second[$key];
                }
            }
            return $rest;
        }

        $first = self::arrayDeepMerge($first, $second);
        return self::arrayDeepMerge($first, ...$rest);
    }

    /**
     * Makes the directories for a path recursively.
     *
     * This method creates a given path issuing mkdir commands for all folders
     * that do not exist yet. Equivalent to 'mkdir -p'.
     *
     * @param  string  $path
     * @param  integer $mode an integer (octal) chmod parameter for the
     *                       created directories
     * @return boolean  true if succeeded
     */
    public static function makeDirectories(string $path, $mode = 0777): bool
    {
        if (!$path) {
            return false;
        }

        if (is_dir($path) || is_file($path)) {
            return true;
        }

        return mkdir(trim($path), $mode, true);
    }

    /**
     * Removes a non empty directory.
     *
     * This method recursively removes a directory and all its descendants.
     * Equivalent to 'rm -rf'.
     *
     * @param  string $folderPath
     * @return boolean  success of the operation
     */
    public static function removeDirectories(string $folderPath): bool
    {
        if (is_dir($folderPath)) {
            foreach (scandir($folderPath) ?: [] as $value) {
                if ($value != '.' && $value != '..') {
                    $value = $folderPath . '/' . $value;

                    if (is_dir($value)) {
                        self::removeDirectories($value);
                    } elseif (is_file($value)) {
                        unlink($value);
                    }
                }
            }

            return rmdir($folderPath);
        } else {
            return false;
        }
    }

    /**
     * Copy all directory content in another one.
     *
     * This method recursively copies all $source files and subdirs in $dest.
     * If $source is a file, only it will be copied in $dest.
     *
     * @param  string $source a directory path
     * @param  string $dest   a directory path
     * @return bool
     */
    public static function copyDirectory(string $source, string $dest): bool
    {
        // Simple copy for a file
        if (is_file($source)) {
            return copy($source, $dest);
        }

        // Make destination directory
        if (!is_dir($dest)) {
            mkdir($dest);
        }

        // Loop through the folder
        $dir = dir($source);
        if (!$dir) {
            return true;
        }

        while ($entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Deep copy directories
            if ($dest !== "$source/$entry") {
                self::copyDirectory("$source/$entry", "$dest/$entry");
            }
        }

        // Clean up
        $dir->close();

        return true;
    }

    /**
     * Checks for a valid class name for Doctrine coding standards.
     *
     * This methods tests if $className is a valid class name for php syntax
     * and for Doctrine coding standards. $className must use camel case naming
     * and underscores for directory separation.
     *
     * @param  string $className
     * @return boolean
     */
    public static function isValidClassName($className)
    {
        if (preg_match('~(^[a-z])|(_[a-z])|([\W])|(_{2})~', $className)) {
            return false;
        }

        return true;
    }
}
