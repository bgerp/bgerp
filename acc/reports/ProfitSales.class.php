<?php



/**
 * Мениджър на отчети от Печалба по продажби
 * Имплементация на 'frame_ReportSourceIntf' за направата на справка на баланса
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_reports_ProfitSales extends acc_reports_CorespondingImpl
{

    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';


    /**
     * Заглавие
     */
    public $title = 'Счетоводство » Печалба по продажби';

    
    /**
     * Дефолт сметка
     */
    public $baseAccountId = '700';

    
    /**
     * Кореспондент сметка
     */
    public $corespondentAccountId = '123';


    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterAddEmbeddedFields($mvc, core_FieldSet &$form)
    {

        // Искаме да покажим оборотната ведомост за сметката на касите
        $baseAccId = acc_Accounts::getRecBySystemId($mvc->baseAccountId)->id;
        $form->setDefault('baseAccountId', $baseAccId);
        $form->setHidden('baseAccountId');
        
        $corespondentAccId = acc_Accounts::getRecBySystemId($mvc->corespondentAccountId)->id;
        $form->setDefault('corespondentAccountId', $corespondentAccId);
        $form->setHidden('corespondentAccountId');
        
        $form->setDefault('side', 'all');
        $form->setHidden('side');
        
        $form->setDefault('orderBy', 'DESC');
        $form->setHidden('orderBy');
        
        $form->setDefault('orderField', 'blAmount');
        $form->setHidden('orderField');
        
        $form->setField('from','refreshForm,silent');
        $form->setField('to','refreshForm,silent');
    }
    
    
    /**
     * Проверява въведените данни
     *
     * @param core_Form $form
     */
    public function checkEmbeddedForm(core_Form &$form)
    {
    	// Размяна, ако периодите са объркани
    	if(isset($form->rec->from) && isset($form->rec->to) && ($form->rec->from > $form->rec->to)) {
    		$mid = $form->rec->from;
    		$form->rec->from = $form->rec->to;
    		$form->rec->to = $mid;
    	}
    }


    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterPrepareEmbeddedForm($mvc, core_Form &$form)
    {
    	
    	foreach (range(1, 3) as $i) {
    	
    		$form->setHidden("feat{$i}");
    	
    	}
    	
    	$salesPositionId = acc_Lists::fetchField("#systemId = 'deals'",'id');
    	
    	foreach(range(1, 3) as $i) {
    		if ($form->rec->{"list{$i}"} == $salesPositionId) {

    			$form->setDefault("feat{$i}", "*");
    		}
    	}
    	
    	// Поставяме удобни опции за избор на период
    	$query = acc_Periods::getQuery();
    	$query->where("#state = 'closed'");
    	$query->orderBy("#end", "DESC");
    	
    	$yesterday = dt::verbal2mysql(dt::addDays(-1, dt::today()), FALSE);
    	$daybefore = dt::verbal2mysql(dt::addDays(-2, dt::today()), FALSE);
    	$optionsFrom = $optionsTo = array();
    	$optionsFrom[dt::today()] = 'Днес';
    	$optionsFrom[$yesterday] = 'Вчера';
    	$optionsFrom[$daybefore] = 'Завчера';
    	$optionsTo[dt::today()] = 'Днес';
    	$optionsTo[$yesterday] = 'Вчера';
    	$optionsTo[$daybefore] = 'Завчера';
    	
    	while ($op = $query->fetch()) {
    		$optionsFrom[$op->start] = $op->title;
    		$optionsTo[$op->end] = $op->title;
    	}
    	
    	$form->setSuggestions('from', array('' => '') + $optionsFrom);
    	$form->setSuggestions('to', array('' => '') + $optionsTo);

    }


    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    public static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
   
		unset($data->listFields['debitQuantity']);
        unset($data->listFields['debitAmount']);
        unset($data->listFields['creditQuantity']);
        unset($data->listFields['creditAmount']);
        unset($data->listFields['blQuantity']);
        unset($data->listFields['debitQuantityCompare']);
        unset($data->listFields['debitAmountCompare']);
        unset($data->listFields['creditQuantityCompare']);
        unset($data->listFields['creditAmountCompare']);
        unset($data->listFields['blQuantityCompare']);

        if($mvc->innerForm->compare == 'yes'){
	        $data->listFields['blAmount'] = "Сега->Сума";
	        $data->listFields['blAmountCompare'] = "Преди->Сума";
	    } else {
	    	$data->listFields['blAmount'] = "Сума";
	    	$data->listFields['delta'] = "Дял";
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

        unset($innerState->recs);
    }


    /**
     * Коя е най-ранната дата на която може да се активира документа
     */
    public function getEarlyActivation()
    {
        $today = dt::today();
    	$activateOn = "{$today} 13:59:59";

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

        $exportFields['item3']  = "Сделки";
        $exportFields['blAmount']  = "Сума";
        $exportFields['delta']  = "Дял";

        return $exportFields;
    }

}