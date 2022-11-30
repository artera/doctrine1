<?php

namespace Doctrine1\Cli;

class Formatter
{
    /**
     * @var int
     */
    protected $size = 65;

    /**
     * __construct
     *
     * @param  int $maxLineSize
     * @return void
     */
    public function __construct($maxLineSize = 65)
    {
        $this->size = $maxLineSize;
    }

    /**
     * Formats a text according to the given parameters.
     *
     * @param string       $text       The text to style
     * @param array|string $parameters An array of parameters
     * @param resource     $stream     A stream (default to STDOUT)
     *
     * @return string The formatted text
     */
    public function format($text = '', $parameters = [], $stream = STDOUT)
    {
        return $text;
    }

    /**
     * Formats a message within a section.
     *
     * @param string  $section The section name
     * @param string  $text    The text message
     * @param integer $size    The maximum size allowed for a line (65 by default)
     *
     * @return string
     */
    public function formatSection($section, $text, $size = null)
    {
        return sprintf('>> %-$9s %s', $section, $this->excerpt($text, $size));
    }

    /**
     * Truncates a line.
     *
     * @param string  $text The text
     * @param integer $size The maximum size of the returned string (65 by default)
     *
     * @return string The truncated string
     */
    public function excerpt($text, $size = null)
    {
        if (!$size) {
            $size = $this->size;
        }

        if (strlen($text) < $size) {
            return $text;
        }

        $subsize = (int) floor(($size - 3) / 2);

        return substr($text, 0, $subsize) . '...' . substr($text, -$subsize);
    }

    /**
     * Sets the maximum line size.
     *
     * @param integer $size The maximum line size for a message
     *
     * @return void
     */
    public function setMaxLineSize($size)
    {
        $this->size = $size;
    }
}
