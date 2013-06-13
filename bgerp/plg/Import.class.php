<?php



/**
 * Плъгин за импорт на данни от Бизнес навигатор
 * 
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_plg_Import extends core_Plugin
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
        if (!$mvc instanceof core_Manager) {
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
    		$importOptions = core_Classes::getOptionsByInterface('bgerp_ImportIntf');
    		if(count($importOptions)){
    			$url = array($mvc, 'import', 'retUrl' => TRUE);
    			$data->toolbar->addBtn('Импорт', $url, NULL, 'ef_icon=img/16/import16.png');
    		}
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
    	if($action == 'import'){
    		$importOptions = core_Classes::getOptionsByInterface('bgerp_ImportIntf');
    		expect(count($importOptions), 'Няма инсталирани драйвъри за импортиране');
    		
    		$exp = cls::get('expert_Expert', array('mvc' => $mvc));
    		$content = static::solveExpert($exp);
    		
        	if($content == 'SUCCESS') {
        		$driverId = $exp->getValue("#driver");
        		$csvData = $exp->getValue("#csvData");
        		$delimiter = $exp->getValue("#delimiter");
        		$enclosure = $exp->getValue("#enclosure");
        		$firstRow = $exp->getValue("#firstRow");
        		
        		$Driver = cls::get($driverId);
        		$fields = $Driver->getFields();
        		foreach($fields as $name => $caption){
        			$fields[$name] = $exp->getValue("#col{$name}");
        		}
        		
        		$rows = static::getCsvRows($csvData, $delimiter, $enclosure, $firstRow, $cols);
        		$msg = $Driver->import($rows, $fields);
        		return Redirect(array($Driver->getDestinationManager(), 'list'), 'FALSE', $msg);
        	} 
        	
    		if($content == 'DIALOG') {
                $content = $exp->getResult();
            }
            
            if($content == 'FAIL') {
                if($exp->onFail) {
                    $content = $mvc->onFail($exp);
                } else {
                    $exp->setRedirect();
                    setIfNot($exp->midRes->alert, $exp->message, 'Не може да се достигне крайната цел');
                    $content = $exp->getResult();
                }
            }
        	
        	$tpl = $mvc->renderWrapping($content);
            
            return FALSE;
    	}
    }
    
    
    /**
     * Връща масив с данните от csv-то
     * @param string $csvData -
     * @param char $delimiter -
     * @param char $enclosure -
     * @param string $firstRow -
     * @return array $rows -
     */
    private static function getCsvRows($csvData, $delimiter, $enclosure, $firstRow)
    {
    	$textArr = explode(PHP_EOL, $csvData);
    	foreach($textArr as $line){
    		$rows[] = str_getcsv($line, $delimiter, $enclosure);
    	}
    	
    	if($firstRow == 'columnNames'){
    		unset($rows[0]);
    	}
    	
    	return $rows;
    }
    
    
	/**
     * Зарежда данни от посочен CSV файл, като се опитва да ги конвертира в UTF-8
     */
    static function getFileContent($fh)
    {
        $csv = fileman_Files::getContent($fh);
        $csv = i18n_Charset::convertToUtf8($csv);
        
        return $csv;
    }
   
    
    /**
     * Enter description here ...
     * @param expert_Expert $exp
     */
    public static function solveExpert(expert_Expert &$exp)
    {
    	$exp->functions['getfilecontentcsv'] = 'bgerp_plg_Import::getFileContent';
    	$exp->functions['getcsvcolnames'] = 'blast_ListDetails::getCsvColNames';
    		
    	$exp->DEF('#driver', 'class(interface=bgerp_ImportIntf,select=title)', 'caption=Драйвър,input,mandatory');
    	$exp->question("#driver", tr("Моля, изберете драйвър") . ":", TRUE, 'title=' . tr('Какъв драйвер ще се използва') . '?');
    		
    	$exp->DEF('#source=Източник', 'enum(csvFile=Файл със CSV данни,csv=Copy&Paste на CSV данни)', 'maxRadio=5,columns=1,mandatory');
        $exp->ASSUME('#source', '"csvFile"');
        $exp->question("#source", tr("Моля, посочете източника на данните") . ":", TRUE, 'title=' . tr('От къде ще се импортират данните') . '?');
    		
        $exp->DEF('#csvData=CSV данни', 'text(1000000)', 'width=100%,mandatory');
        $exp->question("#csvData", tr("Моля, поставете данните") . ":", "#source == 'csv'", 'title=' . tr('Въвеждане на CSV данни за контакти'));
        	
        $exp->DEF('#csvFile=CSV файл', 'fileman_FileType(bucket=csvContacts)', 'mandatory');
        $exp->question("#csvFile", tr("Въведете файл с контактни данни във CSV формат") . ":", "#source == 'csvFile'", 'title=' . tr('Въвеждане на данните от файл'));
        $exp->rule("#csvData", "getFileContentCsv(#csvFile)");
        	
        $exp->DEF('#delimiter=Разделител', 'varchar(1,size=1)', array('value' => ','), 'mandatory');
        $exp->SUGGESTIONS("#delimiter", array(',' => ',', ';' => ';', ':' => ':', '|' => '|'));
        $exp->DEF('#enclosure=Ограждане', 'varchar(1,size=1)', array('value' => '"'), 'mandatory');
        $exp->SUGGESTIONS("#enclosure", array('"' => '"', '\'' => '\''));
        $exp->DEF('#firstRow=Първи ред', 'enum(columnNames=Имена на колони,data=Данни)', 'mandatory');
        $exp->question("#delimiter,#enclosure,#firstRow", tr("Посочете формата на CSV данните") . ":", "#csvData", 'title=' . tr('Уточняване на разделителя и ограждането'));
        
        $exp->rule("#csvColumnsCnt", "count(getCsvColNames(#csvData,#delimiter,#enclosure))");
        $exp->WARNING(tr("Възможен е проблем с формата на CSV данните, защото е открита само една колона"), '#csvColumnsCnt == 2');
        $exp->ERROR(tr("Има проблем с формата на CSV данните"). ". <br>" . tr("Моля проверете дали правилно сте въвели данните и разделителя"), '#csvColumnsCnt < 2');
        
        $driverId = $exp->getValue('#driver');
        if($driverId){
        	$Driver = cls::get($driverId);
	        $fieldsArr = $Driver->getFields();
	        $dManager= $Driver->getDestinationManager();
	        	
	    	foreach($fieldsArr as $name => $caption) {
		        $exp->DEF("#col{$name}={$caption}", 'int', 'mandatory');
		        $exp->OPTIONS("#col{$name}", "getCsvColNames(#csvData,#delimiter,#enclosure)");
		        $exp->ASSUME("#col{$name}", "getCsvColNames(#csvData,#delimiter,#enclosure,'{$caption}')");
		            
		        $qFields .= ($qFields ? ',' : '') . "#col{$name}";
	        }
	        	$exp->question($qFields, tr("Въведете съответстващите полета за \"{$dManager->className}\"") . ":", TRUE, 'title=' . tr('Съответствие между полетата на източника и списъка'));
        		$res = $exp->solve("#driver,#source,#delimiter,#enclosure,#firstRow,{$qFields}");
        } else {
        	$res = $exp->solve("#driver,#source,#delimiter,#enclosure,#firstRow");
        }
        	
        	return $res;
    }
    
    
	/**
     * 
     * Enter description here ...
     */
    private static function prepareImportForm()
    {
    	$form = cls::get('core_Form');
    	$form->title = 'Импортиране';
    	$form->FNC('importClass', 'class(interface=bgerp_ImportIntf,select=title)', 'caption=Технолог,input,mandatory');
        $form->FNC('text', 'text(rows=3)', 'caption=Текст,input,width=35em');
    	$form->FNC('csvFile', 'fileman_FileType(bucket=bnav_importCsv)', 'caption=CSV Файл,input');
       
    	return $form;
    }
}

