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
		while ($recS = $querySales->fetch()) {
            $recSaleArr[] = $recS;
            $dateArr[] = $recS->valior;
		}
		
		if(is_array($recSaleArr)) {
		    $date = min($dateArr);
		    $last3Months = dt::addMonths(-3, $date);
		    list($y,$m,$d) = explode("-", $last3Months);
		    $firstDayAfter3Months = "{$y}-{$m}-01";
		}
		
		foreach ($recSaleArr as $recSale) {
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
			$cDate = $firstDayAfter3Months;
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

                if($amountVat < 0){
                    $amountRest = $amountVat;
                } else {
                    $amountRest = 0;
                }
                    
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
													'amountRest'=> $amountRest ,
													'paymentState'=>$recSale->paymentState,
							                        'dueDate'=>$invRec->dueDate
					);
				$invCntArr[$saleItem->id] = $invCnt++;
			}
		}

        foreach ($data->recs as $id => $rec) { 
        
        	// ако имаме повече от една фактура в сделката
        	if($invCntArr[$rec->saleId] > 1) { 

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
        	
        	if ($currencyNow != $rec->currencyId && isset($rec->rate)) { 
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
        
        $values = $data->recs;
        $toPaid = 0;
        for($line = 0; $line < count($values); $line++) {
            if($line !== 0) continue;
        
            for($i = $line; $i < count($values); $i+=1) {
                
                if($data->recs[$i]->currencyId != $currencyNow && isset($data->recs[$i]->rate) ){
                 
                    $p = $paid[$data->recs[$i]->saleId]['creditAmount'] / $data->recs[$i]->rate;
                } else {
                    $p = $paid[$data->recs[$i]->saleId]['creditAmount'];
                }
               
                // разпределяме платеното по фактури
                if($data->recs[$i]->saleId == $data->recs[$i+1]->saleId) {
                   
                    if($paid[$data->recs[$i]->saleId]['creditAmount']  >  '0') { 
                        $toPaid = $data->recs[$i]->amountVat - $p;
                        $toPaid = round($toPaid, 2); 
                    } else {
                        $toPaid = $data->recs[$i]->amountVat;
                        $toPaid = round($toPaid, 2);
                    }
                   
                    // ако е фактура
                    if($data->recs[$i]->invType == 'invoice') { 
                        if($toPaid >= 0) {
                            $data->recs[$i]->amountRest = $toPaid;
                            $data->recs[$i+1]->amountRest = $data->recs[$i+1]->amountVat;
 
                            if(count($values) % 2 != 0) {
                                $data->recs[$i+2]->amountRest = $data->recs[$i+2]->amountVat;
                            }
                   
                        } else {  
                          
                            $data->recs[$i]->amountRest = 0;
                            if($data->recs[$i+1]->amountVat > 0) {
                                $data->recs[$i+1]->amountRest = $data->recs[$i+1]->amountVat + $toPaid;
                            } else {  
                                $data->recs[$i+1]->amountRest = $data->recs[$i+1]->amountVat;
                               // $data->recs[$i+2]->amountRest = $data->recs[$i+2]->amountVat + $toPaid;
                            }
                        }
                    // ако е известие
                    // TODO как ще се разпределя лащането?
                    } else {  
                       
                        if($data->recs[$i]->amountVat <=0 ) { 
                           
                            $data->recs[$i]->amountRest = $data->recs[$i]->amountVat;
                        }
       
                    }
                }
            }
        }

        $data->sum = new stdClass(); 
        foreach ($data->recs as $currRec) { 
        	
        	$data->sum->amountVat += $currRec->amountVat;
        	$data->sum->toPaid += $currRec->amountRest;
        	$data->sum->currencyId = $currRec->currencyId;

        	if ($currRec->dueDate == NULL || $currRec->dueDate < $data->rec->from) { 
        	    $currRec->amount = $currRec->amountRest;
        		$data->sum->arrears += $currRec->amount;
        	} else {
        	   $currRec->amount = 0;
        	   $data->sum->arrears = 0;
        	}
        }

        
        usort($data->recs, function($a, $b)
        {
            return strcmp($a->date, $b->date);
        });
        
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
	
	function act_Test()
	{
	    $a = "AAA
AARP
ABARTH
ABB
ABBOTT
ABBVIE
ABC
ABLE
ABOGADO
ABUDHABI
AC
ACADEMY
ACCENTURE
ACCOUNTANT
ACCOUNTANTS
ACO
ACTIVE
ACTOR
AD
ADAC
ADS
ADULT
AE
AEG
AERO
AETNA
AF
AFAMILYCOMPANY
AFL
AFRICA
AG
AGAKHAN
AGENCY
AI
AIG
AIGO
AIRBUS
AIRFORCE
AIRTEL
AKDN
AL
ALFAROMEO
ALIBABA
ALIPAY
ALLFINANZ
ALLSTATE
ALLY
ALSACE
ALSTOM
AM
AMERICANEXPRESS
AMERICANFAMILY
AMEX
AMFAM
AMICA
AMSTERDAM
ANALYTICS
ANDROID
ANQUAN
ANZ
AO
AOL
APARTMENTS
APP
APPLE
AQ
AQUARELLE
AR
ARAMCO
ARCHI
ARMY
ARPA
ART
ARTE
AS
ASDA
ASIA
ASSOCIATES
AT
ATHLETA
ATTORNEY
AU
AUCTION
AUDI
AUDIBLE
AUDIO
AUSPOST
AUTHOR
AUTO
AUTOS
AVIANCA
AW
AWS
AX
AXA
AZ
AZURE
BA
BABY
BAIDU
BANAMEX
BANANAREPUBLIC
BAND
BANK
BAR
BARCELONA
BARCLAYCARD
BARCLAYS
BAREFOOT
BARGAINS
BASEBALL
BASKETBALL
BAUHAUS
BAYERN
BB
BBC
BBT
BBVA
BCG
BCN
BD
BE
BEATS
BEAUTY
BEER
BENTLEY
BERLIN
BEST
BESTBUY
BET
BF
BG
BH
BHARTI
BI
BIBLE
BID
BIKE
BING
BINGO
BIO
BIZ
BJ
BLACK
BLACKFRIDAY
BLANCO
BLOCKBUSTER
BLOG
BLOOMBERG
BLUE
BM
BMS
BMW
BN
BNL
BNPPARIBAS
BO
BOATS
BOEHRINGER
BOFA
BOM
BOND
BOO
BOOK
BOOKING
BOOTS
BOSCH
BOSTIK
BOSTON
BOT
BOUTIQUE
BOX
BR
BRADESCO
BRIDGESTONE
BROADWAY
BROKER
BROTHER
BRUSSELS
BS
BT
BUDAPEST
BUGATTI
BUILD
BUILDERS
BUSINESS
BUY
BUZZ
BV
BW
BY
BZ
BZH
CA
CAB
CAFE
CAL
CALL
CALVINKLEIN
CAM
CAMERA
CAMP
CANCERRESEARCH
CANON
CAPETOWN
CAPITAL
CAPITALONE
CAR
CARAVAN
CARDS
CARE
CAREER
CAREERS
CARS
CARTIER
CASA
CASE
CASEIH
CASH
CASINO
CAT
CATERING
CATHOLIC
CBA
CBN
CBRE
CBS
CC
CD
CEB
CENTER
CEO
CERN
CF
CFA
CFD
CG
CH
CHANEL
CHANNEL
CHASE
CHAT
CHEAP
CHINTAI
CHLOE
CHRISTMAS
CHROME
CHRYSLER
CHURCH
CI
CIPRIANI
CIRCLE
CISCO
CITADEL
CITI
CITIC
CITY
CITYEATS
CK
CL
CLAIMS
CLEANING
CLICK
CLINIC
CLINIQUE
CLOTHING
CLOUD
CLUB
CLUBMED
CM
CN
CO
COACH
CODES
COFFEE
COLLEGE
COLOGNE
COM
COMCAST
COMMBANK
COMMUNITY
COMPANY
COMPARE
COMPUTER
COMSEC
CONDOS
CONSTRUCTION
CONSULTING
CONTACT
CONTRACTORS
COOKING
COOKINGCHANNEL
COOL
COOP
CORSICA
COUNTRY
COUPON
COUPONS
COURSES
CR
CREDIT
CREDITCARD
CREDITUNION
CRICKET
CROWN
CRS
CRUISE
CRUISES
CSC
CU
CUISINELLA
CV
CW
CX
CY
CYMRU
CYOU
CZ
DABUR
DAD
DANCE
DATA
DATE
DATING
DATSUN
DAY
DCLK
DDS
DE
DEAL
DEALER
DEALS
DEGREE
DELIVERY
DELL
DELOITTE
DELTA
DEMOCRAT
DENTAL
DENTIST
DESI
DESIGN
DEV
DHL
DIAMONDS
DIET
DIGITAL
DIRECT
DIRECTORY
DISCOUNT
DISCOVER
DISH
DIY
DJ
DK
DM
DNP
DO
DOCS
DOCTOR
DODGE
DOG
DOHA
DOMAINS
DOT
DOWNLOAD
DRIVE
DTV
DUBAI
DUCK
DUNLOP
DUNS
DUPONT
DURBAN
DVAG
DVR
DZ
EARTH
EAT
EC
ECO
EDEKA
EDU
EDUCATION
EE
EG
EMAIL
EMERCK
ENERGY
ENGINEER
ENGINEERING
ENTERPRISES
EPOST
EPSON
EQUIPMENT
ER
ERICSSON
ERNI
ES
ESQ
ESTATE
ESURANCE
ET
EU
EUROVISION
EUS
EVENTS
EVERBANK
EXCHANGE
EXPERT
EXPOSED
EXPRESS
EXTRASPACE
FAGE
FAIL
FAIRWINDS
FAITH
FAMILY
FAN
FANS
FARM
FARMERS
FASHION
FAST
FEDEX
FEEDBACK
FERRARI
FERRERO
FI
FIAT
FIDELITY
FIDO
FILM
FINAL
FINANCE
FINANCIAL
FIRE
FIRESTONE
FIRMDALE
FISH
FISHING
FIT
FITNESS
FJ
FK
FLICKR
FLIGHTS
FLIR
FLORIST
FLOWERS
FLY
FM
FO
FOO
FOOD
FOODNETWORK
FOOTBALL
FORD
FOREX
FORSALE
FORUM
FOUNDATION
FOX
FR
FREE
FRESENIUS
FRL
FROGANS
FRONTDOOR
FRONTIER
FTR
FUJITSU
FUJIXEROX
FUN
FUND
FURNITURE
FUTBOL
FYI
GA
GAL
GALLERY
GALLO
GALLUP
GAME
GAMES
GAP
GARDEN
GB
GBIZ
GD
GDN
GE
GEA
GENT
GENTING
GEORGE
GF
GG
GGEE
GH
GI
GIFT
GIFTS
GIVES
GIVING
GL
GLADE
GLASS
GLE
GLOBAL
GLOBO
GM
GMAIL
GMBH
GMO
GMX
GN
GODADDY
GOLD
GOLDPOINT
GOLF
GOO
GOODHANDS
GOODYEAR
GOOG
GOOGLE
GOP
GOT
GOV
GP
GQ
GR
GRAINGER
GRAPHICS
GRATIS
GREEN
GRIPE
GROUP
GS
GT
GU
GUARDIAN
GUCCI
GUGE
GUIDE
GUITARS
GURU
GW
GY
HAIR
HAMBURG
HANGOUT
HAUS
HBO
HDFC
HDFCBANK
HEALTH
HEALTHCARE
HELP
HELSINKI
HERE
HERMES
HGTV
HIPHOP
HISAMITSU
HITACHI
HIV
HK
HKT
HM
HN
HOCKEY
HOLDINGS
HOLIDAY
HOMEDEPOT
HOMEGOODS
HOMES
HOMESENSE
HONDA
HONEYWELL
HORSE
HOSPITAL
HOST
HOSTING
HOT
HOTELES
HOTMAIL
HOUSE
HOW
HR
HSBC
HT
HTC
HU
HUGHES
HYATT
HYUNDAI
IBM
ICBC
ICE
ICU
ID
IE
IEEE
IFM
IKANO
IL
IM
IMAMAT
IMDB
IMMO
IMMOBILIEN
IN
INDUSTRIES
INFINITI
INFO
ING
INK
INSTITUTE
INSURANCE
INSURE
INT
INTEL
INTERNATIONAL
INTUIT
INVESTMENTS
IO
IPIRANGA
IQ
IR
IRISH
IS
ISELECT
ISMAILI
IST
ISTANBUL
IT
ITAU
ITV
IVECO
IWC
JAGUAR
JAVA
JCB
JCP
JE
JEEP
JETZT
JEWELRY
JIO
JLC
JLL
JM
JMP
JNJ
JO
JOBS
JOBURG
JOT
JOY
JP
JPMORGAN
JPRS
JUEGOS
JUNIPER
KAUFEN
KDDI
KE
KERRYHOTELS
KERRYLOGISTICS
KERRYPROPERTIES
KFH
KG
KH
KI
KIA
KIM
KINDER
KINDLE
KITCHEN
KIWI
KM
KN
KOELN
KOMATSU
KOSHER
KP
KPMG
KPN
KR
KRD
KRED
KUOKGROUP
KW
KY
KYOTO
KZ
LA
LACAIXA
LADBROKES
LAMBORGHINI
LAMER
LANCASTER
LANCIA
LANCOME
LAND
LANDROVER
LANXESS
LASALLE
LAT
LATINO
LATROBE
LAW
LAWYER
LB
LC
LDS
LEASE
LECLERC
LEFRAK
LEGAL
LEGO
LEXUS
LGBT
LI
LIAISON
LIDL
LIFE
LIFEINSURANCE
LIFESTYLE
LIGHTING
LIKE
LILLY
LIMITED
LIMO
LINCOLN
LINDE
LINK
LIPSY
LIVE
LIVING
LIXIL
LK
LOAN
LOANS
LOCKER
LOCUS
LOFT
LOL
LONDON
LOTTE
LOTTO
LOVE
LPL
LPLFINANCIAL
LR
LS
LT
LTD
LTDA
LU
LUNDBECK
LUPIN
LUXE
LUXURY
LV
LY
MA
MACYS
MADRID
MAIF
MAISON
MAKEUP
MAN
MANAGEMENT
MANGO
MARKET
MARKETING
MARKETS
MARRIOTT
MARSHALLS
MASERATI
MATTEL
MBA
MC
MCD
MCDONALDS
MCKINSEY
MD
ME
MED
MEDIA
MEET
MELBOURNE
MEME
MEMORIAL
MEN
MENU
MEO
METLIFE
MG
MH
MIAMI
MICROSOFT
MIL
MINI
MINT
MIT
MITSUBISHI
MK
ML
MLB
MLS
MM
MMA
MN
MO
MOBI
MOBILE
MOBILY
MODA
MOE
MOI
MOM
MONASH
MONEY
MONSTER
MONTBLANC
MOPAR
MORMON
MORTGAGE
MOSCOW
MOTO
MOTORCYCLES
MOV
MOVIE
MOVISTAR
MP
MQ
MR
MS
MSD
MT
MTN
MTPC
MTR
MU
MUSEUM
MUTUAL
MV
MW
MX
MY
MZ
NA
NAB
NADEX
NAGOYA
NAME
NATIONWIDE
NATURA
NAVY
NBA
NC
NE
NEC
NET
NETBANK
NETFLIX
NETWORK
NEUSTAR
NEW
NEWHOLLAND
NEWS
NEXT
NEXTDIRECT
NEXUS
NF
NFL
NG
NGO
NHK
NI
NICO
NIKE
NIKON
NINJA
NISSAN
NISSAY
NL
NO
NOKIA
NORTHWESTERNMUTUAL
NORTON
NOW
NOWRUZ
NOWTV
NP
NR
NRA
NRW
NTT
NU
NYC
NZ
OBI
OBSERVER
OFF
OFFICE
OKINAWA
OLAYAN
OLAYANGROUP
OLDNAVY
OLLO
OM
OMEGA
ONE
ONG
ONL
ONLINE
ONYOURSIDE
OOO
OPEN
ORACLE
ORANGE
ORG
ORGANIC
ORIENTEXPRESS
ORIGINS
OSAKA
OTSUKA
OTT
OVH
PA
PAGE
PAMPEREDCHEF
PANASONIC
PANERAI
PARIS
PARS
PARTNERS
PARTS
PARTY
PASSAGENS
PAY
PCCW
PE
PET
PF
PFIZER
PG
PH
PHARMACY
PHILIPS
PHONE
PHOTO
PHOTOGRAPHY
PHOTOS
PHYSIO
PIAGET
PICS
PICTET
PICTURES
PID
PIN
PING
PINK
PIONEER
PIZZA
PK
PL
PLACE
PLAY
PLAYSTATION
PLUMBING
PLUS
PM
PN
PNC
POHL
POKER
POLITIE
PORN
POST
PR
PRAMERICA
PRAXI
PRESS
PRIME
PRO
PROD
PRODUCTIONS
PROF
PROGRESSIVE
PROMO
PROPERTIES
PROPERTY
PROTECTION
PRU
PRUDENTIAL
PS
PT
PUB
PW
PWC
PY
QA
QPON
QUEBEC
QUEST
QVC
RACING
RADIO
RAID
RE
READ
REALESTATE
REALTOR
REALTY
RECIPES
RED
REDSTONE
REDUMBRELLA
REHAB
REISE
REISEN
REIT
RELIANCE
REN
RENT
RENTALS
REPAIR
REPORT
REPUBLICAN
REST
RESTAURANT
REVIEW
REVIEWS
REXROTH
RICH
RICHARDLI
RICOH
RIGHTATHOME
RIL
RIO
RIP
RMIT
RO
ROCHER
ROCKS
RODEO
ROGERS
ROOM
RS
RSVP
RU
RUHR
RUN
RW
RWE
RYUKYU
SA
SAARLAND
SAFE
SAFETY
SAKURA
SALE
SALON
SAMSCLUB
SAMSUNG
SANDVIK
SANDVIKCOROMANT
SANOFI
SAP
SAPO
SARL
SAS
SAVE
SAXO
SB
SBI
SBS
SC
SCA
SCB
SCHAEFFLER
SCHMIDT
SCHOLARSHIPS
SCHOOL
SCHULE
SCHWARZ
SCIENCE
SCJOHNSON
SCOR
SCOT
SD
SE
SEAT
SECURE
SECURITY
SEEK
SELECT
SENER
SERVICES
SES
SEVEN
SEW
SEX
SEXY
SFR
SG
SH
SHANGRILA
SHARP
SHAW
SHELL
SHIA
SHIKSHA
SHOES
SHOP
SHOPPING
SHOUJI
SHOW
SHOWTIME
SHRIRAM
SI
SILK
SINA
SINGLES
SITE
SJ
SK
SKI
SKIN
SKY
SKYPE
SL
SLING
SM
SMART
SMILE
SN
SNCF
SO
SOCCER
SOCIAL
SOFTBANK
SOFTWARE
SOHU
SOLAR
SOLUTIONS
SONG
SONY
SOY
SPACE
SPIEGEL
SPOT
SPREADBETTING
SR
SRL
SRT
ST
STADA
STAPLES
STAR
STARHUB
STATEBANK
STATEFARM
STATOIL
STC
STCGROUP
STOCKHOLM
STORAGE
STORE
STREAM
STUDIO
STUDY
STYLE
SU
SUCKS
SUPPLIES
SUPPLY
SUPPORT
SURF
SURGERY
SUZUKI
SV
SWATCH
SWIFTCOVER
SWISS
SX
SY
SYDNEY
SYMANTEC
SYSTEMS
SZ
TAB
TAIPEI
TALK
TAOBAO
TARGET
TATAMOTORS
TATAR
TATTOO
TAX
TAXI
TC
TCI
TD
TDK
TEAM
TECH
TECHNOLOGY
TEL
TELECITY
TELEFONICA
TEMASEK
TENNIS
TEVA
TF
TG
TH
THD
THEATER
THEATRE
TIAA
TICKETS
TIENDA
TIFFANY
TIPS
TIRES
TIROL
TJ
TJMAXX
TJX
TK
TKMAXX
TL
TM
TMALL
TN
TO
TODAY
TOKYO
TOOLS
TOP
TORAY
TOSHIBA
TOTAL
TOURS
TOWN
TOYOTA
TOYS
TR
TRADE
TRADING
TRAINING
TRAVEL
TRAVELCHANNEL
TRAVELERS
TRAVELERSINSURANCE
TRUST
TRV
TT
TUBE
TUI
TUNES
TUSHU
TV
TVS
TW
TZ
UA
UBANK
UBS
UCONNECT
UG
UK
UNICOM
UNIVERSITY
UNO
UOL
UPS
US
UY
UZ
VA
VACATIONS
VANA
VANGUARD
VC
VE
VEGAS
VENTURES
VERISIGN
VERSICHERUNG
VET
VG
VI
VIAJES
VIDEO
VIG
VIKING
VILLAS
VIN
VIP
VIRGIN
VISA
VISION
VISTA
VISTAPRINT
VIVA
VIVO
VLAANDEREN
VN
VODKA
VOLKSWAGEN
VOLVO
VOTE
VOTING
VOTO
VOYAGE
VU
VUELOS
WALES
WALMART
WALTER
WANG
WANGGOU
WARMAN
WATCH
WATCHES
WEATHER
WEATHERCHANNEL
WEBCAM
WEBER
WEBSITE
WED
WEDDING
WEIBO
WEIR
WF
WHOSWHO
WIEN
WIKI
WILLIAMHILL
WIN
WINDOWS
WINE
WINNERS
WME
WOLTERSKLUWER
WOODSIDE
WORK
WORKS
WORLD
WOW
WS
WTC
WTF
XBOX
XEROX
XFINITY
XIHUAN
XIN
XN--11B4C3D
XN--1CK2E1B
XN--1QQW23A
XN--30RR7Y
XN--3BST00M
XN--3DS443G
XN--3E0B707E
XN--3OQ18VL8PN36A
XN--3PXU8K
XN--42C2D9A
XN--45BRJ9C
XN--45Q11C
XN--4GBRIM
XN--54B7FTA0CC
XN--55QW42G
XN--55QX5D
XN--5SU34J936BGSG
XN--5TZM5G
XN--6FRZ82G
XN--6QQ986B3XL
XN--80ADXHKS
XN--80AO21A
XN--80AQECDR1A
XN--80ASEHDB
XN--80ASWG
XN--8Y0A063A
XN--90A3AC
XN--90AE
XN--90AIS
XN--9DBQ2A
XN--9ET52U
XN--9KRT00A
XN--B4W605FERD
XN--BCK1B9A5DRE4C
XN--C1AVG
XN--C2BR7G
XN--CCK2B3B
XN--CG4BKI
XN--CLCHC0EA0B2G2A9GCD
XN--CZR694B
XN--CZRS0T
XN--CZRU2D
XN--D1ACJ3B
XN--D1ALF
XN--E1A4C
XN--ECKVDTC9D
XN--EFVY88H
XN--ESTV75G
XN--FCT429K
XN--FHBEI
XN--FIQ228C5HS
XN--FIQ64B
XN--FIQS8S
XN--FIQZ9S
XN--FJQ720A
XN--FLW351E
XN--FPCRJ9C3D
XN--FZC2C9E2C
XN--FZYS8D69UVGM
XN--G2XX48C
XN--GCKR3F0F
XN--GECRJ9C
XN--GK3AT1E
XN--H2BRJ9C
XN--HXT814E
XN--I1B6B1A6A2E
XN--IMR513N
XN--IO0A7I
XN--J1AEF
XN--J1AMH
XN--J6W193G
XN--JLQ61U9W7B
XN--JVR189M
XN--KCRX77D1X4A
XN--KPRW13D
XN--KPRY57D
XN--KPU716F
XN--KPUT3I
XN--L1ACC
XN--LGBBAT1AD8J
XN--MGB9AWBF
XN--MGBA3A3EJT
XN--MGBA3A4F16A
XN--MGBA7C0BBN0A
XN--MGBAAM7A8H
XN--MGBAB2BD
XN--MGBAI9AZGQP6J
XN--MGBAYH7GPA
XN--MGBB9FBPOB
XN--MGBBH1A71E
XN--MGBC0A9AZCG
XN--MGBCA7DZDO
XN--MGBERP4A5D4AR
XN--MGBI4ECEXP
XN--MGBPL2FH
XN--MGBT3DHD
XN--MGBTX2B
XN--MGBX4CD0AB
XN--MIX891F
XN--MK1BU44C
XN--MXTQ1M
XN--NGBC5AZD
XN--NGBE9E0A
XN--NODE
XN--NQV7F
XN--NQV7FS00EMA
XN--NYQY26A
XN--O3CW4H
XN--OGBPF8FL
XN--P1ACF
XN--P1AI
XN--PBT977C
XN--PGBS0DH
XN--PSSY2U
XN--Q9JYB4C
XN--QCKA1PMC
XN--QXAM
XN--RHQV96G
XN--ROVU88B
XN--S9BRJ9C
XN--SES554G
XN--T60B56A
XN--TCKWE
XN--TIQ49XQYJ
XN--UNUP4Y
XN--VERMGENSBERATER-CTB
XN--VERMGENSBERATUNG-PWB
XN--VHQUV
XN--VUQ861B
XN--W4R85EL8FHU5DNRA
XN--W4RS40L
XN--WGBH1C
XN--WGBL6A
XN--XHQ521B
XN--XKC2AL3HYE2A
XN--XKC2DL3A5EE0H
XN--Y9A3AQ
XN--YFRO4I67O
XN--YGBI2AMMX
XN--ZFR164B
XPERIA
XXX
XYZ
YACHTS
YAHOO
YAMAXUN
YANDEX
YE
YODOBASHI
YOGA
YOKOHAMA
YOU
YOUTUBE
YT
YUN
ZA
ZAPPOS
ZARA
ZERO
ZIP
ZIPPO
ZM
ZONE
ZUERICH
ZW";
	    bp(strtolower($a));
	}
}