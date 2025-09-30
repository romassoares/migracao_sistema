<?php
include_once __DIR__ . '/../core/functions.php';
class XmlToArrayService
{
    private $debug = false;

    public function convert($filePath)
    {
        $xml = simplexml_load_file($filePath);
        $children = [];

        foreach ($xml as $tag) {
            $record = [];
            $this->trata_objeto($tag, $record);
            $children[] = $record;
        }
        $caminhos_absolutos = [];
        monta_caminhos_absolutos_arquivo($children, $caminhos_absolutos);
        // var_dump($children);
        // die;
        $retorno = array_unique($caminhos_absolutos);

        return [$retorno, $children];
    }

    public function trata_objeto(SimpleXMLElement $element, array &$record)
    {
        foreach ($element->children() as $prop => $child) {
            $attributes = [];

            foreach ($child->attributes() as $attrKey => $attrValue) {
                $attributes[$attrKey] = (string) $attrValue;
            }

            if ($child->count() > 0) {
                $childRecord = [];

                $this->trata_objeto($child, $childRecord);

                if (!empty($attributes)) {
                    $childRecord = array_merge(['@attributes' => $attributes], $childRecord);
                }

                if (isset($record[$prop])) {
                    if (!is_array($record[$prop]) || !isset($record[$prop][0])) {
                        $record[$prop] = [$record[$prop]];
                    }
                    $record[$prop][] = $childRecord;
                } else {
                    $record[$prop] = $childRecord;
                }
            } else {
                // $value = (string) $child;

                // if (!empty($attributes)) {
                //     $record[$prop] = ['@attributes' => $attributes, '@value' => $value];
                // } else {
                //     $record[$prop] = $value;
                // }
                $value = (string) $child;
                if (!empty($attributes)) {
                    $entry = ['@attributes' => $attributes, '@value' => $value];
                } else {
                    $entry = $value;
                }

                if (isset($record[$prop])) {
                    // Se já existe, transforma em array caso ainda não seja
                    if (!is_array($record[$prop]) || !isset($record[$prop][0])) {
                        $record[$prop] = [$record[$prop]];
                    }
                    $record[$prop][] = $entry;
                } else {
                    $record[$prop] = $entry;
                }
            }
        }
    }
}
