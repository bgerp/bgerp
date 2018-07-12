<?php


/**
 * Експортиране на детайлите на документив в csv формат
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
class export_Csv extends core_Mvc
{
    /**
     * Заглавие на таблицата
     */
    public $title = 'Експортиране на документ като CSV';
    
    
    public $interfaces = 'export_ExportTypeIntf, export_ToXlsExportIntf';
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
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
        
        $canUse = false;
        
        // Трябва да детайли, които да могат да се експортват
        
        $clsArr = core_Classes::getOptionsByInterface('export_DetailExportCsvIntf');
        
        if (empty($clsArr)) {
            
            return false;
        }
        
        $clsInst = cls::get($clsId);
        
        if (!($clsInst instanceof core_Master)) {
            
            return false;
        }
        
        $detArr = arr::make($clsInst->details);
        
        if (empty($detArr)) {
            
            return false;
        }
        
        $rec = $clsInst->fetch($objId);
        
        if (!$rec) {
            
            return false;
        }
        
        foreach ($clsArr as $clsName) {
            if (!cls::load($clsName, true)) {
                continue;
            }
            
            $inst = cls::getInterface('export_DetailExportCsvIntf', $clsName);
            
            $mFieldName = $inst->getExportMasterFieldName();
            
            if (!$mFieldName) {
                continue;
            }
            
            foreach ($detArr as $dName) {
                if (!cls::load($dName, true)) {
                    continue;
                }
                
                $dInst = cls::get($dName);
                
                if (!$dInst->fields[$mFieldName]) {
                    continue;
                }
                
                if (!$dInst->masterKey) {
                    continue;
                }
                
                if (!($inst->class instanceof $dInst->fields[$mFieldName]->type->params['mvc'])) {
                    continue;
                }
                
                if (!$dInst->fetch(array("#{$dInst->masterKey} = '[#1#]'", $objId))) {
                    continue;
                }
                
                $canUse = true;
                
                break;
            }
            
            if ($canUse) {
                break;
            }
        }
        
        return $canUse;
    }
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
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
     * Инпортиране на csv-файл в даден мениджър
     *
     * @param core_Form    $form
     * @param int          $clsId
     * @param int|stdClass $objId
     *
     * @return NULL|string
     */
    public function makeExport($form, $clsId, $objId)
    {
        $clsInst = cls::get($clsId);
        $cRec = $clsInst->fetchRec($objId);
        
        $mid = doclog_Documents::saveAction(
                        array(
                            'action' => doclog_Documents::ACTION_EXPORT,
                            'containerId' => $cRec->containerId,
                            'threadId' => $cRec->threadId,
                        )
                );
        
        // Флъшваме екшъна за да се запише в модела
        doclog_Documents::flushActions();
        
        $lg = '';
        $isPushed = false;
        if ($cRec->template) {
            $lg = $clsInst->pushTemplateLg($cRec->template);
        }
        
        $userId = core_Users::getCurrent();
        
        if ($userId < 1) {
            $userId = $cRec->activatedBy;
        }
        
        if ($userId < 1) {
            $userId = $cRec->createdBy;
        }
        
        if (($userId < 1) && ($cRec->containerId)) {
            $sContainerRec = doc_Containers::fetch($cRec->containerId);
            $userId = $sContainerRec->activatedBy;
            if ($userId < 1) {
                if ($sContainerRec->modifiedBy >= 0) {
                    $userId = $sContainerRec->modifiedBy;
                } elseif ($sContainerRec->createdBy >= 0) {
                    $userId = $sContainerRec->createdBy;
                }
            }
        }
        
        if (!$lg) {
            $lg = doc_Containers::getLanguage($cRec->containerId);
            
            if ($lg && !core_Lg::isGoodLg($lg)) {
                $lg = 'en';
            }
            
            if ($lg) {
                core_Lg::push($lg);
            }
        }
        
        $recs = array();
        
        try {
            $clsArr = core_Classes::getOptionsByInterface('export_DetailExportCsvIntf');
            
            foreach ($clsArr as $clsName) {
                $inst = cls::getInterface('export_DetailExportCsvIntf', $clsName);
                $csvFields = new core_FieldSet();
                $recs = $inst->getRecsForExportInDetails($clsInst, $cRec, $csvFields, $userId);
                
                if (!empty($recs)) {
                    break;
                }
            }
        } catch (core_exception_Expect $e) {
        }
        
        $fileHnd = null;
        if (!empty($recs)) {
            $csv = csv_Lib::createCsv($recs, $csvFields);
            
            $fileName = $clsInst->getHandle($cRec->id) . '_Export.csv';
            
            $fileHnd = fileman::absorbStr($csv, 'exportFiles', $fileName);
        }
        
        if ($lg) {
            core_Lg::pop();
        }
        
        if ($fileHnd) {
            $form->toolbar->addBtn('Сваляне', array('fileman_Download', 'download', 'fh' => $fileHnd, 'forceDownload' => true), 'ef_icon = fileman/icons/16/csv.png, title=Сваляне на документа');
            
            $form->info .= '<b>' . tr('Файл|*: ') . '</b>' . fileman::getLink($fileHnd);
        } else {
            $form->info .= "<div class='formNotice'>" . tr('Няма данни за експорт|*.') . '</div>';
        }
        
        $clsInst->logWrite('Генериране на CSV', $objId);
        
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
}
