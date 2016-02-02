<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за направата на справка
 * по отклоняващи се цени в продажбите
 *
 * @category  bgerp
 * @package   sales
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_reports_OweInvoicesImpl extends frame_BaseDriver
{
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectSource = 'ceo,sales';
	
	
	/**
	 * Кои интерфейси имплементира
	 */
	public $interfaces = 'frame_ReportSourceIntf';
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Продажби » Задължения по фактури';
	
	
	/**
	 * Брой записи на страница
	 */
	public $listItemsPerPage = 50;

	
	/**
	 * Добавя полетата на вътрешния обект
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addEmbeddedFields(core_FieldSet &$form)
	{
		
		$form->FNC('contragentFolderId', 'key(mvc=doc_Folders,select=title)', 'caption=Контрагент,silent,input,mandatory');
		$form->FNC('from', 'date', 'caption=Към дата,silent,input');
		
		$this->invoke('AfterAddEmbeddedFields', array($form));
	}
	
	
	/**
	 * Подготвя формата за въвеждане на данни за вътрешния обект
	 *
	 * @param core_Form $form
	 */
	public function prepareEmbeddedForm(core_Form &$form)
	{
		$form->setOptions('contragentFolderId', array('' => '') + doc_Folders::getOptionsByCoverInterface('crm_ContragentAccRegIntf'));
		$form->setDefault('from',date('Y-m-01', strtotime("-1 months", dt::mysql2timestamp(dt::now()))));
		
		$this->invoke('AfterPrepareEmbeddedForm', array($form));
	}


	/**
	 * Проверява въведените данни
	 *
	 * @param core_Form $form
	 */
	public function checkEmbeddedForm(core_Form &$form)
	{

	}


	/**
	 * Подготвя вътрешното състояние, на база въведените данни
	 *
	 * @param core_Form $innerForm
	 */
	public function prepareInnerState()
	{
		// Подготвяне на данните
		$data = new stdClass();
		$data->recs = array();
		$data->sum = array();
		$data->contragent = new stdClass();
		
		$data->rec = $this->innerForm;
		$this->prepareListFields($data);
		
		// ако имаме избран книент
		if ($data->rec->contragentFolderId) {
			$contragentCls = doc_Folders::fetchField("#id = {$data->rec->contragentFolderId}", 'coverClass');
			$contragentId = doc_Folders::fetchField("#id = {$data->rec->contragentFolderId}", 'coverId');
			$data->contragent->titleLink = cls::get($contragentCls)->getShortHyperLink($contragentId);
		
			// всичко за контрагента
			$contragentRec = cls::get($contragentCls)->fetch($contragentId);
		}

		// записваме го в датата
		$data->contragent = $contragentRec;
		
		
		// търсим всички продажби, които са на този книент и са активни
		$querySales = sales_Sales::getQuery();
		$querySales->where("(#contragentClassId = '{$contragentCls}' AND #contragentId = '{$contragentId}') AND #state = 'active'");
		
		if (isset($data->rec->from))  { 
			// коя е текущата ни валута
			$currencyNow = currency_Currencies::fetchField(acc_Periods::getBaseCurrencyId($data->rec->from),'code');
		} else {
			$currencyNow = currency_Currencies::fetchField(acc_Periods::getBaseCurrencyId(dt::now()),'code');
		}
	
		while ($recSale = $querySales->fetch()) {
			$toPaid = '';
			// нефакторираното е разлика на доставеното и фактурираното
			$data->notInv += $recSale->amountDelivered - $recSale->amountInvoiced;
			
			// плащаме в датат валутата на сделката
			$data->currencyId = $recSale->currencyId;
		
			// ако имаме едно ниво на толеранс от задължение > на 0,5
			if ($recSale->amountDelivered - $recSale->amountPaid >= '0.5') {
				
				// то ще търсим всички фактури
				// които са в нишката на тази продажба
				// и са активни
				$queryInvoices = sales_Invoices::getQuery();
				$queryInvoices->where("#threadId = '{$recSale->threadId}' AND #state = 'active'");
				$queryInvoices->orderBy("#date", "DESC");

				// платеното е разлика на достовеното и салдото
				$paid = $recSale->amountDelivered - $recSale->amountBl;

				while ($invRec = $queryInvoices->fetch()){
				 
				    // платеното е разлика на достовеното и салдото
				    $paid =  $recSale->amountDelivered - $recSale->amountBl;
				    // сумата на фактурата с ДДС е суматана на факурата и ДДС стойността
				    $amountVat =  $invRec->dealValue + $invRec->vatAmount;
				    // имаме една чек сума, която е по-малкото от двете числа:
				    // платено и сумата на фактурата
				    $checkSum =  min($paid,$amountVat);
				    
				    if(!$toPaid && $paid  !=  '0') {
    				    $toPaid = abs($paid - $amountVat);
    				        // ако нищо не е платено по тази сделка
    				        // дължимата сума е сумата по фактура
    				} elseif ($paid  ==  '0') {
    				    $toPaid = $amountVat;
    				        // на всяка следваща стъпка, остатъка намалява с
    				        // чек сумата
    				} else {	
    				    $toPaid = abs($toPaid - $checkSum); 
    				}
    				    // ако дължимата сума е около 0
    				    // или стойноста на фактурата съвпадне с чек сумата
    				    // игнорираме тези редове
    				if (round($toPaid,2) == 0) {
    				        continue;
    				} else {
    				       //if ($checkSum == $amountVat) continue;
    				}
					
					// правим рековете
					$data->recs[] = (object) array ("contragentCls" => $contragentCls,
													'contragentId' => $contragentId,
													'eic'=> $contragentRec->vatId,
							                        'currencyId' => $recSale->currencyId,
													'invId'=>$invRec->id,
													'date'=>$invRec->date,
													'number'=>$invRec->number,
													'amountVat'=> $amountVat,
													'amountRest'=> $toPaid ,
													'paymentState'=>$recSale->paymentState,
							                        'dueDate'=>$invRec->dueDate
					);
				}
			}
		}

        foreach ($data->recs as $rec) { 
        	
        	if ($rec->dueDate == NULL || $rec->dueDate < dt::now()) { 
        		$rec->amount = $rec->amountRest;
        	} else {
        	   unset($rec->amountRest);
        	}
        
        	if ($rec->currencyId != $currencyNow) {
        		$rec->amountVat = currency_CurrencyRates::convertAmount($rec->amountVat, $rec->date, $currencyNow, $rec->currencyId);
        		$rec->amountRest = currency_CurrencyRates::convertAmount($rec->amountRest, $rec->date, $currencyNow, $rec->currencyId);
        		$rec->amount = currency_CurrencyRates::convertAmount($rec->amount, $rec->date, $currencyNow, $rec->currencyId);
        	} 

        }
        
        if (isset ($data->notInv)) { 
        	if ($data->currencyId != $currencyNow) {
        		$data->notInv = currency_CurrencyRates::convertAmount($data->notInv, $data->rec->from, $currencyNow, $data->currencyId);
        	}
        }
        
        $data->sum = new stdClass();
        foreach ($data->recs as $currRec) { 
        	
        	$data->sum->amountVat += $currRec->amountVat;
        	$data->sum->toPaid += $currRec->amountRest;
        	$data->sum->currencyId = $currRec->currencyId;

        	if ($currRec->dueDate == NULL || $currRec->dueDate < dt::now()) { 
        		$data->sum->arrears += $currRec->amountRest;
        	}
        	
        }

		return $data;
	}
	
	
	/**
	 * След подготовката на показването на информацията
	 */
	public static function on_AfterPrepareEmbeddedData($mvc, &$res)
	{
		// Подготвяме страницирането
		$data = $res;
		 
		$pager = cls::get('core_Pager',  array('pageVar' => 'P_' .  $mvc->EmbedderRec->that,'itemsPerPage' => $mvc->listItemsPerPage));
		 
		$pager->itemsCount = count($data->recs, COUNT_RECURSIVE);
		$data->pager = $pager;
		
		$data->summary = new stdClass();
		
		$Double = cls::get('type_Double');
		$Double->params['decimals'] = 2; 
		
		if(count($data->recs)){
			// правим обобщения ред в разбираем за човека вид
			$data->summary  = (object) array('currencyId' => $data->sum->currencyId,
					'amountInv' =>$Double->toVerbal($data->sum->amountVat),
					'amountToPaid' => $Double->toVerbal($data->sum->toPaid),
					'amountArrears' => $Double->toVerbal($data->sum->arrears)
			);
	
			foreach ($data->recs as $rec) {
				if(!$pager->isOnPage()) continue;
		
				$row = $mvc->getVerbal($rec);
				
				// добавяме на редовете със сума и съответната валута
				$row->amountVat =
				"<div>
					<span class='cCode'>$data->currencyId</span> 
                    <span>$row->amountVat</span>
				</div>";
				
				$row->amountRest =
				"<div>
					<span class='cCode'>$data->currencyId</span>
				 	<span>$row->amountRest</span>
				</div>";
		
				$dueDate = sales_Invoices::fetchField($rec->invId,dueDate);
				
				if ($dueDate == NULL || $dueDate < dt::now()) { 
					$row->amount = 
					"<div>
					<span class='cCode'>$data->currencyId</span>
					<span style='color:red'>$row->amount</span></b>
					</div>";
				} else {
					unset ($row->amount);
					$data->summary->amountArrears -= $row->amount;
				}
		
				$data->rows[] = $row;
			}
		}

		// правим един служебен ред за нефактурирано
		if($data->sum->notInv){

				$row = new stdClass();
				$row->contragent = "--------";
				$row->eic = "--------";
				$row->date = "--------";
				$row->number = "Нефактурирано";
				$amountVat = $Double->toVerbal($data->sum->notInv);
				$row->amountVat = 
				"<div>
						<span class='cCode'>$data->currencyId</span>
						<span>$amountVat</span></b>
				</div>";
				
				$data->rows[] = $row;

			// ако нямаме обобщен ред
			if (!$data->summary) {
				// си правим един, които да съдържа нефактурираното
				// и валуюитата на сделката
				$data->summary  = (object) array('amountInv' => $Double->toVerbal($data->sum->notInv),
												 'currencyId'=>$data->currencyId
				);
            // ако вече има
			} else {

				// добавяме нафактурираното към сумата на вече намерените 
				$data->summary->amountInv += $data->sum->notInv;
				$data->summary->amountInv = $Double->toVerbal($data->summary->amountInv);
				$data->summary->currencyId = $data->currencyId;
			}
		}

		$res = $data;
	}
	
	
	/**
	 * Връща шаблона на репорта
	 *
	 * @return core_ET $tpl - шаблона
	 */
	public function getReportLayout_()
	{
		$tpl = getTplFromFile('sales/tpl/OweInvoiceLayout.shtml');
		 
		return $tpl;
	}
	
	
	/**
	 * Рендира вградения обект
	 *
	 * @param stdClass $data
	 */
	public function renderEmbeddedData(&$embedderTpl, $data)
	{

		if(empty($data)) return;
  
    	$tpl = $this->getReportLayout();
    	
    	$tpl->replace($this->getReportTitle(), 'TITLE');
    
    	$form = cls::get('core_Form');
    
    	$this->addEmbeddedFields($form);
    
    	$form->rec = $data->rec;
    	$form->class = 'simpleForm';

    	$tpl->replace($data->contragent->titleLink, 'contragent');
    	$tpl->replace($data->contragent->vatId, 'eic');
    	
    	$tpl->placeObject($data->rec);

    	$f = cls::get('core_FieldSet');
   
    	$f->FLD('date', 'date');
    	$f->FLD('number', 'int');
    	$f->FLD('amountVat', 'double');
    	$f->FLD('amountRest', 'double');
    	$f->FLD('amount', 'double');
    	$f->FLD('paymentState', 'varchar');
    	
    	$table = cls::get('core_TableView', array('mvc' => $f));
    	$tpl->append($table->get($data->rows, $data->listFields), 'CONTENT');

        if (count($data->summary) ) {
	    	if(count($data->rows) == 1){
	    		$data->summary->colspan = count($data->listFields)-4;
	    		$afterRow = new core_ET("<tr  style = 'background-color: #eee'><td colspan=[#colspan#]><b>" . tr('ОБЩО') . "</b></td><td style='text-align:right'><span class='cCode'>[#currencyId#]</span>&nbsp;<b>[#amountInv#]</b></td><td style='text-align:right'><td style='text-align:right'></td><td style='text-align:right'></td></tr>");
	    		 
	    		$afterRow->placeObject($data->summary);
	    		
	    	} elseif (count($data->rows)  > 1) {
	    		$data->summary->colspan = count($data->listFields)-4;
	    		$afterRow = new core_ET("<tr  style = 'background-color: #eee'><td colspan=[#colspan#]><b>" . tr('ОБЩО') . "</b></td><td style='text-align:right'><span class='cCode'>[#currencyId#]</span>&nbsp;<b>[#amountInv#]</b></td><td style='text-align:right'><span class='cCode'>[#currencyId#]</span>&nbsp;<b>[#amountToPaid#]</b></td><!--ET_BEGIN contragent--><td style='text-align:right;color:red'><span class='cCode'>[#currencyId#]</span>&nbsp;<b>[#amountArrears#]</b></td><td style='text-align:right'></td></tr>");
	    		 
	    		$afterRow->placeObject($data->summary);
    		}
        }

    	if (count($data->rows)){
    		$tpl->append($afterRow, 'ROW_AFTER');
    	}
    	
    	if($data->pager){
    		$tpl->append($data->pager->getHtml(), 'PAGER');
    	}
		
		$embedderTpl->append($tpl, 'data');
	}
	
	
	/**
	 * Подготвя хедърите на заглавията на таблицата
	 */
	protected function prepareListFields_(&$data)
	{
		$data->listFields = array(
				'date' => 'Фактура->Дата',
				'number' => 'Фактура->Номер',
				'amountVat' => 'Фактура->Сума',
				'amountRest' => 'Фактура->Остатък',
				'amount' => 'Фактура->Просрочие',
				'paymentState' => 'Състояние',
		);
	}
	
	
	/**
	 * Вербалното представяне на ред от таблицата
	 */
	private function getVerbal($rec)
	{
		$RichtextType = cls::get('type_Richtext');
		$Double = cls::get('type_Double');
		$Double->params['decimals'] = 2;
		$VatType = cls::get('drdata_VatType');
	
		$row = new stdClass();

		$row->contragent = cls::get($rec->contragentCls)->getShortHyperLink($rec->contragentId);
		$row->eic = $VatType->toVerbal($rec->eic) ;
		
		if ($rec->date) {
	    	$row->date = dt::mysql2verbal($rec->date, 'd.m.Y');
		}
	    
		if ($rec->number) {
			$number = str_pad($rec->number, '10', '0', STR_PAD_LEFT);
			$row->number = ht::createLink($number,array('sales_Invoices','single', $rec->invId),FALSE, array('ef_icon' => 'img/16/invoice.png'));
		}
		$row->amountVat = $Double->toVerbal($rec->amountVat);
		$row->amountRest = $Double->toVerbal($rec->amountRest);
	    $row->amount = $Double->toVerbal($rec->amount);

	    $state = array('pending' => "Чакащо", 'overdue' => "Просроченo", 'paid' => "Платенo", 'repaid' => "Издължено");
	 
	    $row->paymentState = $state[$rec->paymentState];
	
		return $row;
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
	
	
	/**
	 * Връща дефолт заглавието на репорта
	 */
	public function getReportTitle()
	{
		$explodeTitle = explode(" » ", $this->title);
		 
		$title = tr("|{$explodeTitle[1]}|*");
	
		return $title;
	}
	
	
	/**
	 * Ще се експортирват полетата, които се
	 * показват в табличния изглед
	 *
	 * @return array
	 */
	public function getExportFields ()
	{

		$exportFields['date']  = 'Дата';
		$exportFields['number']  = 'Номер';
		$exportFields['amountVat']  = 'Сума';
		$exportFields['amountRest']  = 'Остатък';
		$exportFields['amount']  = 'Просрочие';
		$exportFields['paymentState']  = 'Състояние';

	
		return $exportFields;
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
             redirect(array($this), FALSE, "|Броят на заявените записи за експорт надвишава максимално разрешения|* - " . $conf->EF_MAX_EXPORT_CNT, 'error');
         }

         $csv = "";

         $rowContragent = "Клиент: " . $this->innerState->contragent->name ."\n";
         $rowContragent .=  "ЗДДС № / EIC: " . $this->innerState->contragent->vatId;
         foreach ($exportFields as $caption) {
             $header .=  $caption. ',';
         }

         
         if(count($this->innerState->recs)) {
			foreach ($this->innerState->recs as $id => $rec) {
				
				$rCsv = $this->generateCsvRows($rec);

				
				$csv .= $rCsv;
				$csv .=  "\n";
		
			}

			$csv = $rowContragent. "\n" . $header . "\n" . $csv;
	    } 

        return $csv;
	}

	
	/**
	 * Ще направим row-овете в CSV формат
	 *
	 * @return string $rCsv
	 */
	protected function generateCsvRows_($rec)
	{
	
		$exportFields =  $this->getExportFields();
		$rec = self::getVerbal($rec);

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
					$rCsv .= $value .  ",";
	
				} else {
					$rCsv .= '' . ",";
				}
			}
		}
		
		return $rCsv;
	}
}