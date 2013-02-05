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
    var $canWrite = 'admin, pos';
    
    
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
     * Екшъна по подразбиране е разглеждане на статиите
     */
    function act_Default()
    {
        return Redirect(array($this, 'single'));
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$form->setDefault('pointId', pos_Points::getCurrent());
    	$form->setReadOnly('pointId');
    }
    
    
	/**
     * Извиква се след въвеждането на данните
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()) {
	    	//$rec = &$form->rec;
	    	//$rec->date = dt::now();
	    	//$rec->total = 0;
    	}
    }
    
    
	/**
     * Извиква се преди изпълняването на екшън
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
    	if($action == 'single') {
    		$query = static::getQuery();
    		if($query->count() != 0) {
    			return;
    		}
    		
    		$rec = new stdClass();
    		$rec->date = dt::now();
    		$rec->contragentName = 'Анонимен Клиент';
    		$rec->total = 0;
    		$rec->pointId = pos_Points::getCurrent();
    		
    		if($id = static::save($rec)) {
    			
    			return Redirect(array($mvc, 'single', $id));
    		}
    	}
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
     * Пушваме css файла
     */
    static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {	
    	jquery_Jquery::enable($tpl);
    	$tpl->push('pos/tpl/css/styles.css', 'CSS');
    }
}