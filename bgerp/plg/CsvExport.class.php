<?php



/**
 * Драйвър за експортиране на 'sales_Invoices' изходящи фактури към Bulmar Office
 * 
 * 
 * @category  bgerp
 * @package   bgerp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_plg_CsvExport extends core_Manager {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'bgerp_ExportIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Експортиране в Csv";
    
    
    /**
     * Можели да се добавя към този мениджър
     */
    function isApplicable($mvc)
    {
    	return TRUE;
    }
    
    
    /**
     * Подготвя формата за експорт
     * 
     * @param core_Form $form
     */
    function prepareExportForm(core_Form &$form)
    {
    	$form->FNC('delimiter', 'varchar(1,size=3)', 'input,caption=Разделител,mandatory');
    	$form->FNC('enclosure', 'varchar(1,size=3)', 'input,caption=Ограждане,mandatory');
    	
    	$form->setOptions('delimiter', array(',' => ',', ';' => ';', ':' => ':', '|' => '|'));
    	$form->setOptions('enclosure', array('"' => '"', '\'' => '\''));
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
    	
    	$conf = core_Setup::get('EF_MAX_EXPORT_CNT', TRUE);
    	if(count($recs) > $conf) {
    		redirect(array($mvc, 'list'), FALSE, "Броят на заявените записи за експорт надвишава максимално разрешения|* - " . $conf->EF_MAX_EXPORT_CNT, 'error');
    	}
    	
    	$content = $this->prepareFileContent($recs, $filter->delimiter, $filter->enclosure);
    	
    	return $content;
    }
    
    
    /**
     * Подготвя контента за експортиране
     */
    private function prepareFileContent($recs, $delimiter, $enclosure)
    {
		$fields = $this->mvc->selectFields("#export");
		if(is_array($fields) && !count($fields)){
			$fields = $this->mvc->selectFields();
		}
		
    	/* за всеки ред */
    	$csv = '';
    	if(is_array($recs)){
    		foreach($recs as $rec) {
    			$this->mvc->invoke('BeforeExportCsv', array($rec));
    			 
    			// Всеки нов ред ва началото е празен
    			$rCsv = '';
    	
    			/* за всяка колона */
    			foreach($fields as $field => $caption) {
    				$type = $this->mvc->fields[$field]->type;
    					
    				if ($type instanceof type_Key) {
    					$value = $this->mvc->getVerbal($rec, $field);
    				} else {
    					$value = $rec->{$field};
    				}
    					
    				// escape
    				if (preg_match('/\\r|\\n|\,|"/', $value)) {
    					$value = '"' . str_replace('"', '""', $value) . '"';
    				}
    				
    				$value = ($value) ? $enclosure . $value . $enclosure : '';
    				$rCsv .= ($rCsv ?  $delimiter : " ") . $value;
    			}
    	
    			/* END за всяка колона */
    			
    			$csv .= $rCsv . "\r\n";
    		}
    	}
    	
    	return $csv;
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