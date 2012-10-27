<?php
namespace ZendGateway\Service;

class XMLUtils
{

    const OPTION_ATTR_NAME = 'name';

    const OPTION_ATTR_VALUE = 'value';

    public static function getOptions ($collectionName, $input, 
            $asOptionsKey = true)
    {
        $result = array();
        $data = array();
        foreach ($input->$collectionName as $p) {
            $options = array();
            foreach ($p->option as $o) {
                $options[(string) $o[XMLUtils::OPTION_ATTR_NAME]] = (string) $o[XMLUtils::OPTION_ATTR_VALUE];
            }
            if ($asOptionsKey) {
                $data[XMLUtils::OPTION_ATTR_NAME] = (string) $p[XMLUtils::OPTION_ATTR_NAME];
                $data['options'] = $options;
                array_push($result, $data);
            } else {
                $result[(string) $p[XMLUtils::OPTION_ATTR_NAME]] = $options;
            }
        }
        return $result;
    }
    
    // public static function elementToArray ($input)
    // {
    // $result = array();
    // $name = (string) $input[XMLUtils::OPTION_ATTR_NAME];
    // $array = (array) $input;
    // foreach ($array as $key => $value) {
    // // complex xml element
    // if ($value instanceof \SimpleXMLIterator) {
    // $data = array();
    // $attributes = $value->attributes();
    // foreach ($attributes as $attributeName => $attributeValue) {
    // $data[$attributeName] = trim((string) $attributeValue);
    // }
    
    // $options = array();
    // foreach ($value->option as $o) {
    // $options[(string) $o[XMLUtils::OPTION_ATTR_NAME]] = (string)
    // $o[XMLUtils::OPTION_ATTR_VALUE];
    // }
    // $data['options'] = $options;
    // $array[$key] = $data;
    // }
    // }
    // $result[$name] = $array;
    // return $result;
    // }
    public static function elementToArray ($input)
    {
        if ($input instanceof \SimpleXMLIterator) {
            $array = (array) $input;
            $attributes = $input->attributes();
            foreach ($attributes as $attributeName => $attributeValue) {
                $array[$attributeName] = trim((string) $attributeValue);
            }
            
            foreach ($array as $key => $value) {
                if ($key == 'option') {
                    foreach ($value as $o) {
                        $array[(string) $o[XMLUtils::OPTION_ATTR_NAME]] = (string) $o[XMLUtils::OPTION_ATTR_VALUE];
                    }
                } else 
                    if (is_array($value)) {
                        foreach ($value as $idx => $val) {
                            $array[$key][$idx] = XMLUtils::elementToArray($val);
                        }
                    } else 
                        if ($value instanceof \SimpleXMLIterator) {
                            $data = array();
                            $attributes = $value->attributes();
                            foreach ($attributes as $attributeName => $attributeValue) {
                                $data[$attributeName] = trim(
                                        (string) $attributeValue);
                            }
                            if (isset($value->option)) {
                                $options = array();
                                foreach ($value->option as $o) {
                                    $options[(string) $o[XMLUtils::OPTION_ATTR_NAME]] = (string) $o[XMLUtils::OPTION_ATTR_VALUE];
                                }
                                $data['options'] = $options;
                                $array[$key] = $data;
                            } else
                                $array[$key] = XMLUtils::elementToArray($value);
                        }
            }
            return $array;
        } else {
            return $input;
        }
    }

    public static function toArray (\SimpleXMLElement $xml)
    {
        $array = (array) $xml;
        
        foreach (array_slice($array, 0) as $key => $value) {
            if ($value instanceof \SimpleXMLElement) {
                $array[$key] = empty($value) ? NULL : XMLUtils::toArray($value);
            }
        }
        return $array;
    }
}

?>