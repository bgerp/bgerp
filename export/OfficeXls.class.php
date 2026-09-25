<?php


/**
 * Съществуващият офис конвертор като драйвър за XLS експорт
 *
 * @category  bgerp
 * @package   export
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class export_OfficeXls extends core_BaseClass
{
    public $interfaces = 'export_XlsConverterIntf';


    public $title = 'Офис конвертор (docoffice)';


    public static function isAvailable()
    {
        return true;
    }


    public static function convertToXls($fileHnd, $csvData = null)
    {
        $fRec = fileman::fetchByFh($fileHnd);
        $fPath = fileman_webdrv_Office::convertToFile($fRec, 'xls', false, 'export_Xls::afterConvertToXls', 'xls');
        if ($fPath && is_file($fPath)) {
            try {
                return fileman::absorb($fPath, 'exportFiles');
            } finally {
                core_Os::deleteDir(dirname($fPath));
            }
        }

        return null;
    }
}
