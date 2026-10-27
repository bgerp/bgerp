<?php


/**
 * Общи проверки при четене на банков XML.
 *
 * @category bgerp
 * @package payment
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
class payment_XmlParser
{
    public static function load($xml, &$error)
    {
        $error = null;
        $xml = ltrim($xml ?? '', "\xEF\xBB\xBF \t\r\n");
        if (stripos($xml, '<!DOCTYPE') !== false) {
            $error = 'XML извлечението не трябва да съдържа DTD';

            return false;
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $document = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if ($document === false) {
            $error = 'Грешка при парсиране на XML';
        }

        return $document;
    }


    public static function getText($node, $path)
    {
        foreach (explode('/', $path) as $name) {
            if (!isset($node->{$name})) {

                return '';
            }
            $node = $node->{$name};
        }

        return (string) $node;
    }


    public static function isValidAmount($amount)
    {
        return preg_match('/^\+?(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)$/D', $amount ?? '')
            && is_finite((float) $amount) && (float) $amount <= 2000000000;
    }


    public static function isValidDate($date)
    {
        return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $date ?? '', $parts)
            && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
    }
}
