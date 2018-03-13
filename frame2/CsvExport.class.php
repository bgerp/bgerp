<?php



/**
 * Експортиране на справките като csv
 * 
 * @category  bgerp
 * @package   frame2
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class frame2_CsvExport extends core_Mvc
{
    
    
    /**
     * Заглавие на таблицата
     */
    public $title = "Експортиране на справка като CSV";
    
    
    /**
     *  Интерфейси
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
    public function canUseExport($clsId, $objId)
    {
    	$canUse = export_Export::canUseExport($clsId, $objId);
        if (!$canUse) return $canUse;
        
        return $clsId == frame2_Reports::getClassId();
    }
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     *
     * @param integer $clsId
     * @param integer $objId
     *
     * @return boolean
     */
    public function getExportTitle($clsId, $objId)
    {
        return 'CSV файл';
    }
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     * 
     * @param core_Form $form
     * @param integer $clsId
     * @param integer|stdClass $objId
     *
     * @return boolean
     */
    public function makeExport($form, $clsId, $objId)
    {
    	$Frame = cls::get($clsId);
    	$frameRec = $Frame->fetchRec($objId);
    	
    	$mid = doclog_Documents::saveAction(array('action' => doclog_Documents::ACTION_EXPORT, 'containerId' => $frameRec->containerId, 'threadId' => $frameRec->threadId,));
    	doclog_Documents::flushActions();
    	
    	// Ако е избрана версия експортира се тя
    	if($versionId = frame2_Reports::getSelectedVersionId($objId)){
    		if($versionRec = frame2_ReportVersions::fetchField($versionId, 'oldRec')){
    			$frameRec = $versionRec;
    		}
    	}
    	 
    	// Подготовка на данните
    	$csvRecs = $fields = array();
    	if($Driver = $Frame->getDriver($frameRec)){
    		$csvRecs = $Driver->getExportRecs($frameRec, $this);
    		$fields = $Driver->getCsvExportFieldset($frameRec);
    	}
    	 
    	// Ако има данни за експорт
    	if(count($csvRecs)){
    		
    		// Създаване на csv-то
    		$csv = csv_Lib::createCsv($csvRecs, $fields);
    		$csv .= "\n";
    		
    		// Подсигуряване че енкодига е UTF8
    		$csv = mb_convert_encoding($csv, 'UTF-8', 'UTF-8');
    		$csv = iconv('UTF-8', "UTF-8//IGNORE", $csv);
    		
    		// Записване във файловата система
    		$fileName = $Frame->getHandle($objId) . "-" . str::removeWhitespaces(str::utf2ascii($frameRec->title), '_');
    		$fileHnd = fileman::absorbStr($csv, 'exportCsv', "{$fileName}.csv");
    	}
    
    	if(isset($fileHnd)) {
    		$form->toolbar->addBtn('Сваляне', array('fileman_Download', 'download', 'fh' => $fileHnd, 'forceDownload' => TRUE), "ef_icon = fileman/icons/16/csv.png, title=" . tr('Сваляне на документа'));
    		$form->info .= "<b>" . tr('Файл|*: ') . "</b>" . fileman::getLink($fileHnd);
    	} else {
    		$form->info .= "<div class='formNotice'>" . tr("Няма данни за експорт|*.") . "</div>";
    	}
    	
    	$Frame->logWrite('Генериране на CSV', $objId);
    	
    	return $fileHnd;
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
    public function getExternalExportLink($clsId, $objId, $mid)
    {
        Request::setProtected(array('objId', 'clsId', 'mid', 'typeCls'));
        $link = ht::createLink('CSV', array('export_Export', 'exportInExternal', 'objId' => $objId, 'clsId' => $clsId, 'mid' => $mid, 'typeCls' => get_called_class(), 'ret_url' => TRUE), NULL, array('class' => 'hideLink inlineLinks',  'ef_icon' => 'fileman/icons/16/csv.png'));
        
        return $link;
    }
}
