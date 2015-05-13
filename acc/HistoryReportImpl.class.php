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
	public $title = 'Счетоводство»Хронология на аналитична сметка';
	
	
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
					$form->FNC("ent{$i}Id", "acc_type_Item(lists={$gr->rec->num}, allowEmpty, select=titleNum)", "caption=Пера->{$gr->rec->name},input,mandatory");
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
		if(!is_subclass_of($mvc, __CLASS__)){
			$mvc->History->prepareRows($res);
		}
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
	

	/**
	 * Ако имаме в url-то export създаваме csv файл с данните
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $rec
	 */
	public function exportCsv()
	{

		$exportFields = $this->getExportFields();
	
		$conf = core_Packs::getConfig('core');
	
		if (count($this->innerState->recs) > $conf->EF_MAX_EXPORT_CNT) {
			redirect(array($this), FALSE, "Броят на заявените записи за експорт надвишава максимално разрешения|* - " . $conf->EF_MAX_EXPORT_CNT, 'error');
		}
	
		$csv = "";
	
		// генериран хедър
		$header = $this->generateHeader($this->innerState->rec)->header;
		// генериран нулев ред
		$zeroRow = $this->generateCsvRows($this->innerState->zeroRec);
		// генериран първи ред
		$lastRow = $this->generateCsvRows($this->innerState->lastRec);
		
		if(count($this->innerState->recs)) {
			foreach (array_reverse($this->innerState->recs, TRUE) as $id => $rec) {

				$rCsv = $this->generateCsvRows($rec);

				$csv .= $rCsv;
				$csv .=  "\n";
		
			}
			
			$csv = $header . "\n" . $lastRow .  "\n" . $csv . $zeroRow;
	    } else {
	    	$csv = $header . "\n" . $lastRow . "\n" . $zeroRow;
	    }

		return $csv;
	}
	
	
	/**
	 * Ще се експортирват полетата, които се
	 * показват в табличния изглед
	 *
	 * @return array
	 */
	public function getExportFields ()
	{

		$exportFields['valior']  = "Вальор";
		$exportFields['docId']  = "Документ";
		$exportFields['reason']  = "Забележки";
		$exportFields['debitQuantity']  = "Дебит - количество";
		$exportFields['debitAmount']  = "Дебит";
		$exportFields['creditQuantity']  = "Кредит - количество";
		$exportFields['creditAmount']  = "Кредит";
		$exportFields['blQuantity']  = "Остатък - количество";
		$exportFields['blAmount']  = "Остатък";
		
		return $exportFields;
	}
	
	
	/**
	 * Ще направим нови row-ове за експорта.
	 * Ще се обработват променливи от тип
	 * double, key, keylist, date
	 *
	 * @return std Class $rows
	 */
	public function prepareCsvRows ($rec)
	{

		
		// новите ни ролове
		$rows = new stdClass();
		

		// за всеки един запис
		foreach ($rec as $field => $value) { 

			// ако е doubele
			if (in_array($field ,array('baseQuantity', 'baseAmount', 'debitQuantity', 'debitAmount', 'creditQuantity', 'creditAmount', 'blQuantity', 'blAmount'))) {
		   
				$value = $this->toCsvFormatDouble($value);

			}
			
			// ако е class
			try{
				$Class = cls::get($rec['docType']);
				$rows->docId = html2text_Converter::toRichText($Class->getShortHyperLink($rec['docId']));
				$rows->reason = html2text_Converter::toRichText($Class->getContoReason($rec['docId'], $rec['reasonCode']));
			} catch(core_exception_Expect $e){
				if(is_numeric($rec['docId'])){
					$rows->docId = "<span style='color:red'>" . tr("Проблем при показването") . "</span>";
				} else {
					$rows->docId = $rec['docId'];
				}
			}
			
			// ако е date
			if ($field == 'valior') {
				$value = $this->toCsvFormatData($rec['valior']);
			}
	
			$rows->{$field} = $value;
		}
	
		// ако имаме попълнено поле за контрагент или продукт
		// искаме то да илезе с вербалното си име
		foreach (range(1, 3) as $i) {
			if(!empty($rows->{"ent{$i}Id"})){
				$rows->{"ent{$i}Id"} = acc_Items::getVerbal($rec->{"ent{$i}Id"}, 'title');
			}
		}

		return $rows;
	}

	
	/**
	 * Ще направим заглавито на колонките
	 * според това дали ще има скрити полета
	 *
	 * @return stdClass
	 */
	public function generateHeader($rec)
	{
		$exportFields = $this->getExportFields();
	
		if ($rec->baseAmount == $rec->baseQuantity && $rec->debitQuantity == $rec->debitAmount && $rec->creditQuantity == $rec->creditAmount && $rec->blQuantity == $rec->blAmount) { //bp();
			unset ($exportFields['debitQuantity']);
			unset ($exportFields['creditQuantity']);
			unset ($exportFields['blQuantity']);			
		}
		
		foreach ($exportFields as $caption) {
			$header .= "," . $caption;
		}
			
		return (object) array('header' => $header, 'exportFields' => $exportFields);
	}
	
	
	/**
	 * Ще направим row-овете в CSV формат 
	 *
	 * @return string $rCsv
	 */
	public function generateCsvRows($rec)
	{

		$exportFields = $this->generateHeader($this->innerState->rec)->exportFields;
		$rec = frame_CsvLib::prepareCsvRows($rec);
		
		$rCsv = '';

		foreach ($rec as $field => $value) {
			$rCsv = '';
			
			foreach ($exportFields as $field => $caption) {
			
				if ($rec->{$field}) {
			
					$value = $rec->{$field};
					$value = html2text_Converter::toRichText($value);
					// escape
					if (preg_match('/\\r|\\n|,|"/', $value)) {
						$value = '"' . str_replace('"', '""', $value) . '"';
					}
					$rCsv .= "," . $value;
			
				} else {
					$rCsv .= "," . '';
				}
			}
		}
		//bp($rCsv);
		return $rCsv;
	}

}