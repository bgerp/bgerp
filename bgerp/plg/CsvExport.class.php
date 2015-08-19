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
    	$sets = array();
    	$fields = $this->mvc->selectFields();
    	foreach ($fields as $name => $fld){
    		$sets[] = "{$name}={$fld->caption}";
    	}
    	$sets = implode(',', $sets);
    	
    	$form->FNC('delimiter', 'varchar(1,size=3)', 'input,caption=Разделител,mandatory');
    	$form->FNC('fields', "set($sets)", 'input,caption=Полета');
    	$form->FNC('enclosure', 'varchar(1,size=3)', 'input,caption=Ограждане,mandatory');
    	$form->FNC('encoding', 'enum(utf-8=Уникод|* (UTF-8),
                                    cp1251=Windows Cyrillic|* (CP1251),
                                    koi8-r=Rus Cyrillic|* (KOI8-R))', 'caption=Знаци, formOrder=4,input');
    	
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
    	
    	$content = $this->prepareFileContent($recs, $filter->delimiter, $filter->enclosure, $filter->fields);
    	$content = iconv('utf-8', $filter->encoding, $content);
    	
    	return $content;
    }
    
    
    /**
     * Подготвя контента за експортиране
     */
    private function prepareFileContent($recs, $delimiter, $enclosure, $fields)
    {
		$fields = arr::make($fields, TRUE);
		
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
    					Mode::push('text', 'plain');
    					$value = $this->mvc->getVerbal($rec, $field);
    					Mode::pop('text');
    				} else {
    					$value = $rec->{$field};
    				}
    				$value = strip_tags($value);
    				
    				if (preg_match('/\\r|\\n|\,|"/', $value)) {
    					$value = $enclosure . str_replace($enclosure, "{$enclosure}{$enclosure}", $value) . $enclosure;
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