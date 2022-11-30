<?php

namespace Doctrine1\Cli;

class AnsiColorFormatter extends \Doctrine1\Cli\Formatter
{
    /**
     * @var array
     */
    protected $styles = [
            'HEADER'  => ['fg' => 'black', 'bold' => true],
            'ERROR'   => ['bg' => 'red', 'fg' => 'white', 'bold' => true],
            'INFO'    => ['fg' => 'green', 'bold' => true],
            'COMMENT' => ['fg' => 'yellow'],
        ];

    /**
     * @var array
     */
    protected $options = ['bold' => 1, 'underscore' => 4, 'blink' => 5, 'reverse' => 7, 'conceal' => 8];

    /**
     * @var array
     */
    protected $foreground = ['black' => 30, 'red' => 31, 'green' => 32, 'yellow' => 33, 'blue' => 34, 'magenta' => 35, 'cyan' => 36, 'white' => 37];

    /**
     * @var array
     */
    protected $background = ['black' => 40, 'red' => 41, 'green' => 42, 'yellow' => 43, 'blue' => 44, 'magenta' => 45, 'cyan' => 46, 'white' => 47];

    /**
     * Sets a new style.
     *
     * @param string $name    The style name
     * @param array  $options An array of options
     *
     * @return void
     */
    public function setStyle($name, $options = [])
    {
        $this->styles[$name] = $options;
    }

    /**
     * Formats a text according to the given style or parameters.
     *
     * @param string   $text       The test to style
     * @param mixed    $parameters An array of options or a style name
     * @param resource $stream
     *
     * @return string The styled text
     */
    public function format($text = '', $parameters = [], $stream = STDOUT)
    {
        if (!$this->supportsColors($stream)) {
            return $text;
        }

        if (!is_array($parameters) && 'NONE' == $parameters) {
            return $text;
        }

        if (!is_array($parameters) && isset($this->styles[$parameters])) {
            $parameters = $this->styles[$parameters];
        }

        $codes = [];
        if (isset($parameters['fg'])) {
            $codes[] = $this->foreground[$parameters['fg']];
        }

        if (isset($parameters['bg'])) {
            $codes[] = $this->background[$parameters['bg']];
        }

        foreach ($this->options as $option => $value) {
            if (isset($parameters[$option]) && $parameters[$option]) {
                $codes[] = $value;
            }
        }

        return "\033[" . implode(';', $codes) . 'm' . $text . "\033[0m";
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
        $width = 9 + strlen($this->format('', 'INFO'));

        return sprintf(">> %-${width}s %s", $this->format($section, 'INFO'), $this->excerpt($text, $size));
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

        return substr($text, 0, $subsize) . $this->format('...', 'INFO') . substr($text, -$subsize);
    }

    /**
     * Returns true if the stream supports colorization.
     *
     * Colorization is disabled if not supported by the stream:
     *
     *  -  windows
     *  -  non tty consoles
     *
     * @param mixed $stream A stream
     *
     * @return bool true if the stream supports colorization, false otherwise
     */
    public function supportsColors($stream)
    {
        return DIRECTORY_SEPARATOR != '\\' && function_exists('posix_isatty') && @posix_isatty($stream);
    }
}
