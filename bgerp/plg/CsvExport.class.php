<?php



/**
 * Драйвър за експортиране на документи в csv формат
 * 
 * Класа трябва да има $exportableCsvFields за да може да се експортират данни от него в CSV формат
 * 
 * 
 * @category  bgerp
 * @package   bgerp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_plg_CsvExport extends core_BaseClass {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'bgerp_ExportIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Експортиране в Csv";
    
    
    /**
     * Може ли да се добавя към този мениджър
     */
    function isApplicable($mvc)
    {
    	$exportableFields = $this->getExportableCsvFields($mvc);
    	
    	return empty($exportableFields) ? FALSE : TRUE;
    }
    
    
    /**
     * 
     * @param core_Mvc $mvc
     * 
     * @return array
     */
    public function getExportableCsvFields($mvc)
    {
        $exportableFields = arr::make($mvc->exportableCsvFields, TRUE);
        
        $csfFiledsArr = $mvc->selectFields("#export == Csv");
        
        foreach ($csfFiledsArr as $name => $field) {
            $exportableFields[$name] = $name;
        }
        
        return $exportableFields;
    }
    
    
    /**
     * Подготвя формата за експорт
     * 
     * @param core_Form $form
     */
    function prepareExportForm(core_Form &$form)
    {
    	$sets = $selected = array();
    	$fields = $this->mvc->selectFields();
    	$exportableFields = $this->getExportableCsvFields($this->mvc);
    	
    	foreach ($fields as $name => $fld){
    		if(in_array($name, $exportableFields)){
    			$sets[] = "{$name}={$fld->caption}";
    			$selected[$name] = $name;
    		}
    	}
    	
    	$sets[] = "ExternalLink=Линк";
    	
    	$selectedFields = cls::get('type_Set')->fromVerbal($selected);
    	
    	$sets = implode(',', $sets);
    	$form->FNC('showColumnNames', 'enum(yes=Да,no=Не)', 'input,caption=Имена на колони,mandatory');
    	$form->FNC('fields', "set($sets)", 'input,caption=Полета,mandatory');
    	$form->setDefault('fields', $selectedFields);
    	
    	$form->FNC('delimiter', 'varchar(1,size=3)', 'input,caption=Разделител,mandatory');
    	$form->FNC('enclosure', 'varchar(1,size=3)', 'input,caption=Ограждане,mandatory');
    	$form->FNC('decimalSign', 'varchar(1,size=3)', 'input,caption=Десетичен знак,mandatory');
    	$form->FNC('encoding', 'enum(utf-8=Уникод|* (UTF-8),
                                    cp1251=Windows Cyrillic|* (CP1251),
                                    koi8-r=Rus Cyrillic|* (KOI8-R))', 'caption=Знаци,input');
    	
    	$form->setOptions('delimiter', array(',' => ',', ';' => ';', ':' => ':', '|' => '|'));
    	$form->setOptions('enclosure', array('"' => '"', '\'' => '\''));
    	$form->setOptions('decimalSign', array('.' => '.', ',' => ','));
    }
    
    
    /**
     * Проверява импорт формата
     * 
     * @param core_Form $form
     */
    function checkExportForm(core_Form &$form)
    {
    }
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     *
     * @param mixed $data - данни
     * @return mixed - експортираните данни
     */
    public function export($filter)
    {
    	$cu = core_Users::getCurrent();
    	$recs = core_Cache::get($this->mvc->className, "exportRecs{$cu}");
        
        core_App::setTimeLimit(count($recs) / 100);

    	$retUrl = getRetUrl();
    	
    	if (empty($retUrl)) {
    	    if ($this->mvc->haveRightFor('list')) {
    	        $retUrl = array($this->mvc, 'list');
    	    } else {
    	        $retUrl = array($this->mvc);
    	    }
    	}
    	
    	if (!$recs) {
    	    redirect($retUrl, FALSE, '|Няма данни за експортиране');
    	}
    	
    	$maxCnt = core_Setup::get('EF_MAX_EXPORT_CNT', TRUE);
    	if (count($recs) > $maxCnt) {
    		redirect($retUrl, FALSE, "|Броят на заявените записи за експорт надвишава максимално разрешения|* - " . $maxCnt, 'error');
    	}
    	
    	$fieldsArr = arr::make($filter->fields, TRUE);
    	
    	if ($fieldsArr['ExternalLink'] && $recs) {
    	    $this->prepareExternalLink($recs);
    	}
    	
    	$csvParams = array();
    	$params = array();
    	
    	if ($filter->showColumnNames == 'yes') {
    	    if ($this->mvc && $this->mvc instanceof core_FieldSet) {
    	        foreach ($fieldsArr as $field => &$caption) {
    	            
    	            if($field != 'ExternalLink'){
        				$value = $this->mvc->fields[$field]->caption;
        				$valueArr = explode('->', $value);
        				if(count($valueArr) == 1){
        					$value = $valueArr[0];
        				} else {
        					$value = $valueArr[1];
        				}
        				foreach ($valueArr as &$v) {
        				    $v = transliterate(tr($v));
        				}
        				$caption = implode(':', $valueArr);
        			} else {
        				$caption = transliterate(tr('Връзка'));
        			}
    	        }
    	    }
    	} else {
    	    $params['columns'] = 'none';
    	}
    	
    	$params['delimiter'] = $filter->delimiter;
    	$params['decPoint'] = $filter->decimalSign;
    	$params['enclosure'] = $filter->enclosure;
    	
    	// TODO
    	$params['text'] = 'plain';
    	
	    $this->mvc->invoke('BeforeExportCsv', array(&$recs));
     
    	$content = csv_Lib::createCsv($recs, $this->mvc, $fieldsArr, $params);
    	$content = iconv('utf-8', $filter->encoding, $content);
    	
    	return $content;
    }
    
    
    /**
     * Подготвя линковете за виждане от външната част
     * 
     * @param array $recs
     */
    protected function prepareExternalLink(&$recs)
    {
        foreach ((array)$recs as $id => $rec) {
            if ($this->mvc->haveRightFor('single', $id) && $rec->containerId) {
                $mid = doclog_Documents::saveAction(
                    array(
                        'action'      => doclog_Documents::ACTION_EXPORT, 
                        'containerId' => $rec->containerId,
                        'threadId'    => $rec->threadId,
                    )
                );
                
                // Флъшваме екшъна за да се запише в модела
                doclog_Documents::flushActions();
                
                $externalLink = bgerp_plg_Blank::getUrlForShow($rec->containerId, $mid);
            } elseif ($id && $this->mvc instanceof core_Master) {
                $externalLink = toUrl(array($this->mvc, 'Single', $id), 'absolute');
            } else {
                $externalLink = toUrl(array($this->mvc), 'absolute');
            }
            
            $recs[$id]->ExternalLink = $externalLink;
        }
    }
    
    
    /**
     * Връща името на експортирания файл
     *
     * @return string $name
     */
    public function getExportedFileName()
    {
    	$timestamp = time();
    	$name = $this->mvc->className . "Csv{$timestamp}.csv";
    	 
    	return $name;
    }
}