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
		$form->FNC('notInv', 'enum(yes=Да, no=Не)', 'caption=Без нефактурирано,silent,input=none');
		
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
			// всичко за контрагента
			$contragentRec = cls::get($contragentCls)->fetch($contragentId);
			// записваме го в датата
			$data->contragent = $contragentRec;
			$data->contragent->titleLink = cls::get($contragentCls)->getShortHyperLink($contragentId);
		}
		
		// търсим всички продажби, които са на този книент и са активни
		$querySales = sales_Sales::getQuery();
	
		// коя е текущата ни валута
		$currencyNow = currency_Currencies::fetchField(acc_Periods::getBaseCurrencyId($data->rec->from),'code');
		$querySales->where("(#contragentClassId = '{$contragentCls}' AND #contragentId = '{$contragentId}') AND #state = 'active'");		
		
		// за всяка продажба
		while ($recSale = $querySales->fetch()) {

			if ($recSale->amountDelivered !== NULL && $recSale->amountInvoiced !== NULL) {
    			// нефакторираното е разлика на доставеното и фактурираното
    			$data->notInv += $recSale->amountDelivered - $recSale->amountInvoiced;
			}

			// плащаме във валутата на сделката
			$data->currencyId = $recSale->currencyId;

			// то ще търсим всички фактури
			// които са в нишката на тази продажба
			// и са активни
			$queryInvoices = sales_Invoices::getQuery();
			$queryInvoices->where("#threadId = '{$recSale->threadId}' AND #state = 'active' AND #date <= '{$data->rec->from}'");
			$queryInvoices->orderBy("#date", "ASC");

			// перот на селката
			$saleItem = acc_Items::fetchItem('sales_Sales', $recSale->id);
			// перото на контрагента
			$contragentItem = acc_Items::fetchItem($contragentCls, $contragentId);
			// перото на валутата
			$currencyItem = acc_Items::fetchItem('currency_Currencies', currency_Currencies::getIdByCode($recSale->currencyId));

			// от началото на активния счетоводен период
			$cDate = acc_Periods::forceActive()->start;
			// броим фактурите в сделката
			$invCnt = 1;
			while ($invRec = $queryInvoices->fetch()){
                // до края на избраната дата в отчета
                while($cDate < dt::addDays(1, $data->rec->from)){
                    
                    foreach (array('411', '412') as $accId) {
                        $Balance = new acc_ActiveShortBalance(array('from' => $cDate,
    				        'to' => $cDate,
    				        'accs' => $accId,
    				        'item1' => $contragentItem->id,
    				        'item2' => $saleItem->id,
    				        'item3' => $currencyItem->id,
    				        'strict' => TRUE,
    				        'cacheBalance' => FALSE));
    				    	
    				    // Изчлисляваме в момента, какъв би бил крания баланс по сметката в края на деня
                        $Balance = $Balance->getBalanceBefore($accId);
    				    $balHistory = acc_ActiveShortBalance::getBalanceHystory($accId, $cDate, $cDate, $contragentItem->id, $saleItem->id, $currencyItem->id);

    				    if(is_array($balHistory['history'])) {
    				        foreach($balHistory['history'] as $history) { 
    				            // платено по сделката
        				        $paid[$saleItem->id]['creditAmount'] += $history['creditAmount'];
        				        // сделката
        				        $paid[$saleItem->id]['debitAmount'] += $history['debitAmount'];
    				        }
    				    }
                    }
				    
				    $cDate = dt::addDays(1, $cDate);
                }
	
				// сумата на фактурата с ДДС е суматана на факурата и ДДС стойността
                $amountVat =  $invRec->dealValue - $invRec->discountAmount + $invRec->vatAmount;
                    
                // фактурираното то всяка сделка (сумарно от всички фактури)
				$amountVatArr[$saleItem->id] +=  $amountVat; 

				// правим рековете
				$data->recs[] = (object) array ("contragentCls" => $contragentCls,
													'contragentId' => $contragentId,
													'eic'=> $contragentRec->vatId,
							                        'currencyId' => $recSale->currencyId,
													'invId'=>$invRec->id,
				                                    'invType'=>$invRec->type,
													'date'=>$invRec->date,
													'number'=>$invRec->number,
					                                'displayRate'=>$invRec->displayRate,
					                                'rate'=>$invRec->rate,
					                                'saleId'=>$saleItem->id,
													'amountVat'=> $amountVat,
													'amountRest'=> 0 ,
													'paymentState'=>$recSale->paymentState,
							                        'dueDate'=>$invRec->dueDate
					);
				$invCntArr[$saleItem->id] = $invCnt++;
			}
		}

        foreach ($data->recs as $id => $rec) { 

        	// ако имаме повече от една фактура в сделката
        	if($invCntArr[$rec->saleId] > 1) { 
        	    continue;

            // само една фактура
        	} else {
        	    // ако сделката не е фактурирана цялата
        	    if($amountVatArr[$rec->saleId] != $rec->amountVat) {
        	        // от сумата на сделката вадим фактурираното и платеното
        	        $rec->amountRest = $amountVatArr[$rec->saleId] - $rec->amountVat - $paid[$rec->saleId]['creditAmount'];
        	    // ако сделката е фактурирана изцяло
        	    } else {
        	        // от сумата й вадим платеното
        	        $rec->amountRest = round($rec->amountVat,2) - round($paid[$rec->saleId]['creditAmount'],2); 
        	    }
        	}
        
        	if ($rec->currencyId != $currencyNow) { 
                $rec->amountVat /= ($rec->displayRate) ? $rec->displayRate : $rec->rate;
        		$rec->amountVat = round($rec->amountVat, 2);
        		
        		$rec->amountRest /= ($rec->displayRate) ? $rec->displayRate : $rec->rate;
        		$rec->amountRest = round($rec->amountRest, 2);
        		
        		$rec->amount /= ($rec->displayRate) ? $rec->displayRate : $rec->rate;
        		$rec->amount = round($rec->amount, 2);
        		
        	} 

        }
      
        if (isset ($data->notInv)) { 
        	if ($data->currencyId != $currencyNow) {
        		$data->notInv = currency_CurrencyRates::convertAmount($data->notInv, $data->rec->from, $currencyNow, $data->currencyId);
        	}
        }

        // разпределяме платеното по фактури
        for($i = 0; $i <= count($data->recs)-1; $i++) {
            
            if($data->recs[$i]->saleId == $data->recs[$i+1]->saleId) {
                $toPaid = "";
                if($paid[$data->recs[$i]->saleId]['creditAmount']  !=  '0') {
                    $toPaid = $data->recs[$i]->amountVat - $paid[$data->recs[$i]->saleId]['creditAmount'];
                    $toPaid = round($toPaid, 2);
                } else {
                    $toPaid = $data->recs[$i]->amountVat;
                }
                
                // ако е фактура
                if($data->recs[$i]->invType == 'invoice') {
                    if($toPaid >= 0) {
                        $data->recs[$i]->amountRest = $toPaid;
                        $data->recs[$i+1]->amountRest = $data->recs[$i+1]->amountVat;
                    } else { 
                        $data->recs[$i]->amountRest = 0;
                        $data->recs[$i+1]->amountRest = $data->recs[$i+1]->amountVat + $toPaid;
                    }
                // ако е известие
                // TODO как ще се разпределя лащането?
                } else {
                    $data->recs[$i]->amountRest = $data->recs[$i]->amountVat;
                    $data->recs[$i+1]->amountRest = $data->recs[$i+1]->amountVat;
                }
            }

            // проверяваме дали остатъка е просрочен
            if ($data->recs[$i]->dueDate == NULL || $data->recs[$i]->dueDate < $data->rec->from) {
                $data->recs[$i]->amount = $data->recs[$i]->amountRest;
            } else {
                $data->recs[$i]->amount = 0;
            }
            
            if($data->recs[$i]->amountRest == 0) {
                //unset($data->recs[$i]);
            }
        }
       
        $data->sum = new stdClass(); 
        foreach ($data->recs as $currRec) { 
        	
        	$data->sum->amountVat += $currRec->amountVat;
        	$data->sum->toPaid += $currRec->amountRest;
        	$data->sum->currencyId = $currRec->currencyId;

        	if ($currRec->dueDate == NULL || $currRec->dueDate < $data->rec->from) { 
        		$data->sum->arrears += $currRec->amount;
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
					$row->amount = 
					"<div>
					<span class='cCode'>$data->currencyId</span>
					<span>$row->amount</span></b>
					</div>";
					$data->summary->amountArrears = 0;
				}
		
				$data->rows[] = $row;
			}
		}

		// правим един служебен ред за нефактурирано
		if($data->notInv && $mvc->innerForm->notInv == "no"){

				$row = new stdClass();
				$row->contragent = "--------";
				$row->eic = "--------";
				$row->date = "--------";
				$row->number = "Нефактурирано";
				$amountVat = $Double->toVerbal($data->notInv);
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
				$data->summary  = (object) array('amountInv' => $Double->toVerbal($data->notInv),
												 'currencyId'=>$data->currencyId
				);
            // ако вече има
			} else {

				// добавяме нафактурираното към сумата на вече намерените 
				$data->summary->amountInv += $data->notInv;
				//$data->summary->amountInv = $Double->toVerbal($data->summary->amountInv);
				$data->summary->currencyId = $data->currencyId;
			}
		}
		
		// правим обобщения ред в разбираем за човека вид
		$data->summary  = (object) array('currencyId' => $data->currencyId,
		    'amountInv' =>$Double->toVerbal($data->sum->amountVat),
		    'amountToPaid' => $Double->toVerbal($data->sum->toPaid),
		    'amountArrears' => $Double->toVerbal($data->sum->arrears)
		);
		//bp($res);
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

    	$tpl->replace($data->contragent->titleLink, 'contragent');
    	$tpl->replace($data->contragent->vatId, 'eic');
    	
    	$from = dt::mysql2verbal($data->rec->from, 'd.m.Y');
    	$tpl->replace($from, 'from');
    	
    	$tpl->placeObject($data->rec);

    	$f = $this->getFields();
    	
    	$table = cls::get('core_TableView', array('mvc' => $f));
    	//bp($data->rows, $data->listFields);
    	$tpl->append($table->get($data->rows, $data->listFields), 'CONTENT');

        if (count($data->summary) ) {

	       $data->summary->colspan = count($data->listFields)-3;
	       $afterRow = new core_ET("<tr  style = 'background-color: #eee'><td colspan=[#colspan#]><b>" . tr('ОБЩО') . "</b></td><td style='text-align:right'><span class='cCode'>[#currencyId#]</span>&nbsp;<b>[#amountInv#]</b></td><td style='text-align:right'><span class='cCode'>[#currencyId#]</span>&nbsp;<b>[#amountToPaid#]</b></td><!--ET_BEGIN amountArrears--><td style='text-align:right;color:red'><span class='cCode'>[#currencyId#]</span>&nbsp;<b>[#amountArrears#]</b><!--ET_END amountArrears--></td></tr>");
	    		
	       $afterRow->placeObject($data->summary);

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
				'date' => 'Дата',
		        'dueDate' => 'Падеж',
				'number' => 'Номер',
				'amountVat' => 'Сума',
				'amountRest' => 'Остатък',
				'amount' => 'Просрочие',
				//'paymentState' => 'Състояние',
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
		
		if ($rec->dueDate) {
		    $row->dueDate = dt::mysql2verbal($rec->dueDate, 'd.m.Y');
		}
	    
		if ($rec->number) {
			$number = str_pad($rec->number, '10', '0', STR_PAD_LEFT);
			$url = toUrl(array('sales_Invoices','single', $rec->invId),'absolute');
			$row->number = ht::createLink($number,$url,FALSE, array('ef_icon' => 'img/16/invoice.png'));
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
	 * Ако имаме в url-то export създаваме csv файл с данните
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $rec
	 */
	public function exportCsv()
	{

         $conf = core_Packs::getConfig('core');

         if (count($this->innerState->recs) > $conf->EF_MAX_EXPORT_CNT) {
             redirect(array($this), FALSE, "|Броят на заявените записи за експорт надвишава максимално разрешения|* - " . $conf->EF_MAX_EXPORT_CNT, 'error');
         }

         $csv = "";

         $rowContragent = "Клиент: " . $this->innerState->contragent->name ."\n";
         $rowContragent .=  "ЗДДС № / EIC: " . $this->innerState->contragent->vatId."\n";
         $rowContragent .=  "Към дата: " . $this->innerForm->from;

         $fields = $this->getFields();
         $exportFields = $this->innerState->listFields;
         
         if(count($this->innerState->recs)) {
             foreach ($this->innerState->recs as $rec) {
                 $state = array('pending' => "Чакащо", 'overdue' => "Просроченo", 'paid' => "Платенo", 'repaid' => "Издължено");
                 
                 $rec->paymentState = $state[$rec->paymentState];
             }
             
             $csv = csv_Lib::createCsv($this->innerState->recs, $fields, $exportFields);
			 $csv = $rowContragent. "\n" . $csv;
	    } 

        return $csv;
	}
	
	
	/**
	 * Ще се експортирват полетата, които се
	 * показват в табличния изглед
	 *
	 * @return array
	 * @todo да се замести в кода по-горе
	 */
	protected function getFields_()
	{
	    // Кои полета ще се показват
	    $f = new core_FieldSet;
   
    	$f->FLD('date', 'date');
    	$f->FLD('dueDate', 'date');
    	$f->FLD('number', 'int');
    	$f->FLD('amountVat', 'double');
    	$f->FLD('amountRest', 'double');
    	$f->FLD('amount', 'double');
    	$f->FLD('paymentState', 'varchar');
	
	    return $f;
	}
}