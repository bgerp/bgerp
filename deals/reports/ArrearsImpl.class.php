<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за направата на справка
 * продажби по артикули
 *
 * @category  bgerp
 * @package   sales
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_reports_ArrearsImpl extends frame_BaseDriver
{


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo,sales,cash, bank, store';


    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';


    /**
     * Заглавие
     */
    public $title = 'Сделки » Задължения и просрочия';


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
        $form->FLD('from', 'date(allowEmpty)', 'caption=Към,input,mandatory');
        $form->FLD('amout', 'double', 'caption=Не показвай под,unit=лв.');
        $form->FLD('dealerId', 'userList(rolesForAll=sales|ceo,allowEmpty,roles=ceo|sales)', 'caption=Търговец');
        

        $this->invoke('AfterAddEmbeddedFields', array($form));
    }
    
    
    /**
     * Подготвя формата за въвеждане на данни за вътрешния обект
     *
     * @param core_Form $form
     */
    public function prepareEmbeddedForm(core_Form &$form)
    {
        // Дефолт периода е текущия ден
        $today = dt::today();
        	
        $form->setDefault('from',date('Y-m-01', strtotime("-1 months", dt::mysql2timestamp(dt::now()))));
        
        $form->setDefault('amount', '1');

        $this->inputForm($form);
    
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
        $data->summary = new stdClass();
    
        $data->rec = $this->innerForm;
        $this->prepareListFields($data);
        
        $dealerId = keylist::toArray($data->rec->dealerId);
        $dealerArr = implode(',',  $dealerId);

        $querySales = sales_Sales::getQuery();
        if ($data->rec->dealerId) {
            $querySales->where("#state = 'active' AND #valior <= '{$data->rec->from}' AND #dealerId IN ({$dealerArr})");
        } else {
            $querySales->where("#state = 'active' AND #valior <= '{$data->rec->from}'");
        }

        while ($recSale = $querySales->fetch()) {
            // Правим заявка към "Продажбите"
            $query = sales_Invoices::getQuery();
            $query->where("#threadId = '{$recSale->threadId}' AND #type = 'invoice' AND #state = 'active'");
            $query->orderBy("#date", "DESC");
            
            $uDelay = '';
            $delay1 = '';
            $delay2 = '';
            $delay3 = '';
            
            while ($recInvoices = $query->fetch()) { 

                if ($recInvoices->dueDate !== NULL) {
                    $date = $recInvoices->dueDate;
                } else {
                    $date = $recInvoices->date;
                }

                if ($date >= $data->rec->from) {
                    $uDelay = $recSale->amountBl;
                }
                
                if ($date <= $data->rec->from) { 
                    $days = dt::daysBetween($data->rec->from,$date);
                    
                    if ($days >= 0 && $days <= 15) {
                        $delay1 = $recSale->amountBl;
                    }
                    
                    if ($days >= 16 && $days <= 60) {
                        $delay2 = $recSale->amountBl;
                    }
                    
                    if ($days > 60) { 
                        $delay3 = $recSale->amountBl;
                    }
                }

                $dealer = $recSale->dealerId;
          
                $data->recs[] = (object) array(
                                'count' => '',
                                'contragentId' => $recInvoices->contragentId,
                                'contragentClassId' => $recInvoices->contragentClassId,
                                'invoice' => $recInvoices->id,
                                'invoiceNum' => $recInvoices->number,
                                'dealer' => $dealer,
                                'uDelay' => $uDelay,
                                'delay1' => $delay1,
                                'delay2' => $delay2,
                                'delay3' => $delay3,
                                'amount' => '',
                );
            }
        }
 

        // За всички генерирани елементи
        // изчисляваме средната ед. цена
        foreach($data->recs as $id => $recs){ 
            $recs->amount = $recs->uDelay + $recs->delay1 + $recs->delay2 + $recs->delay3;
            
            // Сумираме всички суми
            foreach (array('uDelay', 'delay1', 'delay2', 'delay3', 'amount') as $fld){
                if(!is_null($recs->$fld)){
                    $data->summary->$fld += $recs->$fld;
                }
            }
        }

        arr::order($data->recs, 'amount', strtoupper('DESC'));

        foreach($data->recs as $id=>$r) {

            if($r->amount <= $data->rec->amount || $r->amount == '') {
               
                unset($data->recs[$id]);
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
        
        if(!Mode::is('printing')){	
            $pager = cls::get('core_Pager',  array('itemsPerPage' => $mvc->listItemsPerPage));
            $pager->setPageVar($mvc->EmbedderRec->className, $mvc->EmbedderRec->that);
            $pager->addToUrl = array('#' => $mvc->EmbedderRec->instance->getHandle($mvc->EmbedderRec->that));
           
            $pager->itemsCount = count($data->recs, COUNT_RECURSIVE);
            $data->pager = $pager;
        }
        
        $id = 1;
        if(count($data->recs)){
   
            foreach ($data->recs as $rec) { 
                $rec->count = 1;

                $rec->count = $id++;
                if(!Mode::is('printing')){
                    if(!$pager->isOnPage()) continue;
                }
                
                $row = $mvc->getVerbal($rec);
            
                $data->rows[] = $row;
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
        $tpl = getTplFromFile('deals/tpl/ArrearsReportLayout.shtml');
    	
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
    
        $tpl->prepend($form->renderStaticHtml(), 'FORM');
                     
        $tpl->placeObject($data->rec);

        $f = $this->getFields();
          
        $table = cls::get('core_TableView', array('mvc' => $f));
        $tpl->append($table->get($data->rows, $data->listFields), 'CONTENT');
        
        $data->summary = $this->getVerbalSummary($data->summary);
        $data->summary->colspan = count($data->listFields);
        if(count($data->rows)){
            $data->summary->colspan -= 5;
            $afterRow = new core_ET("<tr  style = 'background-color: #eee'><td colspan=[#colspan#]><b>" . tr('ОБЩО') . "</b></td><td style='text-align:right'><b>[#uDelay#]</b></td><td style='text-align:right'><b>[#delay1#]</b></td><td style='text-align:right'><b>[#delay2#]</b></td><td style='text-align:right'><b>[#delay3#]</b></td><td style='text-align:right'><b>[#amount#]</b></td></tr>");
        }
        
        if($afterRow){
            $afterRow->placeObject($data->summary);
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
	    if (!isset($data->rec->dealerId)) {
    	   $data->listFields = array(
    	        'count' => '№',
                'contragent' => 'Контрагент',
    	        'invoice' => 'Фактура',
    	        'dealer' => 'Търговец',
       	        'uDelay' => 'Без закъснение',
    	        'delay1' => '0-15 дни',
    	        'delay2' => '16-60 дни',
    	        'delay3' => '60+ дни',
    	        'amount' => 'Общо',
    	    );
	    } else {
	        $data->listFields = array(
	            'count' => '№',
	            'contragent' => 'Контрагент',
	            'invoice' => 'Фактура',
	            'uDelay' => 'Без закъснение',
	            'delay1' => '0-15 дни',
	            'delay2' => '16-60 дни',
	            'delay3' => '60+ дни',
	            'amount' => 'Общо',
	        );
	    }
	}
	
	/**
	 * Вербалното представяне на ред от таблицата
	 */
	private function getVerbalSummary($rec)
	{
	    $Double = cls::get('type_Double');
	    $Double->params['decimals'] = 2;
	    
	    $row = new stdClass();
	    
	    foreach (array('uDelay', 'delay1', 'delay2', 'delay3', 'amount') as $fld){
	        if (isset($rec->{$fld})) {
	            $row->{$fld} = $Double->toVerbal($rec->{$fld});
	        }
	    }
	    
	    return $row;
	}


    /**
	 * Вербалното представяне на ред от таблицата
	 */
	 private function getVerbal($rec)
	 {
	    $Int = cls::get('type_Int');
	    $Double = cls::get('type_Double');
	    $Double->params['decimals'] = 2;

	    $row = new stdClass();

	    $row->count = $Int->toVerbal($rec->count);

	    $row->contragent = cls::get($rec->contragentClassId)->getShortHyperLink($rec->contragentId);

	    if ($rec->invoice) {
	        if (strlen($rec->invoiceNum) < 10) {
	            $number = str_pad($rec->invoiceNum, '10', '0', STR_PAD_LEFT);
	        } else {
	            $number = $rec->invoiceNum;
	        }
	       
	        $url = toUrl(array('sales_Invoices','single', $rec->invoice),'absolute');
	        $row->invoice = ht::createLink($number,$url,FALSE, array('ef_icon' => 'img/16/invoice.png'));
	    }

        $row->dealer = crm_Profiles::createLink($rec->dealer, $row->dealer);

	    foreach (array('uDelay', 'delay1', 'delay2', 'delay3', 'amount') as $fld){
	       if (isset($rec->{$fld})) {
	           $row->{$fld} = $Double->toVerbal($rec->{$fld});
	       }
	    }

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
	    $activateOn = "{$this->innerForm->from} 23:59:59";
	    	
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
        
        $exportFields = $this->innerState->listFields;

        foreach($this->innerState->recs as $id => $rec) {
            $dataRecs[] = $this->getVerbal($rec);

            $dataRecs[$id]->count = $id + 1;
            $dataRecs[$id]->contragent = strstr($dataRecs[$id]->contragent, '&', TRUE);
            foreach (array('uDelay', 'delay1', 'delay2', 'delay3', 'amount', 'dealer', 'invoice') as $fld) {
                $dataRecs[$id]->$fld = $rec->$fld;
            }
        }
        $fields = $this->getFields();
        
        if($this->innerState->summary) {
            $afterRow = 'ОБЩО';

            foreach ($this->innerState->summary as $f => $value) {
                $rCsv = '';
                foreach ($exportFields as $field => $caption) {
                    if ($this->innerState->summary->{$field}) {
                       $value = $this->innerState->summary->{$field};
                       $rCsv .= $value. ",";
                    } else {
                        $rCsv .= '' . ",";
                    }
                }
            }
        }

    	$csv = csv_Lib::createCsv($dataRecs, $fields, $exportFields);
    	$csv .= "\n".$afterRow.$rCsv;

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
		$f->FLD('count', 'int','tdClass=smartCenter');
		$f->FLD('contragent', 'varchar','tdClass=smartCenter');
        $f->FLD('invoice', 'key(mvc=sales_Invoices,select=number)', 'tdClass=smartCenter');
        $f->FLD('dealer', 'key(mvc=core_Users,select=names)', 'tdClass=smartCenter');
        $f->FLD('uDelay', 'double', 'tdClass=smartCenter');
        $f->FLD('delay1', 'double', 'tdClass=smartCenter');
        $f->FLD('delay2', 'double', 'tdClass=smartCenter');
        $f->FLD('delay3', 'double', 'tdClass=smartCenter');
        $f->FLD('amount', 'double', 'tdClass=smartCenter');
    
    	return $f;
    }
}