<?php


/**
 * Плъгин позволяващ на обикновена фактура да начислява или да приспада
 * ддс ако се очаква авансово плащане
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class deals_plg_DpInvoice extends core_Plugin
{
    /**
     * Извиква се след описанието на модела
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        if ($mvc instanceof sales_Invoices || $mvc instanceof purchase_Invoices || $mvc instanceof sales_Proformas) {
            
            // Сума на авансовото плащане (ако има)
            $mvc->FLD('dpAmount', 'double', 'caption=Авансово плащане->Сума,input=none,before=displayContragentClassId');
            
            // Операция с авансовото плащане начисляване/намаляване
            $mvc->FLD('dpOperation', 'enum(accrued=Начисляване, deducted=Приспадане, none=Няма)', 'caption=Авансово плащане->Операция,input=none,before=contragentName');
            $mvc->FLD('dpVatGroupId', 'key(allowEmpty,mvc=acc_VatGroups,select=title)', "silent,caption=Аванс->ДДС група,autohide,after=amountDeducted,placeholder=Автоматично,input=none");
            $mvc->FLD('dpReason', 'richtext(rows=2)', 'caption=Аванс->Пояснение,after=amountDeducted,autohide,input=none');
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
        if (!($mvc instanceof sales_Invoices || $mvc instanceof purchase_Invoices || $mvc instanceof sales_Proformas)) {
            
            return;
        }
        
        // Ако е ДИ или КИ не правим нищо
        if ($rec->type != 'invoice') {
            
            return;
        }
        
        // Намиране на пораждащия се документ
        $origin = doc_Threads::getFirstDocument($rec->threadId);
        if (!core_Cls::existsMethod($origin->getInstance(), 'getAggregateDealInfo')) {
            
            return;
        }
        $dealInfo = $origin->getAggregateDealInfo();
        $form->dealInfo = $dealInfo;
        
        $unit = ($rec->vatRate == 'yes' || $rec->vatRate == 'separate') ? 'с ДДС' : 'без ДДС';
        
        $form->FNC('amountAccrued', 'double', "caption=Аванс->Начисляване,input,autohide,before=dpAmount,unit=|*{$rec->currencyId} |{$unit}|*");
        $form->FNC('amountDeducted', 'double', "caption=Аванс->Приспадане,input,autohide,before=dpAmount,unit=|*{$rec->currencyId} |{$unit}|*");
        if (in_array($rec->vatRate, array('yes', 'separate'))) {
            $form->setField('dpVatGroupId', 'input');
        }

        $form->setField('dpReason', 'input');
        
        if (empty($form->rec->id)) {
            
            // Поставяне на дефолт стойностти
            self::getDefaultDpData($form, $mvc);
        } else {
            $Detail = cls::get($mvc->mainDetail);
            
            // Ако има детайл не показваме секцията за аванс
            if ($Detail->fetchField("#{$Detail->masterKey} = {$rec->id}", 'id')) {
                $form->setField('amountAccrued', 'input=none');
            }
            
            // При приспадане ако има сума я показваме положителна
            if ($rec->dpOperation == 'deducted') {
                $rec->dpAmount *= -1;
            }
        }
        
        if (isset($rec->dpAmount)) {
            $dpAmount = $rec->dpAmount / $rec->rate;
            $vat = acc_Periods::fetchByDate($rec->date)->vatRate;
            if(isset($rec->dpVatGroupId)){
                $vat = acc_VatGroups::fetchField($rec->dpVatGroupId, 'vat');
            }
            if ($rec->vatRate != 'yes' && $rec->vatRate != 'separate') {
                $vat = 0;
            }
            
            $dpAmount += $dpAmount * $vat;
            $dpAmount = round($dpAmount, 2);
            
            if ($rec->dpOperation == 'accrued') {
                $form->setDefault('amountAccrued', $dpAmount);
            } elseif ($rec->dpOperation == 'deducted') {
                $form->setDefault('amountDeducted', $dpAmount);
            }
        }
        
        if (isset($form->rec->dpAmount)) {
            $dpAmount = round($form->rec->dpAmount / $form->rec->rate, 6);
            if ($dpAmount == 0) {
                unset($form->rec->dpAmount);
                unset($form->rec->dpOperation);
                
                return;
            }
            
            $form->rec->dpAmount = $dpAmount;
        }
        
        if ($form->rec->dpOperation == 'none') {
            unset($form->rec->dpAmount);
        }
    }
    
    
    /**
     * Функция, която прихваща след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
        if (empty($rec->dpAmount) || empty($rec->dpOperation)) {
            
            return;
        }
        
        // Ако потребителя не е в група доставчици го включваме
        $rec = $mvc->fetchRec($rec);
        
        // Записване на основанието за аванс
        if (empty($rec->dpReason)) {
            $rec->tplLang = $mvc->pushTemplateLg($rec->template);
            $rec->dpReason = self::getReasonText($rec, $rec->dpOperation);
            $mvc->save_($rec, 'dpReason');
            core_Lg::pop();
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
        $agreedDp = $form->dealInfo->get('agreedDownpayment');
        $actualDp = $form->dealInfo->get('downpayment');
        $downpayment = (empty($actualDp)) ? null : $actualDp;
        $flag = true;

        $dpByVats = $form->dealInfo->get('downpaymentAccruedByVats');
        if(countR($dpByVats)){
            if(in_array($rec->vatRate, array('yes', 'separate'))) {
                $form->setField('dpVatGroupId','removeAndRefreshForm=amountAccrued|amountDeducted');
                $form->setDefault('dpVatGroupId', key($dpByVats));
            }
            $invoicedDp = $dpByVats[$form->rec->dpVatGroupId];
            $dpDeductedByVats = $form->dealInfo->get('downpaymentDeductedByVats');
            $deductedDp = $dpDeductedByVats[$form->rec->dpVatGroupId];
        } else {
            $invoicedDp = $form->dealInfo->get('downpaymentInvoiced');
            $deductedDp = $form->dealInfo->get('downpaymentDeducted');
        }

        // Ако е проформа
        if ($mvc instanceof sales_Proformas) {
            $accruedProformaRec = sales_Proformas::fetch("#threadId = {$rec->threadId} AND #state = 'active' AND #dpOperation = 'accrued'");
            $hasDeductedProforma = sales_Proformas::fetchField("#threadId = {$rec->threadId} AND #state = 'active' AND #dpOperation = 'deducted'");

            // Ако има проформа за аванс и няма таква за приспадане, тази приспада
            if ((!empty($accruedProformaRec) && empty($hasDeductedProforma)) || !empty($actualDp)) {

                //$dpAmount = (($accruedProformaRec->dealValue - $accruedProformaRec->discountAmount)+ $accruedProformaRec->vatAmount);
                $dpAmount = round($actualDp, 6);
                $dpOperation = 'deducted';
                $flag = false;
            } else {
                // Ако има проформа за начисляване на аванс и за приспадане, не задаваме дефолти
                if ($hasDeductedProforma) {
                    
                    return;
                }
            }
        }

        if ($flag === true) {
            if (!isset($downpayment)) {
                $dpOperation = 'none';
                if (isset($invoicedDp) && ($invoicedDp - $deductedDp) > 0) {
                    $dpAmount = $invoicedDp - $deductedDp;
                    $dpOperation = 'deducted';
                }
            } else {
                
                // Ако няма фактуриран аванс
                if (empty($invoicedDp)) {
                    if ($flag === true) {
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
        if (!($mvc instanceof sales_Proformas) && $form->rec->contragentCountryId != drdata_Countries::fetchField("#commonName = 'Bulgaria'")) {
            
            return;
        }
        
        switch ($dpOperation) {
            case 'accrued':
                if (isset($dpAmount)) {
                    $delivered = $form->dealInfo->get('deliveryAmount');
                    if (!empty($delivered)) {
                        $dpOperation = 'none';
                        $form->setSuggestions('amountAccrued', array('' => '', "{$dpAmount}" => $dpAmount));
                    } else {
                        $form->setDefault('amountAccrued', $dpAmount);
                    }
                }
                break;
            case 'deducted':
                if ($dpAmount) {
                    $form->setDefault('amountDeducted', $dpAmount);
                }
                break;
            case 'none':
            if (isset($agreedDp)) {
                $dpField = $form->getField('amountAccrued');
                unset($dpField->autohide);
                
                $sAmount = core_Math::roundNumber($agreedDp / $rate);
                $suggestions = array('' => '', "{$sAmount}" => $sAmount);
                $form->setSuggestions('amountAccrued', $suggestions);
            }
            break;
        }
        
        if ($dpOperation) {
            $form->setDefault('dpOperation', $dpOperation);
            
            if ($form->rec->dpOperation == 'accrued' && isset($form->rec->amountDeducted)) {
                unset($form->rec->amountDeducted);
            }
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputDpInvoice($mvc, &$res, &$form)
    {
        // Ако сме в детайла пропускаме
        if ($mvc->Master) {
            
            return;
        }
        
        if (empty($form->dealInfo)) {
            
            return;
        }
        
        if ($form->isSubmitted()) {
            $changeAct = (core_Request::get('Act') == 'changefields');
            
            $rec = &$form->rec;

            $agreedDp = $form->dealInfo->get('agreedDownpayment');
            $actualDp = $form->dealInfo->get('downpayment');
            $invoicedDp = $form->dealInfo->get('downpaymentInvoiced');
            $deductedDp = $form->dealInfo->get('downpaymentDeducted');
            
            if (isset($rec->amountAccrued, $rec->amountDeducted)) {
                $form->setError('amountAccrued,amountDeducted', 'Не може едновременно да се начислява и да се приспада аванс');
                
                return;
            }
            
            if(!empty($rec->dpReason) && (empty($rec->amountAccrued) && empty($rec->amountDeducted))){
                $form->setError('dpReason,amountAccrued,amountDeducted', 'Не може да е попълнено основание за аванс, без да е въведена сума');
                
                return;
            }

            if(!empty($rec->dpVatGroupId) && (empty($rec->amountAccrued) && empty($rec->amountDeducted))){
                $form->setError('dpVatGroupId,amountAccrued,amountDeducted', 'Не може да е попълнена ДДС група на аванса, без да е въведен аванс');

                return;
            }

            $rec->dpAmount = ($rec->amountAccrued) ? $rec->amountAccrued : $rec->amountDeducted;
            $rec->dpOperation = 'none';
            $warningUnit = ($rec->vatRate != 'yes' && $rec->vatRate != 'separate') ? 'без ДДС' : 'с ДДС';
            
            if (isset($rec->amountAccrued)) {
                $rec->dpOperation = 'accrued';

                if (empty($actualDp) && (!empty($invoicedDp) && !empty($deductedDp) && $invoicedDp == $deductedDp)) {
                    $downpayment = $actualDp;
                } else {
                    $downpayment = $agreedDp;
                }
                
                $downpayment = core_Math::roundNumber($downpayment / $rec->rate);
                if ($rec->dpAmount > ($downpayment * 1.05 + 1) && $changeAct !== true) {
                    $dVerbal = cls::get('type_Double', array('params' => array('smartRound' => true)))->toVerbal($downpayment);
                    $warning = ($downpayment === (double) 0) ? 'Зададена е сума, без да се очаква аванс по сделката' : "|Въведения аванс е по-голям от очаквания|* <b>{$dVerbal} {$rec->currencyId}</b> |{$warningUnit}|*";
                    
                    $form->setWarning('amountAccrued', $warning);
                }
            }
            
            if (isset($rec->amountDeducted)) {
                $rec->dpOperation = 'deducted';
                
                if ($changeAct !== true) {
                    if (empty($invoicedDp) || $invoicedDp == $deductedDp) {
                        if (!($mvc instanceof sales_Proformas)) {
                            $form->setWarning('amountDeducted', 'Избрано е приспадане на аванс, без да има начислен такъв');
                        }
                    } else {
                        if (abs($rec->dpAmount) > core_Math::roundNumber($invoicedDp - $deductedDp)) {
                            $downpayment = core_Math::roundNumber(($invoicedDp - $deductedDp) / $rec->rate);
                            $dVerbal = cls::get('type_Double', array('params' => array('smartRound' => true)))->toVerbal($downpayment);
                            $form->setWarning('amountDeducted', "|Въведеният за приспадане аванс е по-голям от начисления|* <b>{$dVerbal} {$rec->currencyId}</b> |{$warningUnit}|*");
                        }
                    }
                }
                
                if (!$form->gotErrors()) {
                    $rec->dpAmount *= -1;
                }
            }


            if (!in_array($rec->vatRate, array('yes', 'separate'))) {
                $vat = 0;
                unset($rec->dpVatGroupId);
            } else {
                $expectedDpVatGroupId = null;
                if(!empty($rec->amountAccrued) || !empty($rec->amountDeducted)){
                    $expectedDpVatGroupId = self::getDefaultDpVatGroupId($mvc, $rec);
                }

                $vat = acc_Periods::fetchByDate($rec->date)->vatRate;
                if(empty($rec->id)){
                    if(isset($expectedDpVatGroupId) && isset($rec->dpVatGroupId) && $rec->dpVatGroupId != $expectedDpVatGroupId){
                        if($rec->dpOperation != 'deducted'){
                            $form->setWarning('dpVatGroupId', "ДДС групата на аванса е различна от очакваната|*: <b>" . acc_VatGroups::getTitleById($expectedDpVatGroupId) . "</b>");
                        }
                    }

                    if(empty($rec->dpVatGroupId) && isset($expectedDpVatGroupId)){
                        $rec->dpVatGroupId = $expectedDpVatGroupId;
                    }
                }

                if(isset($rec->dpVatGroupId)){
                    $vat = acc_VatGroups::fetchField($rec->dpVatGroupId, 'vat');
                }
            }
            
            if (!is_null($rec->dpAmount)) {
                $rec->dpAmount = deals_Helper::getPurePrice($rec->dpAmount, $vat, $rec->rate, $rec->vatRate);
                if ($rec->vatRate == 'separate') {
                    $rec->dpAmount /= 1 + $vat;
                }
            }

            // Обновяваме данните на мастър-записа при редакция
            if (isset($rec->id)) {
                $mvc->updateMaster($rec, false);
            }
        }
    }


    /**
     * Каква е очакваната ДДС група на аванса
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @return int
     */
    private static function getDefaultDpVatGroupId($mvc, $rec)
    {
        if($origin = $mvc::getOrigin($rec)){
            if(isset($origin->mainDetail)){
                $Detail = cls::get($origin->mainDetail);
                if(isset($Detail->productFld)){
                    $valior = $origin->fetchField($origin->valiorFld);
                    $originVatGroups = array();
                    $dQuery = $Detail->getQuery();
                    $dQuery->where("#{$Detail->masterKey} = {$origin->that}");
                    $dQuery->show($Detail->productFld);

                    // Към коя ДДС група е артикула от ориджина
                    while($dRec = $dQuery->fetch()){
                        $grId = cat_products_VatGroups::getCurrentGroup($dRec->{$Detail->productFld}, $valior)->id;
                        if(isset($grId)){
                            $originVatGroups[$grId] = $grId;
                        }
                    }

                    // Ако всички артикули от ориджина са от 1 - група, тя ще е за аванса
                    if(countR($originVatGroups) == 1) return key($originVatGroups);
                }
            }
        }

        return acc_VatGroups::getDefaultIdByDate($rec->date);
    }
    
    
    /**
     * След подготовката на детайлите
     */
    public static function on_AfterPrepareDetail($mvc, &$res, &$data)
    {
        $masterRec = $data->masterData->rec;
        
        // Ако е ДИ или КИ не правим нищо
        if (!($mvc instanceof sales_ProformaDetails) && $masterRec->type != 'invoice') {
            
            return;
        }
        
        // Ако има сума на авансовото плащане и тя не е "0"
        if ($masterRec->dpAmount) {
            
            // Сумата се обръща в валутата на фактурата
            $dpAmount = currency_Currencies::round($masterRec->dpAmount / $masterRec->rate);
            
            // Обръщане на сумата във вербален вид
            $Double = cls::get('type_Double');
            $Double->params['decimals'] = 2;
            $dpAmount = $Double->toVerbal($dpAmount);
            
            // Записване в $data
            $data->dpInfo = (object) array('dpAmount' => $dpAmount, 'dpOperation' => $masterRec->dpOperation);
        }
    }
    
    
    /**
     * След рендиране на лист таблицата
     */
    public static function on_AfterRenderListTable($mvc, &$tpl, &$data)
    {
        // Ако сме в мастъра, пропускаме
        if (empty($mvc->Master)) {
            
            return;
        }
        
        // Ако няма данни за показване на авансово плащане
        if (empty($data->dpInfo)) {
            
            return;
        }
        
        if ($data->dpInfo->dpOperation == 'none') {
            
            return;
        }
        
        // Ако няма записи, да не се показва реда "няма записи"
        if (empty($data->rows)) {
            $tpl->removeBlock('NO_ROWS');
        }

        $RichText = core_Type::getByName('richtext');
        $dpReason = (!empty($data->masterData->rec->dpReason)) ? $RichText->toVerbal($data->masterData->rec->dpReason) : $RichText->toVerbal(self::getReasonText($data->masterData->rec, $data->dpInfo->dpOperation));
        $reason = (!empty($data->masterData->rec->dpReason)) ? $dpReason : ht::createHint($dpReason, 'Основанието ще бъде записано при контиране', 'notice', false);
        $reason = !empty($reason) ? "</br>" . $reason : '';
        
        if ($data->dpInfo->dpOperation == 'accrued') {
            $colspan = countR($data->listFields) - 1;
            $lastRow = new ET("<tr><td colspan='{$colspan}' style='text-indent:20px'>" . tr('Авансово плащане') . ' <span' . $reason . "<td style='text-align:right'>[#dpAmount#]</td></td></tr>");
        } else {
            $fields = core_TableView::filterEmptyColumns($data->rows, $data->listFields, $mvc->hideListFieldsIfEmpty);
            
            $colspan = countR($fields) - 2;
            $colspan1 = isset($fields['reff']) ? 2 : 1;
            $colspan = isset($fields['reff']) ? $colspan - 1 : $colspan;
            
            $lastRow = new ET("<tr><td colspan={$colspan1}></td><td colspan='{$colspan}'>" . tr('Приспадане на авансово плащане') . ' ' . $reason . " <td style='text-align:right'>[#dpAmount#]</td></td></tr>");
        }

        if(!doc_plg_HidePrices::canSeePriceFields($data->masterData->rec)){
            $data->dpInfo->dpAmount = doc_plg_HidePrices::getBuriedElement();
        }

        $lastRow->placeObject($data->dpInfo);
        $tpl->append($lastRow, 'ROW_AFTER');
    }
    
    
    /**
     * Връща дефолтното основание на аванса
     *
     * @param stdClass $masterRec
     * @param string   $dpOperation
     *
     * @return string
     */
    private static function getReasonText($masterRec, $dpOperation)
    {
        $firstDoc = doc_Threads::getFirstDocument($masterRec->threadId);
        $valior = $firstDoc->getVerbal('valior');
        
        $deals = array();
        if ($firstDoc->isInstanceOf('deals_DealMaster')) {
            $closedDeals = $firstDoc->fetchField('closedDocuments');
            $closedDeals = keylist::toArray($closedDeals);
            foreach ($closedDeals as $id) {
                $deals[] = "№{$id}";
            }
            $caption = 'договори';
        }
        
        if (!countR($deals)) {
            $deals[] = "№{$firstDoc->that} " . tr("от|* {$valior}");
            $caption = 'договор';
        }
        
        if ($dpOperation == 'accrued') {
            
            return tr("по {$caption}|* ") . implode(', ', $deals);
        }
        
        $iQuery = sales_Invoices::getQuery();
        $iQuery->where("#state = 'active' AND #dpOperation = 'accrued' AND #id != '{$masterRec->id}'");
        $iQuery->where("#threadId = '{$firstDoc->fetchField('threadId')}'");
        
        $pArr = $invArr = array();
        while ($iRec = $iQuery->fetch()) {
            $invArr[$iRec->id] = '№' . sales_Invoices::recToVerbal($iRec)->number;
        }
        
        $pQuery = sales_Proformas::getQuery();
        $pQuery->where("#state = 'active' AND #dpOperation = 'accrued' AND #id != '{$masterRec->id}'");
        $pQuery->where("#threadId = '{$firstDoc->fetchField('threadId')}'");
        
        while ($pRec = $pQuery->fetch()) {
            $pArr[$pRec->id] = '№' . sales_Invoices::recToVerbal($pRec)->number;
        }
        
        $handleArr = countR($invArr) ? $invArr : $pArr;
        $handleString = implode(', ', $handleArr);
        
        $accruedInvoices = countR($handleArr);
        
        if ($accruedInvoices == 1) {
            $docTitle = countR($invArr) ? 'по фактура' : 'по проформа';
            $misc = tr($docTitle) . " {$handleString}";
        } elseif ($accruedInvoices) {
            $docTitle = countR($invArr) ? 'по фактури' : 'по проформи';
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
        if ($mvc->Master) {
            
            return;
        }
        
        // Ако е ДИ или КИ не правим нищо
        if ($rec->type != 'invoice') {
            
            return;
        }
        
        // Ако има авансово плащане
        if ($rec->dpAmount && $rec->dpOperation == 'accrued') {
            $mvc->updateMaster($rec->id);
            
            // Така спираме изпълнението на on_AfterCreate в фактурата
            return false;
        }
    }
    
    
    /**
     * След калкулиране на общата сума
     */
    public static function on_AfterCalculateAmount($mvc, &$res, &$recs, &$masterRec)
    {
        if (!isset($masterRec->dpAmount)) {
            
            return;
        }
        $total = &$mvc->Master->_total;

        // Ако няма детайли, инстанцираме обекта
        if (!$total) {
            $total = (object) array('amount' => 0, 'vat' => 0, 'discount' => 0);
        }
        
        // Колко е ддс-то
        $vat = acc_Periods::fetchByDate($masterRec->date)->vatRate;
        if(isset($masterRec->dpVatGroupId)){
            $vat = acc_VatGroups::fetchField($masterRec->dpVatGroupId, 'vat');
        }

        if ($masterRec->vatRate != 'yes' && $masterRec->vatRate != 'separate') {
            $vat = 0;
        }

        // Закръгляне на сумите
        $dpVat = $masterRec->dpAmount * $vat / $masterRec->rate;
        $dpAmount = $masterRec->dpAmount / $masterRec->rate;
        
        // Добавяне на авансовите данни в тотала
        $total->vat += $dpVat;
        $total->amount += $dpAmount;

        if(!isset($total->vats["{$vat}"])){
            $total->vats["{$vat}"] = (object) array('amount' => $dpVat, 'sum' => $masterRec->dpAmount / $masterRec->rate);
        } else {
            $total->vats["{$vat}"]->amount += $dpVat;
            $total->vats["{$vat}"]->sum += $masterRec->dpAmount / $masterRec->rate;
        }
    }
}
