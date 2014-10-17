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
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'pos_Payments';
	
	
    /**
     * Заглавие
     */
    var $title = "Средства за плащане";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_State2, cond_Wrapper';

    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, title, change, code, state';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'ceo, cond';
    
    
    /**
     * Кой може да променя?
     */
    var $canWrite = 'ceo, cond';
    
    
    /**
	 * Кой може да променя състоянието на валутата
	 */
    var $canChangestate = 'ceo,cond,admin';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'ceo, cond';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,cond';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,cond';
    

	/**
	 * Какъв е кода на плащането в брой
	 */
	public static $cashCode = '0';
	
	
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('title', 'varchar(255)', 'caption=Наименование');
    	$this->FLD('change', 'enum(yes=Да,no=Не)', 'caption=Ресто?,value=no');
    	$this->FLD('code', 'int', 'caption=Код,mandatory');
    	
    	$this->setDbUnique('title');
    }
    
    
    /**
     * Записи за инициализиране на таблицата
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	$file = "cond/csv/Pospayments.csv";
    	
    	$fields = array(
	    	0 => "title", 
	    	1 => "state", 
	    	2 => "change",
    		3 => "code",);
    	
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields);
    	
    	$res .= $cntObj->html;
    	
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
}