<?php

class Doctrine_Parser_Xml extends Doctrine_Parser
{
    public function dumpData(array $array, ?string $path = null, ?string $charset = null): int|string|null
    {
        $data = self::arrayToXml($array, 'data', null, $charset);

        return $this->doDump((string) $data, $path);
    }

    /**
     * @param  array                 $array        Array to convert to xml
     * @param  string                $rootNodeName Name of the root node
     * @param  SimpleXMLElement|null $xml          SimpleXmlElement, if null SimpleXMLElement will be created
     * @return string|null         String of xml built from array
     */
    public static function arrayToXml(array $array, string $rootNodeName = 'data', ?SimpleXMLElement $xml = null, ?string $charset = null): ?string
    {
        if ($xml === null) {
            $xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"utf-8\"?><$rootNodeName/>");
        }

        foreach ($array as $key => $value) {
            $key = preg_replace('/[^a-z]/i', '', $key);

            if (is_array($value) && !empty($value)) {
                $node = $xml->addChild($key);

                foreach ($value as $k => $v) {
                    if (is_numeric($v)) {
                        unset($value[$k]);
                        $node->addAttribute($k, (string) $v);
                    }
                }

                self::arrayToXml($value, $rootNodeName, $node, $charset);
            } elseif (is_int($key)) {
                // $key will never be an int, since preg_replace never returns an int
                $xml->addChild($value, 'true');
            } else {
                $charset = $charset ? $charset : 'utf-8';
                if (strcasecmp($charset, 'utf-8') !== 0 && strcasecmp($charset, 'utf8') !== 0) {
                    $value = iconv($charset, 'UTF-8', $value);
                }
                $value = htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
                $xml->addChild($key, $value);
            }
        }

        $res = $xml->asXML();
        return $res === false ? null : $res;
    }

    public function loadData(string $path): array
    {
        $contents = $this->doLoad($path);

        $simpleXml = simplexml_load_string($contents);

        return $this->prepareData($simpleXml);
    }

    /**
     * prepareData
     *
     * Prepare simple xml to array for return
     *
     * @param  string|SimpleXMLElement $simpleXml
     * @return array  $return
     */
    public function prepareData($simpleXml)
    {
        $children = [];
        $return   = [];

        if ($simpleXml instanceof SimpleXMLElement) {
            $children = $simpleXml->children();
        }

        foreach ($children as $element => $value) {
            if ($value instanceof SimpleXMLElement) {
                $values = (array) $value->children();

                if (count($values) > 0) {
                    $return[$element] = $this->prepareData($value);
                } else {
                    if (!isset($return[$element])) {
                        $return[$element] = (string) $value;
                    } else {
                        if (!is_array($return[$element])) {
                            $return[$element] = [$return[$element], (string) $value];
                        } else {
                            $return[$element][] = (string) $value;
                        }
                    }
                }
            }
        }

        return $return;
    }
}
