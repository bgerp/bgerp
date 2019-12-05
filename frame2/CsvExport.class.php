<?php


/**
 * Експортиране на справките като csv
 *
 * @category  bgerp
 * @package   frame2
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class frame2_CsvExport extends core_Mvc
{
    /**
     * Заглавие на таблицата
     */
    public $title = 'Експортиране на справка като CSV';
    
    
    /**
     *  Интерфейси
     */
    public $interfaces = 'export_ExportTypeIntf, export_ToXlsExportIntf';
    
    
    /**
     * Импортиране на csv-файл в даден мениджър
     *
     * @param int $clsId
     * @param int $objId
     *
     * @return bool
     */
    public function canUseExport($clsId, $objId)
    {
        $canUse = export_Export::canUseExport($clsId, $objId);
        if (!$canUse) {
            
            return $canUse;
        }
        
        return $clsId == frame2_Reports::getClassId();
    }
    
    
    /**
     * Импортиране на csv-файл в даден мениджър
     *
     * @param int $clsId
     * @param int $objId
     *
     * @return string
     */
    public function getExportTitle($clsId, $objId)
    {
        return 'CSV файл';
    }
    
    
    /**
     * Импортиране на csv-файл в даден мениджър
     *
     * @param core_Form    $form
     * @param int          $clsId
     * @param int|stdClass $objId
     *
     * @return string|NULL
     */
    public function makeExport($form, $clsId, $objId)
    {
        $Frame = cls::get($clsId);
        $frameRec = $Frame->fetchRec($objId);
        
        doclog_Documents::saveAction(array('action' => doclog_Documents::ACTION_EXPORT, 'containerId' => $frameRec->containerId, 'threadId' => $frameRec->threadId,));
        doclog_Documents::flushActions();
        
        // Ако е избрана версия експортира се тя
        if ($versionId = frame2_Reports::getSelectedVersionId($objId)) {
            if ($versionRec = frame2_ReportVersions::fetchField($versionId, 'oldRec')) {
                $frameRec = $versionRec;
            }
        }
        
        $params = (array)$form->rec;
        if (isset($params['newLineDelimiter'])) {
            $params['newLineDelimiter'] = ($params['newLineDelimiter'] == 1) ? "\n" : (($params['newLineDelimiter'] == 2) ? "\r\n" : "\n");
        }
        
        unset($params['type']);
        
        setIfNot($params['encoding'], 'UTF-8');
        setIfNot($params['extension'], 'csv');
        
        // Подготовка на данните
        $lang = null;
        $csvRecs = $fields = array();
        if ($Driver = $Frame->getDriver($frameRec)) {
            $lang = $Driver->getRenderLang($frameRec);
            if(isset($lang)){
                core_Lg::push($lang);
            }
            
            $csvRecs = $Driver->getExportRecs($frameRec, $this);
            $fields = $Driver->getCsvExportFieldset($frameRec);
        }
        
        // Ако има данни за експорт
        if (count($csvRecs)) {
            
            // Създаване на csv-то
            $csv = csv_Lib::createCsv($csvRecs, $fields, null, $params);
            
            if(isset($lang)){
                core_Lg::pop();
            }
            
            // Подсигуряване че енкодига е UTF8
            $csv = mb_convert_encoding($csv, 'UTF-8', 'UTF-8');
            $csv = iconv('utf-8', $params['encoding'] . '//TRANSLIT', $csv);
            
            // Записване във файловата система
            $extension = $params['extension'];
            $fileName = $Frame->getHandle($objId) . '-' . str::removeWhiteSpace(str::utf2ascii($frameRec->title), '_');
            $fileHnd = fileman::absorbStr($csv, "exportFiles", "{$fileName}.{$extension}");
            $fileId = fileman::fetchByFh($fileHnd, 'id');
            doc_Linked::add($frameRec->containerId, $fileId, 'doc', 'file');
        }
        
        if (isset($fileHnd)) {
            $form->toolbar->addBtn('Сваляне', array('fileman_Download', 'download', 'fh' => $fileHnd, 'forceDownload' => true), 'ef_icon = fileman/icons/16/csv.png, title=Сваляне на документа');
            $form->info .= '<b>' . tr('Файл|*: ') . '</b>' . fileman::getLink($fileHnd);
            $Frame->logWrite('Експорт на CSV', $objId);
        } else {
            $form->info .= "<div class='formNotice'>" . tr('Няма данни за експорт|*.') . '</div>';
        }
        
        $fields = array_keys($form->selectFields("#name != 'type'"));
        foreach ($fields as $fld){
            $form->setField($fld, 'input=none');
        }
        
        return $fileHnd;
    }
    
    
    /**
     * Връща линк за експортиране във външната част
     *
     * @param int    $clsId
     * @param int    $objId
     * @param string $mid
     *
     * @return core_ET|NULL
     */
    public function getExternalExportLink($clsId, $objId, $mid)
    {
        Request::setProtected(array('objId', 'clsId', 'mid', 'typeCls'));
        $link = ht::createLink('CSV', array('export_Export', 'exportInExternal', 'objId' => $objId, 'clsId' => $clsId, 'mid' => $mid, 'typeCls' => get_called_class(), 'ret_url' => true), null, array('class' => 'hideLink inlineLinks',  'ef_icon' => 'fileman/icons/16/csv.png'));
        
        return $link;
    }
    
    
    /**
     * Добавя параметри към експорта на формата
     *
     * @param core_Form    $form
     * @param int          $clsId
     * @param int|stdClass $objId
     *
     * @return NULL|string
     */
    public function addParamFields($form, $clsId, $objId)
    {
        $title = $this->getExportTitle($clsId, $objId);
        
        $form->FLD("columns", 'enum(yes=Да,none=Не)', "caption=|{$title}|* - |настройки|*->Имена на колони,autohide=any");
        $form->setDefault("columns", 'yes');
        
        $form->FNC('decPoint', 'varchar(1,size=3)', "input,caption=|{$title}|* - |настройки|*->Десетичен знак,autohide=any");
        $form->FNC('dateFormat', 'enum(,d.m.Y=|*22.11.1999, d-m-Y=|*22-11-1999, d/m/Y=|*22/11/1999, m.d.Y=|*11.22.1999, m-d-Y=|*11-22-1999, m/d/Y=|*11/22/1999, d.m.y=|*22.11.99, d-m-y=|*22-11-99, d/m/y=|*22/11/99, m.d.y=|*11.22.99, m-d-y=|*11-22-99, m/d/y=|*11/22/99)', "input,caption=|{$title}|* - |настройки|*->Формат за дата,autohide=any");
        $form->FNC('datetimeFormat', 'enum(,d.m.y H:i=|*22.11.1999 00:00, d.m.y H:i:s=|*22.11.1999 00:00:00)', "input,caption=|{$title}|* - |настройки|*->Формат за дата и час,autohide=any");
        $form->FNC('delimiter', 'varchar(1,size=3)', "input,caption=|{$title}|* - |настройки|*->Разделител,autohide=any");
        $form->FNC('enclosure', 'varchar(1,size=3)', "input,caption=|{$title}|* - |настройки|*->Ограждане,autohide");
        $form->FNC('encoding', 'enum(utf-8=Уникод|* (UTF-8),cp1251=Windows Cyrillic|* (CP1251))', "caption=|{$title}|* - |разширени настройки|*->Кодиране,input,autohide=any");
        $form->FNC('extension', 'enum(csv=.csv,txt=.txt)', "input,caption=|{$title}|* - |разширени настройки|*->Файлово разширение,autohide=any");
        $form->FNC('newLineDelimiter', 'varchar(1,size=3)', "input,caption=|{$title}|* - |разширени настройки|*->Нов ред,autohide=any");
        
        $dateFormat = null;
        setIfNot($dateFormat, csv_Setup::get('DATE_MASK'), core_Setup::get('EF_DATE_FORMAT', true));
        $form->setDefault('dateFormat', $dateFormat);
        
        $datetimeFormat = null;
        setIfNot($datetimeFormat, csv_Setup::get('DATE_TIME_MASK'), 'd.m.y H:i');
        $form->setDefault('datetimeFormat', $datetimeFormat);
        
        $form->setOptions('newLineDelimiter', array('1' => '\n', '2' => '\r\n', '3' => '\r'));
        $form->setOptions('delimiter', array(',' => ',', ';' => ';', ':' => ':', '|' => '|'));
        $form->setOptions('enclosure', array('"' => '"', '\'' => '\''));
        $form->setOptions('decPoint', array('.' => '.', ',' => ','));
        $form->setDefault('enclosure', '"');
        $form->setDefault('newLineDelimiter', 1);
        
        $decimalSign = '.';
        setIfNot($decimalSign, html_entity_decode(csv_Setup::get('DEC_POINT'), ENT_COMPAT | ENT_HTML401, 'UTF-8'), html_entity_decode(core_Setup::get('EF_NUMBER_DEC_POINT', true), ENT_COMPAT | ENT_HTML401, 'UTF-8'));
        $form->setDefault('decPoint', $decimalSign);
        
        $delimiter = str_replace(array('&comma;', 'semicolon', 'colon', '&vert;', '&Tab;', 'comma', 'vertical'), array(',', ';', ':', '|', "\t", ',', '|'), csv_Setup::get('DELIMITER'));
        if (strlen($delimiter) > 1) {
            $delimiter = html_entity_decode($delimiter, ENT_COMPAT | ENT_HTML401, 'UTF-8');
        }
        $form->setDefault('delimiter', $delimiter);
    }
}
