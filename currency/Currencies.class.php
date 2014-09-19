<?php



/**
 * Валутите
 *
 *
 * @category  bgerp
 * @package   currency
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class currency_Currencies extends core_Master {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf, currency_CurrenciesAccRegIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, currency_Wrapper, acc_plg_Registry,
                     plg_Sorting, plg_State2';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'currency/tpl/SingleLayoutCurrency.shtml';
    

    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Валута";


    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/zone_money.png';
    

    /**
     * Кой може да изтрива
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'ceo,admin,cash,bank,currency,acc';
    
    
    /**
     * Кой може да добавя?
     */
    var $canAdd = 'ceo,currency';
    
    
    /**
     * Кой може да редактира системните данни
     */
    var $canEditsysdata = 'ceo,currency,admin';
    
    
    /**
     * Кой може да променя?
     */
    var $canEdit = 'ceo,currency,admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'powerUser';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,currency,powerUser';
	
	
	/**
	 * Кой може да променя състоянието на валутата
	 */
    var $canChangestate = 'ceo,currency,admin';
    
    
    /**
     * Заглавие
     */
    var $title = 'Списък с всички валути';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "id, name, code, lastUpdate, lastRate, state";
    
    
    /**
     * Полето "name" да е хипервръзка към единичния изглед
     */
    var $rowToolsSingleField = 'name';


    /**
     * Полетата, които ще се показват в единичния изглед
     */
    var $singleFields = 'name, code, lastUpdate, lastRate, groups';
    
    
    /**
     * Детайли на модела
     */
    var $details = "currency_CurrencyRates";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Наименование,width=100%,mandatory');
        $this->FLD('code', 'varchar(3)', 'caption=Код,mandatory,width=60px');
        $this->FLD('lastUpdate', 'date', 'caption=Последно->обновяване, input=none');
        $this->FLD('lastRate', 'double', 'caption=Последно->курс, input=none');
        
        $this->setDbUnique('code');
    }


    /**
     * Връща id-то на валутата с посочения трибуквен ISO код
     * 
     * @param string $code трибуквен ISO код
     * @return int key(mvc=currency_Currencies)
     */
    public static function getIdByCode($code)
    {
        expect($id = self::fetchField(array("#code = '[#1#]'", $code), 'id'));
		
        return $id;
    }
    
    
    /**
     * Връща кода на валутата по зададено id
     *  
     * @param int $id key(mvc=currency_Currencies)
     * @return string $code - трибуквен ISO код на валутата
     */
    public static function getCodeById($id)
    {
        expect($code = self::fetchField($id, 'code'));

        return $code;
    }
    
    
    /**
     * Приготвяне на данните, ако имаме groupId от $_GET
     * В този случай няма да листваме всички записи, а само тези, които
     * имат в полето 'groups' groupId-то от $_GET
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        if ($groupId = Request::get('groupId', 'int')) {
            
            $groupRec = $mvc->CurrencyGroups->fetch($groupId);
            
            // Полето 'groups' е keylist и затова имаме LIKE
            $data->query->where("#groups LIKE '%|{$groupId}|%'");
            
            // Сменяме заглавието
            $data->title = 'Валути в група "|*' . $groupRec->name . "\"";
        }
    }
    
    
    /**
     * Преди рендиране на детайлите
     */
    public static function on_BeforeRenderDetails($mvc, $res, &$data)
    {
    	return FALSE;
    }
    
    /**
     * Смяна на бутона
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $data->toolbar->removeBtn('btnAdd');
        
        $data->toolbar->addBtn('Нова валута', array($mvc, 'Add', 'groupId' => Request::get('groupId', 'int')));
    }
    
    
    /**
     * Слагаме default за checkbox-овете на полето 'groups', когато редактираме групи на дадена валута
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        if (empty($data->form->rec->id) && ($groupId = Request::get('groupId', 'int'))) {
            $data->form->setDefault('groups', '|' . $groupId . '|');
        }
        
        if($data->form->rec->state == 'closed'){
        	$data->form->setField('lists', 'input=none');
        }
    }
    
    
    /**
     * Връща заглавието и мярката на перото за продукта
     *
     * Част от интерфейса: intf_Register
     */
    static function getAccItemRec($rec)
    {
        return (object) array('title' => $rec->code);
    }
    
    
	/**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
    	$file = "currency/csv/Currencies.csv";
    	$fields = array( 
	    	0 => "name", 
	    	1 => "csv_code", 
	    	2 => "state",);
    	
    	$cntObj = csv_Lib::importOnce($this, $file, $fields);
    	$res .= $cntObj->html;
    	
    	return $res;
    }
    
    
    
    /**
     * Изпълнява се преди импортирването на данните
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
    	if(isset($rec->csv_code) && strlen($rec->csv_code) != 0){
    		
    		// Ако данните идват от csv файл
    		$rec->code = $rec->csv_code;
    		
    		if(!$rec->id){
    			$rec->lastUpdate = dt::verbal2mysql();
    		}
    		
    		if($rec->code == 'EUR') {
               $rec->lastRate = 1;
            }
    	}
    }
    
    
    /**
     * След промяна на обект от регистър
     */
    function on_AfterSave($mvc, &$id, &$rec, $fieldList = NULL)
    {
    	if($rec->state == 'active'){
    		
    		// Ако валутата е активна, добавя се като перо
    		$rec->lists = keylist::addKey($rec->lists, acc_Lists::fetchField(array("#systemId = '[#1#]'", 'currencies'), 'id'));
    		acc_Lists::updateItem($mvc, $rec->id, $rec->lists);
    	} else {
			// Ако валутата НЕ е активна, перото се изтрива ("изключва" ако вече е използвано)
			$rec->lists = keylist::addKey($rec->lists, acc_Lists::fetchField(array("#systemId = '[#1#]'", 'currencies'), 'id'));
    		acc_Lists::removeItem($mvc, $rec->id, $rec->lists);
		}
    }
    
    
    /**
     * Функция за закръгляне на валута, която
     * трябва да се използва във всички бизнес документи за показване на суми
     * @param double $amount - сума
     * @param string(3) $code -трибуквен код на валута
     */
    public static function round($amount, $code = NULL)
    {
    	// Мокъп имплементация
    	//@TODO да не е мокъп
    	return round($amount, 2);
    }
    
    
    /*******************************************************************************************
     * 
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_ContragentAccRegIntf
     * 
     ******************************************************************************************/
    
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = NULL;
        
        if ($rec = $self->fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->code,
                'title' => $rec->name,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
        // @todo!
    }
    
    /**
     * КРАЙ НА интерфейса @see acc_RegisterIntf
     */

}