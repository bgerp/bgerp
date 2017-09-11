<?php



/**
 * Плъгин позволяващ на обикновена фактура да начислява или да приспада
 * ддс ако се очаква авансово плащане
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_plg_DpInvoice extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
    	if($mvc instanceof sales_Invoices || $mvc instanceof purchase_Invoices || $mvc instanceof sales_Proformas){
    		
    		// Сума на авансовото плащане (ако има)
	    	$mvc->FLD('dpAmount', 'double', 'caption=Авансово плащане->Сума,input=none,before=contragentName');
	    	
	    	// Операция с авансовото плащане начисляване/намаляване
	    	$mvc->FLD('dpOperation', 'enum(accrued=Начисляване, deducted=Приспадане, none=Няма)', 'caption=Авансово плащане->Операция,input=none,before=contragentName');
	    	$mvc->FLD('dpReason', 'text(rows=2)', 'caption=Аванс->Основание,after=amountDeducted,autohide');
    	}
    }
    
    
    /**
     * Извиква се след подготовка на формата във фактурата
     */
    public static function on_AfterPrepareDpInvoicePlg($mvc, &$res, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	// Ако е детайла на фактурата не правим нищо
        if(!($mvc instanceof sales_Invoices || $mvc instanceof purchase_Invoices || $mvc instanceof sales_Proformas)) return;
    	
        // Ако е ДИ или КИ не правим нищо
        if($rec->type != 'invoice') return;
       
        // Намиране на пораждащия се документ
        $origin         = doc_Threads::getFirstDocument($rec->threadId);
        if(!core_Cls::existsMethod($origin->getInstance(), 'getAggregateDealInfo')) return;
        $dealInfo       = $origin->getAggregateDealInfo();
        $form->dealInfo = $dealInfo;
        
        $unit = ($rec->vatRate == 'yes' || $rec->vatRate == 'separate') ? 'с ДДС' : 'без ДДС';
        
        $form->FNC('amountAccrued', 'double', "caption=Аванс->Начисляване,input,autohide,before=dpAmount,unit=|*{$rec->currencyId} |{$unit}|*");
        $form->FNC('amountDeducted', 'double', "caption=Аванс->Приспадане,input,autohide,before=dpAmount,unit=|*{$rec->currencyId} |{$unit}|*");
        
        if(empty($form->rec->id)){
        	
        	// Поставяне на дефолт стойностти
        	self::getDefaultDpData($form, $mvc);
        } else {
        	$Detail = cls::get($mvc->mainDetail);
        	
        	// Ако има детайл не показваме секцията за аванс
        	if($Detail->fetchField("#{$Detail->masterKey} = {$rec->id}", 'id')){
        		$form->setField('amountAccrued', 'input=none');
        	}
        	
        	// При приспадане ако има сума я показваме положителна
        	if($rec->dpOperation == 'deducted'){
        		$rec->dpAmount *= -1;
        	}
        }

        if(isset($rec->dpAmount)){
        	$dpAmount = $rec->dpAmount / $rec->rate;
        	$vat = acc_Periods::fetchByDate($rec->date)->vatRate;
        	if($rec->vatRate != 'yes' && $rec->vatRate != 'separate'){
        		$vat = 0;
        	}
        
        	$dpAmount += $dpAmount * $vat;
        	$dpAmount = round($dpAmount, 2);
        
        	if($rec->dpOperation == 'accrued'){
        		$form->setDefault('amountAccrued', $dpAmount);
        	} elseif($rec->dpOperation == 'deducted'){
        		$form->setDefault('amountDeducted', $dpAmount);
        	}
        }
        
        if(isset($form->rec->dpAmount)){
        	$dpAmount = round($form->rec->dpAmount / $form->rec->rate, 6);
        	if($dpAmount == 0){
        		unset($form->rec->dpAmount);
        		unset($form->rec->dpOperation);
        		return;
        	}
        	
        	$form->rec->dpAmount = $dpAmount;
        }
        
        if($form->rec->dpOperation == 'none'){
        	unset($form->rec->dpAmount);
        }
    }
    
    
    /**
     * Функция, която прихваща след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
    	if(empty($rec->dpAmount) || empty($rec->dpOperation)) return;
    	
    	// Ако потребителя не е в група доставчици го включваме
    	$rec = $mvc->fetchRec($rec);
    	
    	// Записване на основанието за аванс
    	if(empty($rec->dpReason)){
    		$rec->tplLang = $mvc->pushTemplateLg($rec->template);
    		$rec->dpReason = self::getReasonText($rec, $rec->dpOperation);
    		$mvc->save_($rec, 'dpReason');
    		core_Lg::pop($rec->tplLang);
    	}
    }
    
    
    /**
     * Подготвя дефолт стойностите за авансовите плащания
     * 
     * @param core_Form $form
     */
    private static function getDefaultDpData(core_Form &$form, $mvc)
    {
    	$rec = $form->rec;
    	
    	// Договореното до момента
    	$aggreedDp  = $form->dealInfo->get('agreedDownpayment');
    	$actualDp   = $form->dealInfo->get('downpayment');
    	$invoicedDp = $form->dealInfo->get('downpaymentInvoiced');
    	$deductedDp = $form->dealInfo->get('downpaymentDeducted');
    	
    	$downpayment = (empty($actualDp)) ? NULL : $actualDp;
    	
    	$flag = TRUE;
    	
    	// Ако е проформа
    	if($mvc instanceof sales_Proformas){
    		$accruedProformaRec = sales_Proformas::fetch("#threadId = {$rec->threadId} AND #state = 'active' AND #dpOperation = 'accrued'");
    		$hasDeductedProforma = sales_Proformas::fetchField("#threadId = {$rec->threadId} AND #state = 'active' AND #dpOperation = 'deducted'");
    		
    		// Ако има проформа за аванс и няма таква за приспадане, тази приспада
    		if(!empty($accruedProformaRec) && empty($hasDeductedProforma)){
    			
    			$dpAmount = (($accruedProformaRec->dealValue - $accruedProformaRec->discountAmount)+ $accruedProformaRec->vatAmount);
    			$dpAmount = core_Math::roundNumber($dpAmount);
    			$dpOperation = 'deducted';
    			$flag = FALSE;
    		}
    		
    		// Ако има проформа за начисляване на аванс и за приспадане, не задаваме дефолти
    		if($accruedProformaRec && $hasDeductedProforma) return;
    	}
    	
    	if($flag === TRUE){
    		if(!isset($downpayment)) {
    			$dpOperation = 'none';
    		
    			if(isset($invoicedDp) && ($invoicedDp - $deductedDp) > 0){
    				$dpAmount = $invoicedDp - $deductedDp;
    				$dpOperation = 'deducted';
    			}
    		} else {
    		
    			// Ако няма фактуриран аванс
    			if(empty($invoicedDp)){
    				if($flag === TRUE){
    					// Начисляване на аванса
    					$dpAmount = $downpayment;
    					$dpOperation = 'accrued';
    				}
    			} else {
    				 
    				// Ако има вече начислен аванс, по дефолт е приспадане със сумата за приспадане
    				$dpAmount = $invoicedDp - $deductedDp;
    				$dpOperation = 'deducted';
    			}
    		}
    	}
    	
    	$rate = ($form->rec->rate) ? $form->rec->rate : $form->dealInfo->get('rate');
    	
    	$dpAmount /= $rate;
    	$dpAmount = core_Math::roundNumber($dpAmount);
    	
    	// За проформи, Ако държавата не е България не предлагаме начисляване на ДДС
    	if(!($mvc instanceof sales_Proformas) && $form->rec->contragentCountryId != drdata_Countries::fetchField("#commonName = 'Bulgaria'")) return;
			
    	switch($dpOperation){
    		case 'accrued':
    			if(isset($dpAmount)){
    				$delivered = $form->dealInfo->get('deliveryAmount');
    				if(!empty($delivered)){
    					$dpOperation = 'none';
    					$form->setSuggestions('amountAccrued', array('' => '', "{$dpAmount}" => $dpAmount));
    				} else {
    					$form->setDefault('amountAccrued', $dpAmount);
    				}
    			}
    			break;
    		case 'deducted':
    			if($dpAmount){
    				$form->setDefault('amountDeducted', $dpAmount);
    			}
    			break;
    		case 'none';
    		if(isset($aggreedDp)){
    			$dpField = $form->getField('amountAccrued');
    			unset($dpField->autohide);
    				
    			$sAmount = core_Math::roundNumber($aggreedDp / $rate);
    			$suggestions = array('' => '', "{$sAmount}" => $sAmount);
    			$form->setSuggestions('amountAccrued', $suggestions);
    		}
    		break;
    	}
    		 
    	if($dpOperation){
    		$form->setDefault('dpOperation', $dpOperation);
    			
    		if($form->rec->dpOperation == 'accrued' && isset($form->rec->amountDeducted)){
    			unset($form->rec->amountDeducted);
    		}
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputDpInvoice($mvc, &$res, &$form)
    {
        // Ако сме в детайла пропускаме
    	if($mvc->Master) return;
    	
    	if(empty($form->dealInfo)) return;
    	
    	if ($form->isSubmitted()) {
    		
        	$rec = &$form->rec;
        	
        	$aggreedDp  = $form->dealInfo->get('agreedDownpayment');
	    	$actualDp   = $form->dealInfo->get('downpayment');
	    	$invoicedDp = $form->dealInfo->get('downpaymentInvoiced');
	    	$deductedDp = $form->dealInfo->get('downpaymentDeducted');
        	
	    	if(isset($rec->amountAccrued) && isset($rec->amountDeducted)){
	    		$form->setError('amountAccrued,amountDeducted', 'Не може едновременно да се начислява и да се приспада аванс');
	    		return;
	    	}
	    	
	    	$rec->dpAmount = ($rec->amountAccrued) ? $rec->amountAccrued : $rec->amountDeducted;
	    	$rec->dpOperation = 'none';
	    	$warningUnit = ($rec->vatRate != 'yes' && $rec->vatRate != 'separate') ? 'без ДДС' : 'с ДДС';
	    	
	    	if(isset($rec->amountAccrued)){
	    		$rec->dpOperation = 'accrued';
	    		
	    		$downpayment = (!isset($actualDp)) ? $aggreedDp  : $actualDp;
	    		if(empty($actualDp) && (!empty($invoicedDp) && !empty($deductedDp) && $invoicedDp == $deductedDp)){
	    			$downpayment = $actualDp;
	    		} else {
	    			$downpayment = $aggreedDp;
	    		}
	    		
	    		$downpayment = core_Math::roundNumber($downpayment / $rec->rate);
	    		if($rec->dpAmount > ($downpayment * 1.05 + 1)){
	    			$dVerbal = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($downpayment);
	    			$warning = ($downpayment === (double)0) ? "Зададена е сума, без да се очаква аванс по сделката" : "|Въведения аванс е по-голям от очаквания|* <b>{$dVerbal} {$rec->currencyId}</b> |{$warningUnit}|*";
	    			
	    			$form->setWarning('amountAccrued', $warning);
	    		}
	    	}
	    	
	    	if(isset($rec->amountDeducted)){
	    		$rec->dpOperation = 'deducted';

	    		if(empty($invoicedDp) || $invoicedDp == $deductedDp){
	    			if(!($mvc instanceof sales_Proformas)){
	    				$form->setWarning('amountDeducted', 'Избрано е приспадане на аванс, без да има начислен такъв');
	    			}
	    		} else {
	    			if(abs($rec->dpAmount) > core_Math::roundNumber($invoicedDp - $deductedDp)){
						$downpayment = core_Math::roundNumber(($invoicedDp - $deductedDp) / $rec->rate);
						$dVerbal = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($downpayment);
	    				$form->setWarning('amountDeducted', "|Въведеният за приспадане аванс е по-голям от начисления|* <b>{$dVerbal} {$rec->currencyId}</b> |{$warningUnit}|*");
					}
	    		}
	    		
	    		if(!$form->gotErrors()){
	    			$rec->dpAmount *= -1;
	    		}
	    	}
	    	
	    	$vat = acc_Periods::fetchByDate($rec->date)->vatRate;
	    	if($rec->vatRate != 'yes' && $rec->vatRate != 'separate'){
	    		$vat = 0;
	    	}
	    	
	    	if(!is_null($rec->dpAmount)){
	    		$rec->dpAmount = deals_Helper::getPurePrice($rec->dpAmount, $vat, $rec->rate, $rec->vatRate);
	    		if($rec->vatRate == 'separate'){
	    			$rec->dpAmount /= 1 + $vat;
	    		}
	    	}
	    	
	    	// Обновяваме данните на мастър-записа при редакция
	    	if(isset($rec->id)){
	    		$mvc->updateMaster($rec, FALSE);
	    	}
        }
    }
    
    
    /**
     * Помощна ф-я връщаща сумата на аванса без ддс
     */
    private static function getDpWithoutVat($downpayment, $rec)
    {
    	$vat = acc_Periods::fetchByDate($rec->date)->vatRate;
    	
    	$vatAmount = ($rec->vatRate == 'yes' || $rec->vatRate == 'separate') ? ($downpayment) * $vat / (1 + $vat) : 0;
    	
    	return  $downpayment - $vatAmount;
    }
    
    
    /**
     * След подготовката на детайлите
     */
    public static function on_AfterPrepareDetail($mvc, &$res, &$data)
    {
    	$masterRec = $data->masterData->rec;
    	
    	// Ако е ДИ или КИ не правим нищо
    	if(!($mvc instanceof sales_ProformaDetails) && $masterRec->type != 'invoice') return;
    	
    	// Ако има сума на авансовото плащане и тя не е "0"
    	if($masterRec->dpAmount){
    		
    		// Сумата се обръща в валутата на фактурата
    		$dpAmount = currency_Currencies::round($masterRec->dpAmount / $masterRec->rate);
    		
    		// Обръщане на сумата във вербален вид
    		$Double = cls::get('type_Double');
    		$Double->params['decimals'] = 2;
    		$dpAmount = $Double->toVerbal($dpAmount);
    		
    		// Записване в $data
    		$data->dpInfo = (object)array('dpAmount' => $dpAmount, 'dpOperation' => $masterRec->dpOperation);
    	}
    }
    
    
    /**
     * След рендиране на лист таблицата
     */
    public static function on_AfterRenderListTable($mvc, &$tpl, &$data)
    {
    	// Ако сме в мастъра, пропускаме
    	if(empty($mvc->Master)) return;
    	
    	// Ако няма данни за показване на авансово плащане
    	if(empty($data->dpInfo)) return;
    	
    	if($data->dpInfo->dpOperation == 'none') return;
    	
    	// Ако няма записи, да не се показва реда "няма записи"
    	if(empty($data->rows)){
    		$tpl->removeBlock('NO_ROWS');
    	}
    	
    	$reason = (!empty($data->masterData->rec->dpReason)) ? $data->masterData->rec->dpReason : self::getReasonText($data->masterData->rec, $data->dpInfo->dpOperation);
    	
    	if($data->dpInfo->dpOperation == 'accrued'){
    		$colspan = count($data->listFields) - 1;
    		$lastRow = new ET("<tr><td colspan='{$colspan}' style='text-indent:20px'>" . tr('Авансово плащане') . " " . $reason . "<td style='text-align:right'>[#dpAmount#]</td></td></tr>");
    	} else {
    		$fields = core_TableView::filterEmptyColumns($data->rows, $data->listFields, $mvc->hideListFieldsIfEmpty);
    		
    		$colspan = count($fields) - 2;
    		$colspan1 = isset($fields['reff']) ? 2 : 1;
    		$colspan = isset($fields['reff']) ? $colspan - 1 : $colspan;
    		
    		$lastRow = new ET("<tr><td colspan={$colspan1}></td><td colspan='{$colspan}'>" . tr("Приспадане на авансово плащане") . " " . $reason . " <td style='text-align:right'>[#dpAmount#]</td></td></tr>");
    	}
    	
    	$lastRow->placeObject($data->dpInfo);
    	$tpl->append($lastRow, 'ROW_AFTER');
    }
    
    
    /**
     * Връща дефолтното основание на аванса
     * 
     * @param stdClass $masterRec
     * @param varchar $dpOperation
     * @return string
     */
    private static function getReasonText($masterRec, $dpOperation)
    {
    	$firstDoc = doc_Threads::getFirstDocument($masterRec->threadId);
    	$valior = $firstDoc->getVerbal('valior');
    	 
    	$deals = array();
    	if($firstDoc->isInstanceOf('deals_DealMaster')){
    		$closedDeals = $firstDoc->fetchField('closedDocuments');
    		$closedDeals = keylist::toArray($closedDeals);
    		foreach ($closedDeals as $id){
    			$deals[] = "№{$id}";
    		}
    		$caption = 'договори';
    	}
    	 
    	if(!count($deals)){
    		$deals[] = "№{$firstDoc->that} " . tr("от|* {$valior}");
    		$caption = 'договор';
    	}
    	
    	if($data->dpInfo->dpOperation == 'accrued'){
    		return tr("по {$caption}|* ") . implode(', ', $deals);
    	}
    		
    	$iQuery = sales_Invoices::getQuery();
    	$iQuery->where("#state = 'active' AND #dpOperation = 'accrued' AND #id != '{$masterRec->id}'");
    	$iQuery->where("#threadId = '{$firstDoc->fetchField('threadId')}'");
    		
    	$pArr = $invArr = array();
    	while($iRec = $iQuery->fetch()){
    		$invArr[$iRec->id] = "№" . sales_Invoices::recToVerbal($iRec)->number;
    	}
    		
    	$pQuery = sales_Proformas::getQuery();
    	$pQuery->where("#state = 'active' AND #dpOperation = 'accrued' AND #id != '{$masterRec->id}'");
    	$pQuery->where("#threadId = '{$firstDoc->fetchField('threadId')}'");
    		 
    	while($pRec = $pQuery->fetch()){
    		$pArr[$pRec->id] = "№" . sales_Invoices::recToVerbal($pRec)->number;
    	}
    		
    	$handleArr = count($invArr) ? $invArr : $pArr;
    	$handleString = implode(', ', $handleArr);
    		 
    	$accruedInvoices = count($handleArr);
    		
    	if($accruedInvoices == 1){
    		$docTitle = count($invArr) ? 'по фактура' : 'по проформа';
    		$misc = tr($docTitle) . " {$handleString}";
    	} elseif($accruedInvoices) {
    		$docTitle = count($invArr) ? 'по фактури' : 'по проформи';
    		$misc = tr($docTitle) . " {$handleString}";
    	} else {
    		$misc = tr("по {$caption}|* ") . implode(', ', $deals);
    	}
    		
    	return $misc;
    }
    
    
    /**
     * Изпълнява се след създаване
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	if($mvc->Master) return;
    	
    	// Ако е ДИ или КИ не правим нищо
    	if($rec->type != 'invoice') return;
    	
    	// Ако има авансово плащане
    	if($rec->dpAmount && $rec->dpOperation == 'accrued'){
    		$mvc->updateMaster($rec->id);
    		
    		// Така спираме изпълнението на on_AfterCreate в фактурата
    		return FALSE;
    	}
    }
    
    
    /**
     * След калкулиране на общата сума
     */
    public static function on_AfterCalculateAmount($mvc, &$res, &$recs, &$masterRec)
    {
    	if(!isset($masterRec->dpAmount)) return;
    	$total = &$mvc->Master->_total;
    	
    	// Ако няма детайли, инстанцираме обекта
    	if(!$total){
    		$total = (object)array('amount' => 0, 'vat' => 0, 'discount' => 0);
    	}
    	
    	// Колко е ддс-то
    	$vat = acc_Periods::fetchByDate($masterRec->date)->vatRate;
    	if($masterRec->vatRate != 'yes' && $masterRec->vatRate != 'separate'){
    		$vat = 0;
    	}
    	
    	// Закръгляне на сумите
    	$dpVat = $masterRec->dpAmount * $vat / $masterRec->rate;
    	$dpAmount = $masterRec->dpAmount / $masterRec->rate;
    	
    	// Добавяне на авансовите данни в тотала
    	$total->vat    += $dpVat;
    	$total->amount += $dpAmount;
    	
    	if(isset($total->vats["{$vat}"])){
    		$total->vats["{$vat}"]->amount += $dpVat;
    		$total->vats["{$vat}"]->sum += $masterRec->dpAmount / $masterRec->rate;
    	} else {
    		$total->vats = array("{$vat}" => (object)array('amount' => $dpVat, 'sum' => $masterRec->dpAmount / $masterRec->rate));
    	}
    }
}
