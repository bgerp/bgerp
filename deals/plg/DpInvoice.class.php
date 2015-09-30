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
    	if($mvc instanceof sales_Invoices || $mvc instanceof purchase_Invoices){
    		
    		// Сума на авансовото плащане (ако има)
	    	$mvc->FLD('dpAmount', 'double', 'caption=Авансово плащане->Сума,input=none,before=contragentName');
	    	
	    	// Операция с авансовото плащане начисляване/намаляване
	    	$mvc->FLD('dpOperation', 'enum(accrued=Начисляване, deducted=Приспадане, none=Няма)', 'caption=Авансово плащане->Операция,input=none,before=contragentName');
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
        if(!($mvc instanceof sales_Invoices || $mvc instanceof purchase_Invoices)) return;
    	
        // Ако е ДИ или КИ не правим нищо
        if($rec->type != 'invoice') return;
        
        // Намиране на пораждащия се документ
        $origin         = $mvc->getOrigin($rec);
        $dealInfo       = $origin->getAggregateDealInfo();
        $form->dealInfo = $dealInfo;
        
        // Ако няма очаквано авансово плащане не правим нищо
        //$aggreedDownpayment = $dealInfo->get('agreedDownpayment');
        
        //if(empty($aggreedDownpayment)) return;
        
        if(empty($form->rec->id)){
        	
        	// Поставяне на дефолт стойностти
        	self::getDefaultDpData($form);
        } else {
        	
        	// Ако има детайл не показваме секцията за аванс
        	if(cls::get($mvc->mainDetail)->fetchField("#invoiceId = {$rec->id}", 'id')){
        		return;
        	}
        	
        	// При приспадане ако има сума я показваме положителна
        	if($rec->dpOperation == 'deducted'){
        		$rec->dpAmount *= -1;
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
        
        // Показване на полетата за авансовите плащания
		$form->setField('dpAmount',"input,unit=|*{$rec->currencyId} |без ДДС|*");
        $form->setField('dpOperation','input');
        
        if($form->rec->dpOperation == 'accrued'){
        	//$form->setField('dueDate', 'input=none');
        } elseif($form->rec->dpOperation == 'none'){
        	unset($form->rec->dpAmount);
        }
    }
    
    
    /**
     * Подготвя дефолт стойностите за авансовите плащания
     * 
     * @param core_Form $form
     */
    private static function getDefaultDpData(core_Form &$form)
    {   
    	// Договореното до момента
    	$aggreedDp  = $form->dealInfo->get('agreedDownpayment');
    	$actualDp   = $form->dealInfo->get('downpayment');
    	$invoicedDp = $form->dealInfo->get('downpaymentInvoiced');
    	$deductedDp = $form->dealInfo->get('downpaymentDeducted');
    	
    	// Ако има платен аванс ръководим се по него, ако няма по договорения
    	$downpayment = (empty($actualDp)) ? $aggreedDp : $actualDp;
    	
    	// Ако няма авансово плащане на задаваме дефолти
    	if(!isset($downpayment)) {
    		$dpOperation = 'none';
    	} else {
    		
    		// Ако няма фактуриран аванс
    		if(empty($invoicedDp)){
    			 
    			// Начисляване на аванса
    			$dpAmount = $downpayment;
    			$dpOperation = 'accrued';
    		} else {
    		
    			// Ако има вече начислен аванс, по дефолт е приспадане със сумата за приспадане
    			$dpAmount = $invoicedDp - $deductedDp;
    			$dpOperation = 'deducted';
    		}
    		 
    		// Слагане на изчислените дефолти
    		if(isset($dpAmount)){
    			$dpAmount = self::getDpWithoutVat($dpAmount, $form->rec);
    			$form->setDefault('dpAmount', $dpAmount);
    		}
    	}
    	
    	if($dpOperation){
    		$form->setDefault('dpOperation', $dpOperation);
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
        	
	    	if(isset($rec->dpOperation) && $rec->dpOperation !== 'none' && !isset($rec->dpAmount)){
	    		$form->setError('dpAmount', 'Ако е избрано начисляване/приспадане трябва да има сума');
	    		return;
	    	}
	    	
        	if($rec->dpOperation === 'accrued'){
        		
        		$downpayment = (empty($actualDp)) ? $aggreedDp  : $actualDp;
        		$vat = acc_Periods::fetchByDate($rec->date)->vatRate;
        		if($rec->vatRate != 'yes' && $rec->vatRate != 'separate'){
    				$vat = 0;
    			}
        		
        		$downpayment = round(($downpayment - ($downpayment * $vat / (1 + $vat))) / $rec->rate, 6);
        		
        		if($rec->dpAmount > $downpayment){
        			$warning = ($downpayment === (double)0) ? "Зададена е сума, без да се очаква аванс по сделката" : "|Въведената сума е по-голяма от очаквания аванс от|* '{$downpayment}' |без ДДС|*";
        			
	            	$form->setWarning('dpAmount', $warning);
	            }
        	} elseif($rec->dpOperation === 'deducted'){
        		
        		if(empty($invoicedDp)){
        			$form->setWarning('dpOperation', 'Избрано е приспадане на аванс, без да има начислено ДДС за аванс');
        		} else {
        			if(abs($rec->dpAmount) > ($invoicedDp - $deductedDp)){
        				$form->setWarning('dpAmount', 'Приспаднатия аванс е по-голям от този който трябва да бъде приспаднат');
        			}
        		}

        		if(!$form->gotErrors()){
        			$rec->dpAmount *= -1;
        		}
        	}
        	
        	if($rec->dpOperation && !$form->gotErrors()){
        		$rec->dpAmount = $rec->dpAmount * $rec->rate;
        		if($rec->dpAmount == 0){
        			$rec->dpOperation = 'none';
        		}
        		
        		// Обновяваме данните на мастър-записа при редакция
        		if(isset($rec->id)){
        			$mvc->updateMaster($rec, FALSE);
        		}
        	}
        	
        	if($rec->dpOperation == 'none'){
        		$rec->dpAmount = NULL;
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
    	if($masterRec->type != 'invoice') return;
    	
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
    	
    	if($data->dpInfo->dpOperation == 'none'){
    		return;
    	}
    	
    	// Ако няма записи, да не се показва реда "няма записи"
    	if(empty($data->rows)){
    		$tpl->removeBlock('NO_ROWS');
    	}
    	
    	if($data->dpInfo->dpOperation == 'accrued'){
    		$colspan = count($data->listFields) - 2;
    		$lastRow = new ET("<tr><td colspan='{$colspan}' style='text-indent:20px'>" . tr('Авансово плащане') . "<td style='text-align:right'>[#dpAmount#]</td></td></tr>");
    	} else {
    		$colspan = count($data->listFields) - 3;
    		$lastRow = new ET("<tr><td></td><td colspan='{$colspan}'>" . tr("Приспадане на авансово плащане") . "<td style='text-align:right'>[#dpAmount#]</td></td></tr>");
    	}
    	
    	$lastRow->placeObject($data->dpInfo);
    	
    	$tpl->append($lastRow, 'ROW_AFTER');
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
    	
    	$conf = core_Packs::getConfig('acc');
    	
    	// Ако сумата и ддс-то е в границата на допустимото разминаваме, приравняваме ги на 0
    	if($total->vat >= -1 * $conf->ACC_MONEY_TOLERANCE && $total->vat <= $conf->ACC_MONEY_TOLERANCE){
    		$total->vat = 0;
    	}
    	
    	if($total->amount >= -1 * $conf->ACC_MONEY_TOLERANCE && $total->amount <= $conf->ACC_MONEY_TOLERANCE){
    		$total->amount = 0;
    	}
    }
}