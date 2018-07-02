<?php



/**
 * Клас 'core_Xml' - Библиотечни функции за работа с XML
 *
 *
 * @category  bgerp
 * @package   core
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_Xml
{
    /**
     * Преобразува SimpleXMLElement в масив
     */
    public static function toArrayFlat($xml, &$return, $path = '', $root = false)
    {
        $children = array();
        
        if ($xml instanceof SimpleXMLElement) {
            $children = $xml->children();
            
            if ($root) { // we're at root
                $path .= '/' . $xml->getName();
            }
        }
        
        if (count($children) == 0) {
            $return[$path] = (string) $xml;
            
            return;
        }
        
        $seen = array();
        
        foreach ($children as $child => $value) {
            $childname = ($child instanceof SimpleXMLElement) ? $child->getName() : $child;
            
            if (!isset($seen[$childname])) {
                $seen[$childname] = 0;
            }
            $seen[$childname]++;
            self::toArrayFlat($value, $return, $path . '/' . $child . '[' . $seen[$childname] . ']');
        }
    }
}
