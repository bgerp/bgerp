<?php



/**
 * Мениджър на отчети от Приходи от продажби по продукти
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_SaleArticlesReport extends frame_BaseDriver
{
    
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';
    
    
    /**
     * Заглавие
     */
    public $title = 'Счетоводство->Приходи от продажби на Стоки и Продукти ';
    
    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 50;
    
    
    /**
     * Работен кеш
     */
    protected $cache = array();
    
    
    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Fieldset $fieldset
     */
    public function addEmbeddedFields(core_Form &$form)
    {
    	$form->FLD('accountId', 'acc_type_Account', 'caption=Сметка,input=hidden');
    	$form->FLD('from', 'date', 'caption=От,mandatory');
    	$form->FLD('to', 'date', 'caption=До,mandatory');
    }
    
    
    /**
     * Подготвя формата за въвеждане на данни за вътрешния обект
     *
     * @param core_Form $form
     */
    public function prepareEmbeddedForm(core_Form &$form)
    {
    	
    	$query = acc_Accounts::getQuery();
    	$query->where("#num = '701'");
    	$rec = $query->fetch();
    	$form->setDefault('accountId', "{$rec->id}");
    	
    }
    
    
    /**
     * Проверява въведените данни
     *
     * @param core_Form $form
     */
    public function checkEmbeddedForm(core_Form &$form)
    {
    	if($form->isSubmitted()){
    		if($form->rec->to < $form->rec->from){
    		     $form->setError('to, from', 'Началната дата трябва да е по малка от крайната');
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
    	$data = new stdClass();
    	$fRec = $data->rec = $this->innerForm;
        //$data->rec = $this->innerForm;
        
        $accSysId = acc_Accounts::fetchField($data->rec->accountId, 'systemId');
        $Balance = new acc_ActiveShortBalance(array('from' => $fRec->from, 'to' => $fRec->to, 'accs' => $accSysId, 'cacheBalance' => FALSE));
        $data->recs = $Balance->getBalance($accSysId);
      
        if(count($data->recs)){
        	foreach ($data->recs as $rec){
        		foreach (range(1, 3) as $i){
        			if(!empty($rec->{"ent{$i}Id"})){
        				$this->cache[$rec->{"ent{$i}Id"}] = $rec->{"ent{$i}Id"};
        			}
        		}
        	}
        	
        	if(count($this->cache)){
	        	$iQuery = acc_Items::getQuery();
	            $iQuery->show("num");
	            $iQuery->in('id', $this->cache);
	            
	            while($iRec = $iQuery->fetch()){
	                $this->cache[$iRec->id] = $iRec->num;
	            }
        	}
        }
      
        return $data;
    }
    
    
    /**
     * След подготовката на показването на информацията
     */
    public function on_AfterPrepareEmbeddedData($mvc, &$res)
    {

    }
    
    
    /**
     * Рендира вградения обект
     *
     * @param stdClass $data
     */
    public function renderEmbeddedData($data)
    {
    	if(empty($data)) return;
    	
    	$tpl = new ET("
            <h1>Приходи от продажби на Стоки и Продукти </h1>
            [#FORM#]
 
    		[#PAGER#]
            [#ARTICLE#]
        "
    	);
    	
    	$form = cls::get('core_Form');
    	
    	$this->addEmbeddedFields($form);
    	
    	$form->rec = $data->rec;
    	$form->class = 'simpleForm';
    	 
    	$tpl->prepend($form->renderStaticHtml(), 'FORM');
    	
    	$tpl->placeObject($data->rec);
    	
    	$pager = cls::get('core_Pager',  array('pageVar' => 'P_' .  $this->EmbedderRec->that,'itemsPerPage' => $this->listItemsPerPage));
    	$pager->itemsCount = count($data->recs, COUNT_RECURSIVE);
    
    	$f = cls::get('core_FieldSet');
    	
    	$f->FLD('article', 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', 'caption=Продукт->Тип');
    	$f->FLD('baseAmount', 'double', 'caption=Начално салдо->ДК');
    	$f->FLD('debitAmount', 'double', 'caption=Обороти->Дебит');
    	$f->FLD('creditAmount', 'double', 'caption=Обороти->Кредит');
    	$f->FLD('blAmount', 'double', 'caption=Крайно салдо->ДК');
    	
    	 
    	$rows = array();
    	
    	$ft = $f->fields;
    	
    	$amountType = $ft['baseAmount']->type;
    	$amountType->params['decimals'] = 2;
    
    	foreach ($data->recs as $accountsId => $rec) {
    		if (!$pager->isOnPage()) continue;
    		
    		$product = substr($accountsId, -2 );
    		$row = new stdClass();
    		
    		foreach (range(1, 3) as $i) {
	    		if(!empty($rec->{"ent{$i}Id"})){
	    				$row->article = acc_Items::getVerbal($rec->{"ent{$i}Id"}, 'titleLink');
	    		}
    		}

    		
    		if ($rec->baseAmount < 0) {
    			$row->baseAmount = "<span style='color:red'>{$amountType->toVerbal($rec->baseAmount)}</span>";
    		} else {
    			$row->baseAmount = $amountType->toVerbal($rec->baseAmount);
    		}
    				
    		if ($rec->debitAmount < 0) {
    			$row->debitAmount = "<span style='color:red'>{$amountType->toVerbal($rec->debitAmount)}</span>";
    		} else {
    			$row->debitAmount = $amountType->toVerbal($rec->debitAmount);
    		}
    				
    		if ($rec->creditAmount < 0) {
    			$row->creditAmount = "<span style='color:red'>{$amountType->toVerbal($rec->creditAmount)}</span>";
    		} else {
    			$row->creditAmount = $amountType->toVerbal($rec->creditAmount);
    		}
    				
    		if ($rec->blAmount < 0) {
    			$row->blAmount = "<span style='color:red'>{$amountType->toVerbal($rec->blAmount)}</span>";
    		} else {
    			$row->blAmount = $amountType->toVerbal($rec->blAmount);
    		}
    			
    		
    		$rows[] = $row;
    	}
    	
    	$table = cls::get('core_TableView', array('mvc' => $f));
    	$html = $table->get($rows, 'article=Продукт->Тип,baseAmount=Начално салдо->ДК,debitAmount=Обороти->Дебит,creditAmount=Обороти->Кредит,blAmount=Крайно салдо->ДК');

    	$tpl->append($pager->getHtml(), 'PAGER');
    	$tpl->append($html, 'ARTICLE'); 	

    	return  $tpl;
    	
    }
      
      
      /**
       * Скрива полетата, които потребител с ниски права не може да вижда
       *
       * @param stdClass $data
       */
      public function hidePriceFields()
      {

      }
      
      
      /**
       * Коя е най-ранната дата на която може да се активира документа
       */
      public function getEarlyActivation()
      {
      	  $activateOn = "{$this->innerForm->to} 23:59:59";
      	  	
      	  return $activateOn;
      }
}