<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за направата на справка на баланса
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_HistoryReportImpl extends frame_BaseDriver
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'acc_HistoryReport';
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectSource = 'ceo, acc';
	
	
	/**
	 * Кои интерфейси имплементира
	 */
	public $interfaces = 'frame_ReportSourceIntf';
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Счетоводство->Хронология на аналитична сметка';
	
	
	/**
	 * Мениджъра на хронологията 
	 * 
	 * @param acc_BalanceHistory $History
	 */
	private $History;
	
	
	/**
	 * Параметър по подразбиране
	 */
	function init($params = array())
	{
		$this->History = cls::get('acc_BalanceHistory');
	}
	
	
	/**
	 * Добавя полетата на вътрешния обект
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addEmbeddedFields(core_Form &$form)
	{
		$form->FLD('accountId', 'acc_type_Account(allowEmpty)', 'input,caption=Сметка,silent,mandatory,removeAndRefreshForm=ent1Id|ent2Id|ent3Id|orderField|orderBy|');
		$form->FLD('fromDate', 'date(allowEmpty)', 'caption=От,input,mandatory');
		$form->FLD('toDate', 'date(allowEmpty)', 'caption=До,input,mandatory');
		$form->FLD('isGrouped', 'varchar', 'caption=Групиране');
		$form->setOptions('isGrouped', array('' => '', 'yes' => 'Да', 'no' => 'Не'));
		
		$orderFields = ",valior=Вальор,docId=Документ,debitQuantity=Дебит»К-во,debitAmount=Дебит»Сума,creditQuantity=Кредит»К-во,creditAmount=Кредит»Сума,blQuantity=Остатък»К-во,blAmount=Остатък»Сума";
		
		$form->FLD('orderField', "enum({$orderFields})", 'caption=Подредба->По,formOrder=110000');
		$form->FLD('orderBy', 'enum(,asc=Въздходящ,desc=Низходящ)', 'caption=Подредба->Тип,formOrder=110001');
		
		$this->invoke('AfterAddEmbeddedFields', array($form));
	}
	
	
	/**
	 * Подготвя формата за въвеждане на данни за вътрешния обект
	 *
	 * @param core_Form $form
	 */
	public function prepareEmbeddedForm(core_Form &$form)
	{
		$op = acc_Periods::getPeriodOptions();
		 
		$form->setSuggestions('fromDate', array('' => '') + $op->fromOptions);
		$form->setSuggestions('toDate', array('' => '') + $op->toOptions);
		 
		if($form instanceof core_Form){
			$form->input();
		}
		 
		if(isset($form->rec->accountId)){
			 
			$accInfo = acc_Accounts::getAccountInfo($form->rec->accountId);
			 
			foreach (range(1, 3) as $i){
				if(isset($accInfo->groups[$i])){
					$gr = $accInfo->groups[$i];
					$form->FNC("ent{$i}Id", "acc_type_Item(lists={$gr->rec->num}, allowEmpty)", "caption=Избор на пера->{$gr->rec->name},input,mandatory");
				} else {
					$form->FNC("ent{$i}Id", "int", "");
				}
			}
		}
		
		$this->invoke('AfterPrepareEmbeddedForm', array($form));
	}


	/**
	 * Проверява въведените данни
	 *
	 * @param core_Form $form
	 */
	public function checkEmbeddedForm(core_Form &$form)
	{
		if($form->isSubmitted()){
			if($form->rec->toDate < $form->rec->fromDate){
				$form->setError('to, from', 'Началната дата трябва да е по малка от крайната');
			}
			
			if($form->rec->orderField == ''){
				unset($form->rec->orderField);
			}
			
			if($form->rec->orderBy == ''){
				unset($form->rec->orderBy);
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
		// Подготвяне на данните
		$filter = $this->innerForm;
		
		$data = new stdClass();
		$accNum = acc_Accounts::fetchField($filter->accountId, 'num');
		 
		$data->rec = new stdClass();
		$data->rec->accountId = $filter->accountId;
		$data->rec->ent1Id = $filter->ent1Id;
		$data->rec->ent2Id = $filter->ent2Id;
		$data->rec->ent3Id = $filter->ent3Id;
		$data->rec->accountNum = $accNum;
		
		acc_BalanceDetails::requireRightFor('history', $data->rec);
		 
		$balanceRec = $this->History->getBalanceBetween($filter->fromDate, $filter->toDate);
		 
		$data->balanceRec = $balanceRec;
		$data->fromDate = $filter->fromDate;
		$data->toDate = $filter->toDate;
		$data->isGrouped = ($filter->isGrouped != 'no') ? 'yes' : 'no';
		
		$data->orderField = $this->innerForm->orderField;
		$data->orderBy = $this->innerForm->orderBy;
		
		$this->History->prepareHistory($data);
		 
		return $data;
	}
	
	
	/**
	 * След подготовката на показването на информацията
	 */
	public static function on_AfterPrepareEmbeddedData($mvc, &$res)
	{
		$mvc->History->prepareRows($res);
	}
	
	
	/**
	 * Рендира вградения обект
	 *
	 * @param stdClass $data
	 */
	public function renderEmbeddedData($data)
	{
		$tpl = $this->History->renderHistory($data);
		$tpl->replace($this->title, 'TITLE');
		
		return $tpl;
	}


	/**
	 * Добавяме полета за търсене
	 *
	 * @see frame_BaseDriver::alterSearchKeywords()
	 */
	public function alterSearchKeywords(&$searchKeywords)
	{
		if(!empty($this->innerForm)){
			$newKeywords = '';
			$newKeywords .= acc_Accounts::getVerbal($this->innerForm->accountId, 'title');
			$newKeywords .= " " . acc_Accounts::getVerbal($this->innerForm->accountId, 'num');
			
			foreach (range(1, 3) as $i){
				if(!empty($this->innerForm->{"ent{$i}Id"})){
					$newKeywords .= " " . acc_Items::getVerbal($this->innerForm->{"ent{$i}Id"}, 'title');
				}
			}
			
			$searchKeywords .= " " . plg_Search::normalizeText($newKeywords);
		}
	}
	
	
	/**
	 * Скрива полетата, които потребител с ниски права не може да вижда
	 *
	 * @param stdClass $data
	 */
	public function hidePriceFields()
	{
		$innerState = &$this->innerState;
		
		foreach (array('baseAmount', 'baseQuantity', 'blQuantity', 'blAmount') as $fld){
			unset($innerState->row->$fld);
		}
		
		unset($innerState->recs);
	}
	
	
	/**
	 * Коя е най-ранната дата на която може да се активира документа
	 */
	public function getEarlyActivation()
	{
		$activateOn = "{$this->innerForm->toDate} 23:59:59";
		 
		return $activateOn;
	}
}