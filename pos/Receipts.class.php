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
	    		$query = static::getQuery();
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
    	$rec->date = dt::now();
    	$rec->contragentName = 'Анонимен Клиент';
    	$rec->total = 0;
    	$rec->pointId = pos_Points::getCurrent();
    	
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
    }
    
    
    static function on_AfterPrepareSingle($mvc, $res, $data)
    {	
    	//@TODO
    }
    
    
	/**
     * След подготовка на тулбара на единичен изглед.
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	if($mvc->haveRightFor('list')) {
    		
    		// Добавяме бутон за достъп до 'List' изгледа
    		$data->toolbar->addBtn('Всички',array($mvc, 'list', 'ret_url' => TRUE),
    							   'ef_icon=img/16/application_view_list.png, order=18');    
    								 
    	}
    	
    	// Добавяне на бутон за създаване на нова дефолт Бележка
    	$data->toolbar->addBtn('Нова Бележка', 
    						    array($mvc, 'new'),'',
    						   'id=btnAdd,class=btn-add,order=20');
    }
    
    
    /**
     * Пушваме css файла
     */
    static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {	
    	jquery_Jquery::enable($tpl);
    	$tpl->push('pos/tpl/css/styles.css', 'CSS');
    	$tpl->push('pos/js/scripts.js', 'JS');
    }
    
    
    /**
     * 
     */
    function updateReceipt($detailRec)
    {
    	expect($rec = $this->fetch($detailRec->receiptId));
    	switch($detailRec->param) {
    		case 'sale':
    			$rec->total = 0;
    			$query = pos_ReceiptDetails::getQuery();
    			$query->where("#receiptId = {$rec->id}");
    			$query->where("#param = 'sale'");
    			while($dRec = $query->fetch()) {
    				$rec->total .= $dRec->amount;
    			}
    			break;
    		case 'discount':
    			break;
    		case 'payment':
    			break;
    		case 'client':
    			break;
    	}
    	
    	$this->save($rec);
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
}