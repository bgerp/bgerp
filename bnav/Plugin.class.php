<?php



/**
 * Плъгин за импорт на данни от Бизнес навигатор
 * 
 *
 * @category  bgerp
 * @package   bnav
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bnav_Plugin extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     * 
     * @param core_Mvc $mvc
     */
    function on_AfterDescription(core_Mvc $mvc)
    {
    	// Проверка за приложимост на плъгина към зададения $mvc
        if(!static::checkApplicability($mvc)) return;
        
        // Добавяне на необходимите полета
        $mvc->FLD('bnavCode', 'varchar(150)', 'caption=Код БН,remember=info,width=15em,mandatory');
		
        if($mvc->fields['eanCode']){
        	
        	// Полето се слага след баркода на продукта
        	$mvc->fields = array_slice($mvc->fields, 0, 4, true) +
            array('bnavCode' => $mvc->fields['bnavCode']) +
            array_slice($mvc->fields, 4, NULL, true);
        }
    }
    
    
    /**
     * Проверява дали този плъгин е приложим към зададен мениджър
     * 
     * @param core_Mvc $mvc
     * @return boolean
     */
    protected static function checkApplicability($mvc)
    {
    	// Прикачане е допустимо само към наследник на cat_Products ...
        if (!$mvc instanceof cat_Products) {
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    /**
   	 * Обработка на SingleToolbar-a
   	 */
   	static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(haveRole('admin')){
    		$url = array($mvc, 'import', 'retUrl' => TRUE);
    		$data->toolbar->addBtn('Импорт', $url, NULL, 'ef_icon=img/16/import16.png');
    	}
    }
    
    
    /**
     * Преди всеки екшън на мениджъра-домакин
     *
     * @param core_Manager $mvc
     * @param core_Et $tpl
     * @param core_Mvc $data
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
    	if($action == 'test'){
    		$hnd = 'BltWOe';
    		static::importProducts($hnd);
    		//$mvc->
    		return FALSE;
    	}
    	
    	if ($action == 'import') {
    		
    		// Генерираме форма за основание и "обличаме" я във wrapper-а на $mvc.
        	$form = static::prepareImportForm();
        	$form->input();
        	if($form->isSubmitted()){
        		$importRes = static::importProducts($form->rec->csvFile);
        	}
        	
        	$form->toolbar->addSbBtn('Запис', 'save', array('class'=>'btn-next btn-move'));
        	$form->toolbar->addBtn('Отказ', array($mvc, 'list'), array('class'=>'btn-cancel'));
        	
        	$form = $form->renderHtml();
        	$tpl = $mvc->renderWrapping($form);
        	
        	return FALSE;
        }
    }
    
    
    /**
     * 
     * Enter description here ...
     */
    private static function prepareImportForm()
    {
    	$form = cls::get('core_Form');
    	$form->title = 'Импортиране от бизнес навигатор';
        $form->FNC('csvFile', 'fileman_FileType(bucket=bnav_importCsv)', 'caption=CSV Файл,input,mandatory');
    	return $form;
    }
    
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $rec
     */
    private static function importProducts($hnd)
    {
    	$html = '';
    	
    	$groups = static::importGroups($hnd, $html);
    	bp($html);
    	return $html;
    }
    
    
    /**
     * Филтрира продуктовите групи и създава масив 
     * с неповтарящи се групи
     * @param array $arr - масив получен от csv файл
     * @return array $newGroups - масив с групи
     */
    static function filterImportGroups(&$hnd)
    {
    	$i = 0;
    	$newGroups = array();
    	$path = fileman_Files::fetchByFh($hnd, 'path');
    	
    	if(($handle = fopen($path, "r")) !== FALSE) {
	    	while (($csvRow = fgetcsv($handle, 5000, ",")) !== FALSE) {
	    		if($i != 0){
		    		if(!array_key_exists($csvRow[4], $newGroups) && !is_numeric($csvRow[4])){
	    				$rowArr = array('title' =>$csvRow[4], 'sysId' =>$csvRow[3]);
		    			$newGroups[$csvRow[4]] = $rowArr;
	    			}
	    		}
	    		$i++;
	    	}
    	}
    	
    	return $newGroups;
    }
    
    
    /**
     * Импортиране на групите от csv-то( ако ги няма )
     * @param array $arr - масив получен от csv файл-а
     * @param string $html - Съобщение
     * @return array $groups - масив с ид-та на всяка 
     * група от системата
     */
    static function importGroups(&$hnd, &$html)
    {
    	$newGroups = static::filterImportGroups($hnd);
    	$added = $updated = 0;
    	$groups = array();
    	foreach($newGroups as $gr){
    		$nRec = new stdClass();
    		$nRec->name = $gr['title'];
    		$nRec->sysId = $gr['sysId'];
    		
    		if($rec = cat_Groups::fetch("#name = '{$gr['title']}'")){
    			$nRec->id = $rec->id;
    			$updated++;
    		} else {
    			$added++;
    		}
    			
    		$groups[$gr['title']] = cat_Groups::save($nRec);
    	}
    	
    	$html .= "Добавени {$added} нови групи, Обновени {$updated} съществуващи групи";
    	return $groups;
    }
}