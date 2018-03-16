<?php


/**
 * Експортиране на детайлите на документив в xls формат
 * 
 * @category  bgerp
 * @package   export
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class export_Xls extends core_Mvc
{
    
    
    /**
     * Заглавие на таблицата
     */
    public $title = "Експортиране на документ като XLS";
    
    
    /**
     *  
     */
    public $interfaces = 'export_ExportTypeIntf';
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     * 
     * @param integer $clsId
     * @param integer $objId
     * 
     * @return boolean
     */
    function canUseExport($clsId, $objId)
    {
        $csvClsArr = $this->getCsvExportIntf();
        
        if (empty($csvClsArr)) return FALSE;
        
        $canUse = FALSE;
        
        foreach ($csvClsArr as $csvClsId => $clsName) {
            if (!cls::load($csvClsId, TRUE)) continue;
            
            $inst = cls::get($csvClsId);
            
            if ($inst->canUseExport($clsId, $objId)) {
                
                $canUse = TRUE;
                
                break;
            }
        }
        
        return $canUse;
    }
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     *
     * @param integer $clsId
     * @param integer $objId
     *
     * @return string
     */
    function getExportTitle($clsId, $objId)
    {
        
        return 'Таблица';
    }
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     * 
     * @param core_Form $form
     * @param integer $clsId
     * @param integer|stdClass $objId
     *
     * @return NULL|string
     */
    function makeExport($form, $clsId, $objId)
    {
        $csvClsArr = $this->getCsvExportIntf();
        
        if (empty($csvClsArr)) return FALSE;
        
        $nFileHnd = '';
        
        foreach ($csvClsArr as $csvClsId => $clsName) {
            if (!cls::load($csvClsId, TRUE)) continue;
            
            $inst = cls::get($csvClsId);
            
            $nForm = cls::get('core_Form');
            
            $oId = (is_object($objId)) ? $objId->id : $objId;
            
            if ($inst->canUseExport($clsId, $oId)) {
                
                $fileHnd = $inst->makeExport($nForm, $clsId, $objId);
                
                // Ако се създаде CSV - генерираме XLS
                if ($fileHnd) {
                    
                    $fRec = fileman::fetchByFh($fileHnd);
                    $fPath = fileman_webdrv_Office::convertToFile($fRec, 'xls', FALSE, 'export_Xls::afterConvertToXls', 'xls');
                    
                    if ($fPath && is_file($fPath)) {
                        $nFileHnd = fileman::absorb($fPath, 'exportCsv');
                        
                        // Изтриваме директорията след като качим файла
                        core_Os::deleteDir(dirname($fPath));
                        
                        break;
                    }
                }
            }
        }
        
        if ($nFileHnd) {
            if ($nFileHnd) {
                $form->toolbar->addBtn('Сваляне', array('fileman_Download', 'download', 'fh' => $nFileHnd, 'forceDownload' => TRUE), "ef_icon = fileman/icons/16/xls.png, title=" . tr('Сваляне на документа'));
                
                $form->info .= "<b>" . tr('Файл|*: ') . "</b>" . fileman::getLink($nFileHnd);
            } else {
                $form->info .= "<div class='formNotice'>" . tr("Няма данни за експорт|*.") . "</div>";
            }
            
            $clsInst = cls::get($clsId);
            $clsInst->logWrite('Генериране на XLS', $objId);
            
            return $nFileHnd;
        }
    }
    
    
    /**
     * Функция, която получава управлението след конвертирането на офис докуемнта към PDF
     *
     * @param object $script - Обект със стойности
     *
     * @return boolean TRUE - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове
     * и записа от таблицата fconv_Process
     *
     * @access protected
     */
    static function afterConvertToXls($script)
    {
        // Десериализираме параметрите
        $params = unserialize($script->params);
        
        // Проверяваме дали е имало грешка при предишното конвертиране
        $error = fileman_Indexes::haveErrors($script->outFilePath, $params);
        
        // Отключваме предишния процес
        core_Locks::release($params['lockId']);
        
        // Да не се изтрива директрояита, след като качим файла
        return FALSE;
    }
    
    
    /**
     * Връща линк за експортиране във външната част
     *
     * @param integer $clsId
     * @param integer $objId
     * @param string $mid
     * 
     * @return core_ET|NULL
     */
    function getExternalExportLink($clsId, $objId, $mid)
    {
        Request::setProtected(array('objId', 'clsId', 'mid', 'typeCls'));
        
        $link = ht::createLink('XLS', array('export_Export', 'exportInExternal', 'objId' => $objId, 'clsId' => $clsId, 'mid' => $mid, 'typeCls' => get_called_class(), 'ret_url' => TRUE), NULL, array('class' => 'hideLink inlineLinks',  'ef_icon' => 'fileman/icons/16/xls.png'));
        
        return $link;
    }
    
    
    /**
     * Връща класовете, които може да имат съответния интерфейс
     * 
     * @return array
     */
    protected function getCsvExportIntf()
    {
        $clsArr = core_Classes::getOptionsByInterface('export_ToXlsExportIntf');
        
        return $clsArr;
    }
}
