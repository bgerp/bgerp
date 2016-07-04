<?php



/**
 * Плъгин позволяващ лесно избиране на сделка по контрагент. Добавя две полета във формата
 * за избор на папка на контрагент и за въвеждане на хендлър на сделка.
 * При избор на контрагент се зареждат наличните сделки като предложения.
 * При въвеждане на хендлър се проверява дали има такъв документ и дали въобще може да бъде избран
 *
 * $mvc->selectedDealOriginFieldName - в това поле ще се запише контейнера на сделката
 * $mvc->selectedDealClasses         - между кои класове на сделки да търсим
 * $mvc->selectDealAfterField        - след кое поле в формата да се покажат полетата за избор
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_plg_SelectDeal extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    public static function on_AfterDescription(core_Master &$mvc)
    {
    	setIfNot($mvc->selectedDealOriginFieldName, 'dealId');
    	setIfNot($mvc->selectedDealClasses, 'purchase_Purchases,findeals_Deals,sales_Sales');
    	$mvc->selectedDealClasses = arr::make($mvc->selectedDealClasses, TRUE);
    	
    	$mvc->FNC('contragentFolderId', 'key(mvc=doc_Folders,select=title)', "caption=Кореспондираща сделка->Контрагент,removeAndRefreshForm,silent,input,after={$mvc->selectDealAfterField}");
    	$mvc->FNC('dealHandler', 'varchar', "caption=Кореспондираща сделка->Номер,input,silent,hint=Въведете хендлър на документ");
    	$mvc->FLD($mvc->selectedDealOriginFieldName, 'int', 'input=hidden,tdClass=leftColImportant');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	if(isset($rec->id)){
    		if($rec->{$mvc->selectedDealOriginFieldName}){
    			$corespondent = doc_Containers::getDocument($rec->{$mvc->selectedDealOriginFieldName});
    			$form->setDefault('dealHandler', $corespondent->getHandle());
    		}
    	}
    	
    	$form->setOptions('contragentFolderId', array('' => '') + doc_Folders::getOptionsByCoverInterface('crm_ContragentAccRegIntf'));
    	
    	// Ако има избрана папка на контрагент, зареждаме всички достъпни сделки като предложение
    	if(isset($rec->contragentFolderId)){
    		$suggestions = $mvc->getContragentDealSuggestions($rec->contragentFolderId);
    		$form->setSuggestions('dealHandler', $suggestions);
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	
    	// Ако е събмитната формата
    	if($form->isSubmitted()){
    		
    		if(!empty($rec->dealHandler)){
    			$handlerError = $mvc->checkSelectedHandle($rec->dealHandler, $rec);
    			if($handlerError){
    				$form->setError('dealHandler', $handlerError);
    			}
    			
    			if(!$form->gotErrors()){
    				$doc = doc_Containers::getDocumentByHandle($rec->dealHandler);
    				$form->setDefault($mvc->selectedDealOriginFieldName, $doc->fetchField('containerId'));
    			}
    		} else {
    			$rec->{$mvc->selectedDealOriginFieldName} = NULL;
    		}
    	}
    }
    
    
    /**
     * Проверява хендлъра дали може да се избере
     *
     * @param core_Mvc $mvc  - класа
     * @param string $error  - текста на грешката
     * @param string $handle - хендлъра на сделката
     * @param stdClass $rec  - текущия запис
     */
    public static function on_AfterCheckSelectedHandle($mvc, &$error = NULL, $handle, $rec)
    {
    	if(!strlen($handle)) return;
    	
    	$doc = doc_Containers::getDocumentByHandle($handle);
    	if(!empty($doc) && !$doc->haveRightFor('single')){
    		unset($doc);
    	}
    	
    	if(!$doc){
    		$error = 'Няма документ с такъв хендлър';
    	}
    }
    
    
    /**
     * Подготвяме предложенията за избор на сделки на контрагент
     *
     * @param int $folderId - папка на контрагента
     * @return array $suggestions - масив с предложенията
     */
    public static function on_AftergetContragentDealSuggestions($mvc, &$res, $folderId)
    {
    	$suggestions = array();
    	 
    	$after = dt::addMonths(-3, dt::today());
    	$after = dt::verbal2mysql($after, FALSE);
    	$allowedClasses = $mvc->selectedDealClasses;
    	
    	// За всички финансови сделки и покупки
    	foreach ($allowedClasses as $cls){
    		$Cls = cls::get($cls);
    		expect($Cls instanceof deals_DealBase, 'Не е подадена валидна сделка');
    		
    		// Намираме тези в папката на контрагента за един месец назад
    		$fQuery = $Cls->getQuery();
    		$fQuery->where("#folderId = {$folderId}");
    		$fQuery->where("#state = 'active'");
    		$fQuery->where("#createdOn >= '{$after}'");
    
    		// За всеки запис подготвяме опциите показвайки за име вида 'хендлър / дата / сума валута'
    		while($fRec = $fQuery->fetch()){
    			$handle = $Cls->getHandle($fRec->id);
    			$date = dt::mysql2verbal($fRec->{$Cls->filterDateField}, "d.m.Y");
    			$amount = round($fRec->amountDeal / $fRec->currencyRate, 2);
    
    			$suggestions[$handle] = "{$handle} / {$date} / $amount {$fRec->currencyId}";
    		}
    	}
    	 
    	// Връщаме предложенията
    	if(count($suggestions)){
    		$suggestions = array('' => '') + $suggestions;
    	}
    	 
    	$res = $suggestions;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(isset($rec->{$mvc->selectedDealOriginFieldName})){
    		$row->{$mvc->selectedDealOriginFieldName} = doc_Containers::getDocument($rec->{$mvc->selectedDealOriginFieldName})->getLink(0);
    	}
    }
}