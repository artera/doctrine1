#!/usr/bin/env php
<?php

$file = fopen("php://stdin", "r");
if (!$file) {
    return;
}

$fields_separator = "; ";
$code = null;
$descr = null;
$sqlstate = null;
$matchformat = "";

echo <<<EOF
<?php

namespace Doctrine1\Connection\Mysql;

enum ErrorCode: int
{

EOF;

while (($line = fgets($file)) !== false) {
    $line = trim($line);
    $fields = explode("; ", $line);

    if (str_starts_with($fields[0], "Error number: ")) {
        if (strpos($fields[0], "MY-") === false) {
            $code = substr($fields[0], 14); // Extracting the error number
            $descr = substr($fields[1], 8);
            $sqlstate = $fields[2] ?? "";
        } else {
            $code = "";
            $descr = "";
        }
    } elseif (str_starts_with($line, "Message: ") && $code && $descr !== "ER_NO" && $descr !== "ER_YES") {
        echo "    case $descr = $code; // $sqlstate; $line\n";
        $message = substr($line, 9);

        if (strpos($message, "%") !== false && $message !== "%s") {
            $message = preg_quote($message);
            $message = preg_replace("/%(?:(?:\\\\\\.)?(?:\\\\\\*)?s|\d{0,2}x)/", "(.*?)", $message) ?? $message;
            $message = preg_replace("/%(?:l?l?[ud]|zu|\d{0,2}d)/", "(\d+?)", $message) ?? $message;
            $message = preg_replace("/%f/", "(\d+?(?:.\d+)?)", $message) ?? $message;
            $message = preg_replace("/%c/", "$1(.)", $message) ?? $message;
            $message = var_export($message, true);
            $matchformat .= "            self::$descr => $message,\n";
        }
    }
}

fclose($file);

$matchformat = trim($matchformat);

echo <<<EOF

    /** @phpstan-return string[]|null */
    public function scanMessage(string \$message): ?array
    {
        \$regex = match (\$this) {
            $matchformat
            default => null,
        };
        if (\$regex === null || !preg_match("/^\$regex\$/", \$message, \$matches)) {
            return null;
        }
        unset(\$matches[0]);
        return array_values(\$matches);
    }
}

EOF;
