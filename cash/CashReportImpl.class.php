<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за справка на движенията по каса
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cash_CashReportImpl extends frame_BaseDriver
{
    
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';
    
    
    /**
     * Заглавие
     */
    public $title = 'Финанси->Дневни обороти - каса';
    
    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 31;
    
    
    /**
     * Дефолт сметка
     */
    protected $defaultAccount = '501';
    
    
    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Fieldset $fieldset
     */
    public function addEmbeddedFields(core_Form &$form)
    {
    	$form->FLD('accountId', 'acc_type_Account(allowEmpty)', 'input=hidden,mandatory');
    	$form->FLD('from', 'date', 'caption=От,mandatory');
    	$form->FLD('to', 'date', 'caption=До');
    	
    	// Дефолтната сметка да е избрана по дефолт
    	$accId = acc_Accounts::getRecBySystemId($this->defaultAccount)->id;
    	$form->setDefault('accountId', $accId);
    	
    	// Номера на валутите и касите
    	$caseListRec = acc_Lists::fetchBySystemId('case')->num;
    	$curListRec = acc_Lists::fetchBySystemId('currencies')->num;
    	
    	// Показваме полета за избор на перо каса и валута
    	$form->FLD('caseItem', "acc_type_Item(lists={$caseListRec}, allowEmpty)", 'caption=Каса,mandatory');
    	$form->FLD('currencyItem', "acc_type_Item(lists={$curListRec}, allowEmpty)", 'caption=Валута,mandatory');
    
    	// Дефолтния период е в рамките на 1 седмица назаде
    	if(empty($form->rec->id)){
    		$today = dt::today();
    		$form->setDefault('from', dt::addDays(-7, $today));
    		$form->setDefault('to', $today);
    	}
    	
    	// Слагаме избраната каса, ако има такава
    	if($curCase = cash_Cases::getCurrent('id', FALSE)){
    		$caseItemId = acc_Items::fetchItem('cash_Cases', $curCase)->id;
    		$form->setDefault('caseItem', $caseItemId);
    	}
    }
    
    
    /**
     * Проверява въведените данни
     *
     * @param core_Form $form
     */
    public function checkEmbeddedForm(core_Form &$form)
    {
    	if($form->isSubmitted()){
    		if(isset($form->rec->to) && isset($form->rec->from)){
    			if($form->rec->to < $form->rec->from){
    				$form->setError('to, from', 'Началната дата трябва да е по малка от крайната');
    			}
    		}
    	}
    }
    
    
    /**
     * Подготвя вътрешното състояние, на база въведените данни
     *
     * @param core_Form $innerForm
     */
    public function prepareInnerState()
    {
    	$data = new stdClass();
    	$data->rec = $this->innerForm;
    	$data->recs = array();
    	if(empty($data->rec->to)){
    		$data->rec->to = $data->rec->from;
    	}
    	
    	// На коя позиция са валутата и касата в сметката
    	$cItemPosition = acc_Lists::getPosition($this->defaultAccount, 'cash_CaseAccRegIntf');
    	$currencyPosition = acc_Lists::getPosition($this->defaultAccount, 'currency_CurrenciesAccRegIntf');
    	
    	// Започваме да извличаме баланса от началната дата
    	// За всеки ден от периода намираме какви са салдата и движенията по касата и валутата
    	$curDate = $data->rec->from;
    	do{
    		$newRec = (object)array('date' => $curDate);
    		
    		// Намираме движенията по сметката за тези пера за тази дата
    		$accSysId = acc_Accounts::fetchField($data->rec->accountId, 'systemId');
    		$Balance = new acc_ActiveShortBalance(array('from' => $curDate, 'to' => $curDate, 'accs' => $accSysId, 'cacheBalance' => FALSE, "item{$cItemPosition}" => $data->rec->caseItem, "item{$currencyPosition}" => $data->rec->currencyItem));
    		$balance = $Balance->getBalance($accSysId);
    		
    		// Ако има баланс
    		if(count($balance)){
    			foreach ($balance as $b){
    				
    				// И в него участват касата и валутата
    				if(!($b->{"ent{$cItemPosition}Id"} == $data->rec->caseItem && $b->{"ent{$currencyPosition}Id"} == $data->rec->currencyItem)) continue;
    				
    				// Сабираме салдата и оборотите
    				foreach (array('baseQuantity', 'debitQuantity', 'creditQuantity', 'blQuantity') as $fld){
    					if(isset($b->$fld)){
    						$newRec->$fld += $b->$fld;
    					}
    				}
    			}
    		}
    		
    		// Добавяме към записите
    		$data->recs[] = $newRec;
    		
    		// Новата дата е един ден след текущата
    		$curDate = dt::addDays(1, $curDate);
    		$curDate = dt::verbal2mysql($curDate, FALSE);
    	
    		// Продължаваме докато текущата дата се изравни с крайната
    	} while($curDate <= $data->rec->to);
    	
    	// Връщаме данните
    	return $data;
    }
    
    
    /**
     * След подготовката на показването на информацията
    */
    public static function on_AfterPrepareEmbeddedData($mvc, &$data)
    {
    	$data->listFields = arr::make("date=Дата,baseQuantity=Начално, debitQuantity=Приход,creditQuantity=Разход,blQuantity=Остатък", TRUE);
    	$data->recs = array_reverse($data->recs, TRUE);
    	
    	// Ако има намерени записи
    	if(count($data->recs)){
    		
    		if(!Mode::is('printing')){
    			
    			// Подготвяме страницирането
    			$data->Pager = cls::get('core_Pager',  array('itemsPerPage' => $mvc->listItemsPerPage));
    			$data->Pager->itemsCount = count($data->recs);
    		}
    		
    		// За всеки запис
    		foreach ($data->recs as &$rec){
    			 
    			// Ако не е за текущата страница не го показваме
    			if(isset($data->Pager) && !$data->Pager->isOnPage()) continue;
    			 
    			// Вербално представяне на записа
    			$data->rows[] = $mvc->getVerbalRec($rec);
    		}
    	}
    }
    
    
    /**
     * Вербално представяне на групираните записи
     *
     * @param stdClass $rec - групиран запис
     * @return stdClass $row - вербален запис
     */
    private function getVerbalRec($rec)
    {
    	$row = new stdClass();
    	$Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
    	
    	$row->date = dt::mysql2verbal($rec->date, "d.m.Y");
    	
    	// Вербално представяне на сумите и к-та
    	foreach (array('baseQuantity', 'debitQuantity', 'creditQuantity', 'blQuantity') as $fld){
    		if(isset($rec->{$fld})){
    			$row->{$fld} = $Double->toVerbal($rec->{$fld});
    			if($rec->{$fld} < 0){
    				$row->{$fld} = "<span class='red'>{$row->{$fld}}</span>";
    			}
    		}
    	}
    		 
    	// Връщаме подготвеното вербално рпедставяне
    	return $row;
    }
    
    
    /**
     * Връща шаблона на репорта
     *
     * @return core_ET $tpl - шаблона
     */
    public function getReportLayout_()
    {
    	$tpl = getTplFromFile('cash/tpl/CaseReportDays.shtml');
    	 
    	return $tpl;
    }
    
    
    /**
     * Рендира вградения обект
     *
     * @param stdClass $data
     */
    public function renderEmbeddedData($data)
    {
    	if(empty($data)) return;
    	 
    	$tpl = $this->getReportLayout();
    	$tpl->replace($this->title, 'TITLE');
    	 
    	// Рендираме статичната форма
    	$this->prependStaticForm($tpl, 'FORM');
    	
    	// Рендираме таблицата с намерените записи
    	$tableMvc = new core_Mvc;
    	$tableMvc->FLD('baseQuantity', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('debitQuantity', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('creditQuantity', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('blQuantity', 'int', 'tdClass=accCell');
    	
    	$table = cls::get('core_TableView', array('mvc' => $tableMvc));
    	
    	$tpl->append($table->get($data->rows, $data->listFields), 'DETAILS');
    	
    	if($data->Pager){
    		$tpl->append($data->Pager->getHtml(), 'PAGER_TOP');
    		$tpl->append($data->Pager->getHtml(), 'PAGER_BOTTOM');
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Скрива полетата, които потребител с ниски права не може да вижда
     *
     * @param stdClass $data
     */
    public function hidePriceFields()
    {
    	$innerState = &$this->innerState;
    
    	unset($innerState->recs);
    }
    
    
    /**
     * Коя е най-ранната дата на която може да се активира документа
     */
    public function getEarlyActivation()
    {
    	$activateOn = "{$this->innerForm->to} 23:59:59";
    
    	return $activateOn;
    }
}