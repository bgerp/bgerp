<?php


/**
 * Мениджър за "Средства за плащане" 
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class cond_Payments extends core_Manager {
	
	
	/**
	 * Интерфейси, поддържани от този мениджър
	 */
	public $interfaces = 'cond_PaymentAccRegIntf';
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'pos_Payments';
	
	
    /**
     * Заглавие
     */
    public $title = "Безналични средства за плащане";
    
    
    /**
     * Заглавие на единичния обект
     */
    public $singleTitle = 'безналично средство за плащане';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, plg_State2, cond_Wrapper, acc_plg_Registry';

    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, title, change, code, state';
    
    
    /**
     * Кой може да променя?
     */
    public $canWrite = 'no_one';
    
    
    /**
	 * Кой може да променя състоянието на валутата
	 */
    public $canChangestate = 'no_one';
    
    
    /**
     * Кой може да го отхвърли?
     */
    public $canReject = 'ceo,admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,admin';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,admin';
	
	
	/**
	 * В коя номенклатура да се добави при активиране
	 */
	public $addToListOnActivation = 'nonCash';
	
	
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('title', 'varchar(255)', 'caption=Наименование');
    	$this->FLD('change', 'enum(yes=Да,no=Не)', 'caption=Ресто?,value=no,tdClass=centerCol');
    	$this->FLD('code', 'int', 'caption=Код,mandatory,tdClass=centerCol');
    	
    	$this->setDbUnique('title');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
    	$file = "cond/csv/Pospayments.csv";
    	 
    	$fields = array(
    			0 => "title",
    			1 => "state",
    			2 => "change",
    			3 => "code",);
    	 
    	$cntObj = csv_Lib::importOnce($this, $file, $fields);
    	 
    	$res = $cntObj->html;
    	 
    	return $res;
    }
    
    
    /**
     * Връща масив от обекти, които са ид-та и заглавията на методите
     * @return array $payments
     */
    public static function fetchSelected()
    {
    	$payments = array();
    	$query = static::getQuery();
	    $query->where("#state = 'active'");
	    $query->orderBy("code");
	    while($rec = $query->fetch()) {
	    	$payment = new stdClass();
	    	$payment->id = $rec->id;
	    	$payment->title = $rec->title;
	    	$payments[] = $payment;
	    }
	    
    	return $payments;
    }
    
    
    /**
     *  Метод отговарящ дали даден платежен връща ресто
     *  @param int $id - ид на метода
     *  @return boolean $res - дали връща или не връща ресто
     */
    public static function returnsChange($id)
    {
    	expect($rec = static::fetch($id), 'Няма такъв платежен метод');
    	($rec->change == 'yes') ? $res = TRUE : $res = FALSE;
    	
    	return $res;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    public static function getItemRec($objectId)
    {
    	$self = cls::get(__CLASS__);
    	$result = NULL;
    
    	if ($rec = $self->fetch($objectId)) {
    		$result = (object)array(
    				'num' => $rec->id . " pm",
    				'title' => $rec->title,
    		);
    	}
    
    	return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    public static function itemInUse($objectId)
    {
    	// @todo!
    }
}