<?php



/**
 * Мениджър за "Бележки за продажби" 
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class pos_Receipts extends core_Master {
    
    
	/**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'acc_TransactionSourceIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Бележки за продажба";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_Rejected, plg_Printing,
    				 plg_State, pos_Wrapper, doc_SequencerPlg, bgerp_plg_Blank';

    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Бележка за продажба";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, number, date, contragentName, total, createdOn, createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsSingleField = 'number';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
	 * Коментари на статията
	 */
	var $details = 'pos_ReceiptDetails';
	
	
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'admin, pos';
    
    
    /**
     * Кой може да променя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой може да променя?
     */
    var $canEdit = 'pos, admin';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'admin, pos';
    
	
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'pos/tpl/SingleReceipt.shtml';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('pointId', 'key(mvc=pos_Points, select=title)', 'caption=Точка на Продажба');
    	$this->FLD('date', 'date(format=d.m.Y)', 'caption=Дата, input=none');
    	$this->FLD('number', 'int', 'caption=Номер, input=none');
    	$this->FLD('contragentName', 'varchar(255)', 'caption=Контрагент,input=none');
    	$this->FLD('contragentObjectId', 'int', 'input=none');
    	$this->FLD('contragentClass', 'key(mvc=core_Classes,select=name)', 'input=none');
    	$this->FLD('total', 'float', 'caption=Общо, input=none');
    	$this->FLD('paid', 'float', 'caption=Платено, input=none');
    	$this->FLD('change', 'float', 'caption=Ресто, input=none');
    	$this->FLD('tax', 'float', 'caption=Такса, input=none');
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Активиран, rejected=Оттеглен)', 
            'caption=Статус, input=none'
        );
    }
    
    
	/**
     * Екшъна по подразбиране, Дефолт Екшъна е "Single"
     */
    function act_Default()
    {
        return Redirect(array($this, 'single'));
    }
    
    
	/**
     * Извиква се преди изпълняването на екшън
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
    	$id = Request::get('id');
    	if($action == 'single' && !$id) {
    		
    			// Ако не е зададено Ид, намираме кой е последно добавената бележка
	    		$cu = core_Users::getCurrent();
    			$query = static::getQuery();
	    		$query->where("#createdBy = {$cu}");
	    		$query->orderBy("#createdOn", "DESC");
	    		if($rec = $query->fetch()) {
	    			
	    			return Redirect(array($mvc, 'single', $rec->id));
	    		}
    		
	    	// Ако няма последно добавена бележка създаваме нова
    		return Redirect(array($mvc, 'new'));
    	}
    }
    
    
    /**
     *  Екшън създаващ нова бележка, и редиректващ към Единичния и изглед
     *  Добавянето на нова бележка става само през този екшън 
     */
    function act_New()
    {
    	$rec = new stdClass();
    	$pos = pos_Points::getCurrent();
    	$rec->date = dt::now();
    	$rec->contragentName = tr('Анонимен Клиент');
    	$rec->contragentClass = core_Classes::getId('crm_Persons');
    	$rec->contragentObjectId = pos_Points::defaultContragent($pos);
    	$rec->total = 0;
    	$rec->paid = 0;
    	$rec->change = 0;
    	$rec->pointId = $pos;
    	
    	$this->requireRightFor('add', $rec);
    	$id = static::save($rec);
    	
    	return Redirect(array($this, 'single', $id));
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->number = $mvc->abbr . $row->number;
    	$double = cls::get('type_Double');
    	$double->params['decimals'] = 2;
    	$row->total = $double->toVerbal($rec->total);
    	$row->paid = $double->toVerbal($rec->paid);
    	$row->change = $double->toVerbal($rec->change);
    }

    
	/**
     * След подготовка на тулбара на единичен изглед.
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	if($mvc->haveRightFor('list')) {
    		
    		// Добавяме бутон за достъп до 'List' изгледа
    		$data->toolbar->addBtn('Всички', array($mvc, 'list', 'ret_url' => TRUE),
    							   'ef_icon=img/16/application_view_list.png, order=18');    
    								 
    	}
    	
    	// Добавяне на бутон за създаване на нова дефолт Бележка
    	$data->toolbar->addBtn('Нова Бележка', 
    						    array($mvc, 'new'),'',
    						   'id=btnAdd,class=btn-add,order=20');
    	
    	if(haveRole('pos,admin')) {
	    $data->toolbar->addBtn('Приключи', array(
	                			   'acc_Journal',
	                               'conto',
	                               'docId' => $data->rec->id,
	                               'docType' => $mvc->className,
	                               'ret_url' => TRUE), '', 'order=34');
    	}
    }
    
    
    /**
     * Пушваме css и js файловете
     */
    static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {	
    	jquery_Jquery::enable($tpl);
    	jquery_Jquery::enableUI($tpl);
    	$tpl->push('pos/tpl/css/styles.css', 'CSS');
    	$tpl->push('pos/js/scripts.js', 'JS');
    }
    
    
    /**
     * Извлича информацията за всички продукти които са продадени чрез
     * тази бележки, във вид подходящ за контирането
     * @param int id - ид на бележката
     * @return array $products - Масив от продукти
     */
    static function fetchProducts($id)
    {
    	expect($rec = static::fetch($id), 'Несъществуваща бележка');
    	$products = array();
    	$currencyId = currency_Currencies::getIdByCode();
    	
    	$query = pos_ReceiptDetails::getQuery();
    	$query->where("#receiptId = {$id}");
    	$query->where("#action LIKE '%sale%'");
    	while($rec = $query->fetch()) {
    		$products[] = (object) array(
    			'productId' =>$rec->productId,
	    		'contragentClassId' => $rec->contragentClass,
	    		'contragentId' => $rec->contragentObjectId,
    			'currencyId' => $currencyId,
	    		'amount' => $rec->amount,
	    		'quantity' => $rec->quantity);
    	}
    	
    	return $products;
    }
    
    
    /**
     * Ъпдейтва бележката след като и се създаде нов детайл
     * @param stdClass $detailRec - запис от pos_ReceiptDetails
     */
    function updateReceipt($detailRec)
    {
    	expect($rec = $this->fetch($detailRec->receiptId));
    	$action = explode("|", $detailRec->action);
    	switch($action[0]) {
    		case 'sale':
    			$rec->total = $this->countTotal($rec->id);
    			break;
    		case 'discount':
    			break;
    		case 'payment':
    			$rec->paid = $this->countPaidAmount($rec->id);
    			$rec->change = $rec->paid - $rec->total;
    			break;
    		case 'client':
    			break;
    	}
    	
    	$this->save($rec);
    }
    
    
    /**
     * Изчислява всичко платено до момента
     * @param int $id - запис от модела
     * @return double $paid;
     */
    function countPaidAmount($id)
    {
    	$paid = 0;
    	$query = pos_ReceiptDetails::getQuery();
    	$query->where("#receiptId = {$id}");
    	$query->where("#action LIKE '%payment%'");
    	while($dRec = $query->fetch()) {
    		$paid += $dRec->amount;
    	}
    	
    	return $paid;
    }
    
    
    /**
     * Изчислява дължимата сума
     * @param int $id
     * @return double $total;
     */
    function countTotal($id)
    {
    	$total = 0;
    	$query = pos_ReceiptDetails::getQuery();
    	$query->where("#receiptId = {$id}");
    	$query->where("#action LIKE '%sale%'");
    	while($dRec = $query->fetch()) {
    		$total += $dRec->amount;
    	}
    	
    	return $total;
    }
    
    
    /**
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{ 
		if($action == 'add' && isset($rec)) {
			$res = 'pos, ceo, admin';
		}
	}
	
	
	/**
   	 *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
   	 *  Създава транзакция която се записва в Журнала, при контирането
   	 */
    public static function getTransaction($id)
    {
    	expect($rec = static::fetch($id));
    	$products = static::fetchProducts($id);
    	foreach ($products as $product) {
    		$currencyCode = currency_Currencies::getCodeById($product->currencyId);
    		$amount = currency_CurrencyRates::convertAmount($product->amount, $rec->date, $currencyCode);
	    	
    		$entries[] = array(
	        'amount' => $amount, // Стойност на продукта за цялото количество, в основна валута
	        
	        'debit' => array(
	            '411', // Сметка "411. Вземания от клиенти"
	                array($product->contragentClassId, $product->contragentId), // Перо 1 - Клиент
	                //array('pos_Receipts', $id),              // Перо 2 - Документ-продажба
	                array('currency_Currencies', $product->currencyId),     // Перо 3 - Валута
	            
	                'quantity' => $product->amount, // "брой пари" във валутата на продажбата
	        ),
	        
	        'credit' => array(
	            '702', // Сметка "702. Приходи от продажби на стоки"
	                //array('cat_Products', $product->productId), // Перо 1 - Продукт
	                array('pos_Receipts', $id),  // Перо 2 - Документ-продажба
	            'quantity' => $product->quantity, // Количество продукт в основната му мярка
	        ),
	    );
    	}
    	
    	$transaction = (object)array(
                'reason'  => 'PoS Продажба #' . $rec->id,
                'valior'  => $rec->date,
                'entries' => $entries, 
            );
      
      bp($transaction);
      return $transaction;
    	//@TODO
    }
    
    
	/**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public static function finalizeTransaction($id)
    {
        $rec = (object)array(
            'id' => $id,
            'state' => 'active'
        );
        
        return self::save($rec);
    }
    
	
    /**
     * Предефиниране на наследения метод act_Single
     */
    function act_Single()
    {      
        $this->requireRightFor('single');
    	$id = Request::get('id');
        if(!$id) {
        	$id = Request::get('receiptId');
        }
        $data = new stdClass();
        expect($data->rec = $this->fetch($id));
        $this->requireRightFor('single', $data->rec);
        
        $this->prepareSingle($data);
        
        if($dForm = $data->pos_ReceiptDetails->form) {
            $rec = $dForm->input();
            $Details = cls::get('pos_ReceiptDetails');
			$Details->invoke('AfterInputEditForm', array($dForm));
           
        	// Ако формата е успешно изпратена - запис, лог, редирект
            if ($dForm->isSubmitted() && Request::get('ean')) {
            	 
            	// Записваме данните
            	$id = $Details->save($rec);
                $Details->log('add', $id);
                
                return new Redirect(array($this, 'Single', $data->rec->id));
            }
        }
       
        $tpl = $this->renderSingle($data);
        $tpl = $this->renderWrapping($tpl, $data);
        $this->log('Single: ' . ($data->log ? $data->log : tr($data->title)), $id);
        
        return $tpl;
    }
    
    
    /**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::rejectTransaction
     */
    public static function rejectTransaction($id)
    {
        $rec = self::fetch($id, 'id,state,valior');
        
        if ($rec) {
            static::reject($id);
        }
    }
}