<?php


/**
 * Общи помощни функции за JSON-LD доставчиците
 *
 * @category  bgerp
 * @package   jsonld
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class jsonld_Helper
{
    /**
     * Преобразува публична скаларна стойност в чист текст
     *
     * @param mixed $value
     *
     * @return string|null
     */
    public static function getCleanText($value)
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = html_entity_decode(
            strip_tags((string) $value),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
        $value = str_replace("\xc2\xa0", ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value);
        $value = trim($value);

        return $value !== '' ? $value : null;
    }


    /**
     * Връща абсолютния URL на публична снимка или null, ако файлът не е годен
     *
     * @param string|null $fh
     *
     * @return string|null
     */
    public static function getImageUrl($fh)
    {
        if (empty($fh) || !fileman_Files::isImage($fh)) {
            return null;
        }

        $path = fileman::fetchByFh($fh, 'path');

        if (!$path || !file_exists($path)) {
            return null;
        }

        $image = new thumb_Img(array(
            $fh,
            1600,
            1200,
            'fileman',
            'isAbsolute' => true,
            'mode' => 'small-no-change',
        ));

        return $image->getUrl('forced');
    }
}
