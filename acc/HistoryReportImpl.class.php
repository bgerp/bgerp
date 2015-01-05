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
	public $title = 'Хронологична справка на аналитична сметка';
	
	
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
		$form->FLD('accountId', 'acc_type_Account(allowEmpty)', 'input,caption=Сметка,silent,mandatory', array('attr' => array('onchange' => "addCmdRefresh(this.form);this.form.submit()")));
		$form->FLD('fromDate', 'date(allowEmpty)', 'caption=От,input,mandatory');
		$form->FLD('toDate', 'date(allowEmpty)', 'caption=До,input,mandatory');
		$form->FLD('isGrouped', 'varchar', 'caption=Групиране');
		$form->setOptions('isGrouped', array('' => '', 'yes' => 'Да', 'no' => 'Не'));
	}
	
	
	/**
	 * Подготвя формата за въвеждане на данни за вътрешния обект
	 *
	 * @param core_Form $form
	 */
	public function prepareEmbeddedForm(core_Form &$form)
	{
		$op = $this->History->getBalancePeriods();
		 
		$form->setSuggestions('fromDate', array('' => '') + $op->fromOptions);
		$form->setSuggestions('toDate', array('' => '') + $op->toOptions);
		 
		if($form instanceof core_Form){
			$form->input();
		}
		 
		if(isset($form->rec->accountId)){
			
			if($form->rec->id){
				if(frame_Reports::fetchField($form->rec->id, 'filter')->accountId != $form->rec->accountId){
					unset($form->rec->ent1Id, $form->rec->ent2Id, $form->rec->ent3Id);
					Request::push(array('ent1Id' => NULL, 'ent2Id' => NULL, 'ent3Id' => NULL));
				}
			}
			 
			$accInfo = acc_Accounts::getAccountInfo($form->rec->accountId);
			 
			foreach (range(1, 3) as $i){
				if(isset($accInfo->groups[$i])){
					$gr = $accInfo->groups[$i];
					$form->FNC("ent{$i}Id", "acc_type_Item(lists={$gr->rec->num}, allowEmpty)", "caption=Избор на пера->{$gr->rec->name},input,mandatory");
				} else {
					$form->FNC("ent{$i}Id", "int", "");
					//$form->rec->{"ent{$i}Id"} = NULL;
				}
			}
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
			if($form->rec->toDate < $form->rec->fromDate){
				$form->setError('to, from', 'Началната дата трябва да е по малка от крайната');
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
		
		$this->History->prepareHistory($data);
		 
		return $data;
	}
	
	
	/**
	 * След подготовката на показването на информацията
	 */
	public function on_AfterPrepareEmbeddedData($mvc, &$res)
	{
		$this->History->prepareRows($res);
	}
	
	
	/**
	 * Рендира вградения обект
	 *
	 * @param stdClass $data
	 */
	public function renderEmbeddedData($data)
	{
		return $this->History->renderHistory($data);
	}


	/**
	 * Добавяме полета за търсене
	 *
	 * @see frame_BaseDriver::alterSearchKeywords()
	 */
	public function alterSearchKeywords(&$searchKeywords)
	{
		if(!empty($this->innerForm)){
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
		return $this->innerForm->toDate;
	}
}