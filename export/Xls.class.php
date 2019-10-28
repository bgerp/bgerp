<?php


/**
 * Експортиране на детайлите на документив в xls формат
 *
 * @category  bgerp
 * @package   export
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class export_Xls extends core_Mvc
{
    /**
     * Заглавие на таблицата
     */
    public $title = 'Експортиране на документ като XLS';
    
    
    public $interfaces = 'export_ExportTypeIntf';
    
    
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
        $csvClsArr = $this->getCsvExportIntf();
        
        if (empty($csvClsArr)) {
            
            return false;
        }
        
        $canUse = false;
        
        foreach ($csvClsArr as $csvClsId => $clsName) {
            if (!cls::load($csvClsId, true)) {
                continue;
            }
            
            $inst = cls::get($csvClsId);
            
            if ($inst->canUseExport($clsId, $objId)) {
                $canUse = true;
                
                break;
            }
        }
        
        return $canUse;
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
        return 'XLS таблица';
    }
    
    
    /**
     * Импортиране на csv-файл в даден мениджър
     *
     * @param core_Form    $form
     * @param int          $clsId
     * @param int|stdClass $objId
     *
     * @return NULL|string
     */
    public function makeExport($form, $clsId, $objId)
    {
        $csvClsArr = $this->getCsvExportIntf();
        
        if (empty($csvClsArr)) {
            
            return false;
        }
        
        $nFileHnd = '';
        
        foreach ($csvClsArr as $csvClsId => $clsName) {
            if (!cls::load($csvClsId, true)) {
                continue;
            }
            
            $inst = cls::get($csvClsId);
            
            $nForm = cls::get('core_Form');
            
            $oId = (is_object($objId)) ? $objId->id : $objId;
            
            if ($inst->canUseExport($clsId, $oId)) {
                $fileHnd = $inst->makeExport($nForm, $clsId, $objId);
                
                // Ако се създаде CSV - генерираме XLS
                if ($fileHnd) {
                    $fRec = fileman::fetchByFh($fileHnd);
                    $fPath = fileman_webdrv_Office::convertToFile($fRec, 'xls', false, 'export_Xls::afterConvertToXls', 'xls');
                    
                    if ($fPath && is_file($fPath)) {
                        $nFileHnd = fileman::absorb($fPath, 'exportFiles');
                        
                        // Изтриваме директорията след като качим файла
                        core_Os::deleteDir(dirname($fPath));
                        
                        break;
                    }
                }
            }
        }
        
        if ($nFileHnd) {
            $form->toolbar->addBtn('Сваляне', array('fileman_Download', 'download', 'fh' => $nFileHnd, 'forceDownload' => true), 'ef_icon = fileman/icons/16/xls.png, title=Сваляне на документа');
            
            $form->info .= '<b>' . tr('Файл|*: ') . '</b>' . fileman::getLink($nFileHnd);
            $clsInst = cls::get($clsId);
            $clsInst->logWrite('Генериране на XLS', $objId);
            
            return $nFileHnd;
        }
        $form->info .= "<div class='formNotice'>" . tr('Грешка при експорт|*.') . '</div>';
    }
    
    
    /**
     * Функция, която получава управлението след конвертирането на офис докуемнта към PDF
     *
     * @param object $script - Обект със стойности
     *
     * @return bool TRUE - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове
     *              и записа от таблицата fconv_Process
     *
     * @access protected
     */
    public static function afterConvertToXls($script)
    {
        // Десериализираме параметрите
        $params = unserialize($script->params);
        
        // Проверяваме дали е имало грешка при предишното конвертиране
        $error = fileman_Indexes::haveErrors($script->outFilePath, $params);
        
        // Отключваме предишния процес
        core_Locks::release($params['lockId']);
        
        // Да не се изтрива директрояита, след като качим файла
        return false;
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
        
        $link = ht::createLink('XLS', array('export_Export', 'exportInExternal', 'objId' => $objId, 'clsId' => $clsId, 'mid' => $mid, 'typeCls' => get_called_class(), 'ret_url' => true), null, array('class' => 'hideLink inlineLinks',  'ef_icon' => 'fileman/icons/16/xls.png'));
        
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
        
    }
}
