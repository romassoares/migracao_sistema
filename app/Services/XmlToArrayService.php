<?php

class XmlToArrayService
{
    public function convert($filePath)
    {
        // $reader = new XMLReader();
        // $reader->open($filePath);

        // $result = [];

        // while ($reader->read()) {
        //     if ($reader->nodeType == XMLReader::ELEMENT) {
        //         $node = $reader->expand();
        //         $dom = new DOMDocument();
        //         $node = $dom->importNode($node, true);
        //         $dom->appendChild($node);

        //         $result[] = simplexml_load_string($dom->saveXML());
        //     }
        // }

        // $reader->close();
        // return $result;


        $xml = simplexml_load_file($filePath);
        $children = [];

        foreach ($xml as $tag) {
            $record = [];
            $this->trata_objeto($tag, $record);
            $children[] = $record;
        }
        return $children;
    }

    public function trata_objeto(SimpleXMLElement $element, array &$record)
    {
        foreach ($element->children() as $prop => $child) {
            $childRecord = [];

            $attributes = [];
            foreach ($child->attributes() as $attrKey => $attrValue) {
                $attributes[$attrKey] = (string) $attrValue;
            }

            if ($child->count() > 0) {
                $this->trata_objeto($child, $childRecord);
                if (!empty($attributes)) {
                    $record[$prop][] = ['@attributes' => $attributes, '@children' => $childRecord];
                } else {
                    $record[$prop][] = $childRecord;
                }
            } else {
                $value = str_replace("'", '', (string) $child);

                if (!empty($attributes)) {
                    $record[$prop][] = ['@attributes' => $attributes, '@value' => $value];
                } else {
                    $record[$prop][] = $value;
                }
            }
        }
    }
}
