<?php



/**
 * Плъгин за филтриране на документи с вальор по ключови думи и дата,
 * показва и Обобщение на резултатите от списъчния изглед
 * 
 * За Обобщението: Показва в малка таблица над списъчния изглед обобщена
 * информация за намерените резултати като брой и други.
 * За да се посочи в модела че на дадено поле трябва да се извади
 * обобщаваща информация е нужно да се дефинира параметър "summary="
 * 
 * 
 * Възможни стойности на 'summary': 
 * summary = amount - Служи за обобщение на числово поле което представлява
 * парична сума. Обощения резултат се показва в неговата равностойност
 * в основната валута за периода. По дефолт се приема че полето в което
 * е описано в коя валута е сумата е 'currencyId'. Ако полето се казва
 * другояче се дефинира константата 'filterCurrencyField' със стойност
 * името на полето съдържащо валутата.
 * summary = quantity - изчислява сумарната стойност на поле което съдържа
 * някаква бройка (като брой продукти и други)  
 * 
 * 
 * За Филтър формата:
 * Създава филтър форма която филтрира документите по зададен времеви период
 * и пълнотекстото поле (@see plg_Search). По дефолт приема че полето
 * по която дата ще се търси е "valior". За документи където полето
 * се казва по друг начин се дефинира константата 'filterDateField' която
 * показва по кое поле ще се филтрира
 * 
 * За търсене по дата, когато документа има начална и крайна дата се дефинират
 * 'filterFieldDateFrom' и 'filterFieldDateTo'
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_plg_DocumentSummary extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     * 
     * @param core_Mvc $mvc
     */
    function on_AfterDescription(core_Mvc $mvc)
    {
    	// Проверка за приложимост на плъгина към зададения $mvc
        static::checkApplicability($mvc);
        
        setIfNot($mvc->filterDateField, 'valior');
        setIfNot($mvc->filterCurrencyField, 'currencyId');
    }
    
    
    /**
     * Проверява дали този плъгин е приложим към зададен мениджър
     * 
     * @param core_Mvc $mvc
     * @return boolean
     */
    protected static function checkApplicability($mvc)
    {
    	// Прикачане е допустимо само към наследник на core_Manager ...
        if (!$mvc instanceof core_Manager) {
            return FALSE;
        }
      
        if(!$mvc->getInterface('acc_TransactionSourceIntf')) {
        	return FALSE;
        }
        
        return TRUE;
    }
    
    
    /**
	 *  Подготовка на филтър формата
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{
		$data->listFilter->layout = new ET(tr('|*' . getFileContent('acc/plg/tpl/FilterForm.shtml')));
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->FNC('from', 'date', 'width=6em,caption=От,silent');
		$data->listFilter->FNC('to', 'date', 'width=6em,caption=До,silent');
		$data->listFilter->setDefault('from', date('Y-m-01'));
		$data->listFilter->setDefault('to', date("Y-m-t", strtotime(dt::now())));
		
		$fields = $data->listFilter->selectFields();
		if(isset($fields['search'])){
			$data->listFilter->showFields .= 'search,';
		}
		$data->listFilter->showFields .= 'from, to';
		
        // Активиране на филтъра
        $data->listFilter->input(NULL, 'silent');
    }
	
	
	/**
	 * Филтрираме резултатите
	 */
	function on_BeforePrepareListRecs($mvc, $res, $data)
	{
		if($filter = $data->listFilter->rec) {
			
			if($filter->search){
				plg_Search::applySearch($filter->search, $data->query);
			}
    		
	        $dateRange = array();
	        
	        if ($filter->from) {
	            $dateRange[0] = $filter->from; 
	        }
	        
	        if ($filter->to) {
	            $dateRange[1] = $filter->to; 
	        }
	        
	        if (count($dateRange) == 2) {
	            sort($dateRange);
	        }
	       
			if($dateRange[0]) {
				$fromField = ($mvc->filterFieldDateTo) ? $mvc->filterFieldDateTo : $mvc->filterDateField;
    			$data->query->where(array("#{$fromField} >= '[#1#]'", $dateRange[0]));
    			if($mvc->filterFieldDateTo){
    				$data->query->orWhere(array("#{$fromField} IS NULL", $dateRange[0]));
    			}
    		}
    		
			if($dateRange[1]) {
				$toField = ($mvc->filterFieldDateFrom) ? $mvc->filterFieldDateFrom : $mvc->filterDateField;
    			$data->query->where(array("#{$toField} <= '[#1#] 23:59:59'", $dateRange[1]));
    			if($mvc->filterFieldDateFrom){
    				$data->query->orWhere(array("#{$toField} IS NULL", $dateRange[1]));
    			}
    		}
		}
	}
	
	
	/**
	 * След рендиране на List Summary-то
	 */
	static function on_AfterRenderListSummary($mvc, $tpl, $data)
    {
    	$res = array();
    	$double = cls::get('type_Double');
    	$double->params['decimals'] = 0;
    	$queryCopy = clone $data->query;
    	$queryCopy->show = array();
    	$queryCopy->groupBy = array();
    	$queryCopy->executed = FALSE;
    	
    	$fieldsArr = $mvc->selectFields("#summary");
    	$baseCurrency = acc_Periods::getBaseCurrencyCode();
    	while($rec = $queryCopy->fetch()){
    		static::prepareSummary($mvc, $fieldsArr, $rec, $res, $baseCurrency);
    	}
    	
    	$queryCopy->where("#state = 'draft'");
    	$draftCount = $queryCopy->count();
    	unset($queryCopy->where[(count($queryCopy->where) -1)]);
    	$queryCopy->where("#state = 'active' || #state = 'closed'");
    	$activeCount = $queryCopy->count();
    	
    	$res['countA'] = (object)array('caption' => tr('Активирани'), 'measure' => tr('бр'), 'quantity' => $activeCount);
    	$res['countB'] = (object)array('caption' => tr('Чернови'), 'measure' => tr('бр'), 'quantity' => $draftCount);
    	$tpl = static::renderSummary($res);
    	
    	return FALSE;
    }
    
    
    /**
     * Подготвя обощаващата информация
     * @param core_Mvc $mvc - Класа към който е прикачен плъгина
     * @param array $fld - Поле от модела имащо атрибут "summary"
     * @param stdClass $rec - Запис от модела
     * @param array $res - Масив в който ще върнем резултатите
     * @param string $currencyCode - основната валута за периода
     */
    private static function prepareSummary($mvc, $fieldsArr, $rec, &$res, $currencyCode)
    {
    	if(count($fieldsArr) == 0) return;
    	
    	foreach($fieldsArr as $fld){
    		if(!array_key_exists($fld->name, $res)) {
	    		$res[$fld->name] = (object)array('caption' => $fld->caption, 'measure' => '', 'number' => 0);
	    	}
	    			
	    	switch($fld->summary) {
	    		case "amount":
	    			if($currencyId = $rec->{$mvc->filterCurrencyField}){
	    				(is_numeric($currencyId)) ? $code = currency_Currencies::getCodeById($currencyId) : $code = $currencyId;
	    				$baseAmount = currency_CurrencyRates::convertAmount($rec->{$fld->name}, dt::now(), $code, NULL);
	    			} else {
	    				
	    				// Ако няма стойнсот за валутата по обобщение приемаме
	    				// че сумата е в основната валута за периода
	    				$baseAmount = $rec->{$fld->name};
	    			}
		    		
		    		$res[$fld->name]->amount += $baseAmount;
		    		$res[$fld->name]->measure = "<span class='cCode'>{$currencyCode}</span>";
	    			break;
	    		case "quantity":
	    			$res[$fld->name]->quantity += $rec->{$fld->name};
	    			$res[$fld->name]->measure = tr('бр');
	    			break;
	    	}
    	}
    }
    
    
   /**
    * Рендира обобщението
    * @param array $res - Масив от записи за показване
    * @return core_ET $tpl - Шаблон на обобщението
    */
    private static function renderSummary($res)
    {
    	// Зареждаме и подготвяме шаблона
    	$double = cls::get('type_Double');
    	$int = cls::get('type_Int');
    	$double->params['decimals'] = 2;
    	$tpl = new ET(tr('|*' . getFileContent("acc/plg/tpl/Summary.shtml")));
    	$rowTpl = $tpl->getBlock("ROW");
    	if(count($res)) {
	    	foreach($res as $row) {
	    		if($row->amount) {
	    			$row->amount = $double->toVerbal($row->amount);
	    		} elseif($row->quantity) {
	    			$row->quantity = $int->toVerbal($row->quantity);
	    		}
	    		
	    		$row->caption = str_replace("->", ": ", $row->caption);
	    		$rowTpl->placeObject($row);
	    		$rowTpl->removeBlocks();
	    		$rowTpl->append2master();
	    	}
    	}
    	$tpl->push('acc/plg/tpl/summary.css', 'CSS');
    	
    	return $tpl;
    }
 }