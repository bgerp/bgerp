<?php


/**
 * Драйвър за експортиране на документи в XLS формат (Експортира първо в CSV после го конвертира)
 *
 * Класа трябва да има $exportableCsvFields за да може да се експортират данни от него в XLS формат
 *
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_plg_XlsExport extends bgerp_plg_CsvExport
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'bgerp_ExportIntf';


    /**
     * Заглавие
     */
    public $title = 'Експортиране в XLS';


    /**
     * Дали експорта връща file handler
     */
    public $exportIsFh = true;


    /**
     * Връща името на експортирания файл
     *
     * @return string $name
     */
    public function getExportedFileName()
    {
        $timestamp = time();
        $name = $this->mvc->className . "Csv{$timestamp}.xls";

        return $name;
    }


    /**
     * Импортиране на csv-файл в даден мениджър
     *
     * @param mixed $data - данни
     *
     * @return mixed - експортираните данни
     */
    public function export($filter)
    {
        $content = parent::export($filter);

        $name = parent::getExportedFileName();
        $fh = fileman::absorbStr($content, 'exportCsv', $name);

        $fRec = fileman::fetchByFh($fh);
        $fPath = fileman_webdrv_Office::convertToFile($fRec, 'xls', false, 'export_Xls::afterConvertToXls', 'xls');

        if ($fPath && is_file($fPath)) {
            $nFileHnd = fileman::absorb($fPath, 'exportFiles');
            core_Os::deleteDir(dirname($fPath));

            return $nFileHnd;
        }

        return null;
    }
}