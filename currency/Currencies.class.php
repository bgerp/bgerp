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
    //var $loadList = 'plg_Created, plg_RowTools, currency_Wrapper, acc_plg_Registry,
    //                 CurrencyGroups=currency_CurrencyGroups,  plg_Sorting, plg_State2';
                     
    var $loadList = 'plg_Created, plg_RowTools, currency_Wrapper,
                     CurrencyGroups=currency_CurrencyGroups,  plg_Sorting, plg_State2';
    
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
     * Заглавие
     */
    var $title = 'Списък с всички валути';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "id, name, code, lastUpdate, lastRate, state, createdOn, createdBy";
    
    
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
        $this->FLD('groups', 'keylist(mvc=currency_CurrencyGroups, select=name)', 'caption=Групи');
        
        $this->setDbUnique('name');
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
     * Валута по подразбиране според клиента
     * 
     * @see doc_ContragentDataIntf
     * @param stdClass $contragentInfo
     * @return int key(mvc=currency_Currencies) 
     */
    public static function getDefault($contragentInfo)
    {
        // @TODO
        return core_Packs::getConfig('currency')->CURRENCY_BASE_CODE;
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
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        if ($groupId = Request::get('groupId', 'int')) {
            
            $groupRec = $mvc->CurrencyGroups->fetch($groupId);
            
            // Полето 'groups' е keylist и затова имаме LIKE
            $data->query->where("#groups LIKE '%|{$groupId}|%'");
            
            // Сменяме заглавието
            $data->title = 'Валути в група "|*' . $groupRec->name . "\"";
        }
    }
    
    function on_BeforeRenderDetails($mvc, $res, &$data)
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
    static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $data->toolbar->removeBtn('btnAdd');
        
        $data->toolbar->addBtn('Нова валута', array($mvc, 'Add', 'groupId' => Request::get('groupId')));
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
        $currDefs = array("БЪЛГАРСКИ ЛЕВ|BGN|active",
            "ЕВРО|EUR|active",
            "ЩАТСКИ ДОЛАР|USD|active",
       	    "АВСТРАЛИЙСКИ ДОЛАР|AUD|closed",
            "БРАЗИЛСКИ РЕАЛ|BRL|closed",
            "КАНАДСКИ ДОЛАР|CAD|closed",
            "ШВЕЙЦАРСКИ ФРАНК|CHF|closed",
            "КИТАЙСКИ РЕНМИНБИ ЮАН|CNY|closed",
            "ЧЕШКА КРОНА|CZK|closed",
            "ДАТСКА КРОНА|DKK|closed",
            "БРИТАНСКА ЛИРА|GBP|closed",
            "ХОНГКОНГСКИ ДОЛАР|HKD|closed",
            "ХЪРВАТСКА КУНА|HRK|closed",
            "УНГАРСКИ ФОРИНТ|HUF|closed",
            "ИНДОНЕЗИЙСКА РУПИЯ|IDR|closed",
            "ИЗРАЕЛСКИ ШЕКЕЛ|ILS|closed",
            "ИНДИЙСКА РУПИЯ|INR|closed",
            "ЯПОНСКА ЙЕНА|JPY|closed",
            "ЮЖНОКОРЕЙСКИ ВОН|KRW|closed",
            "ЛИТОВСКИ ЛИТАС|LTL|closed",
            "ЛАТВИЙСКИ ЛАТ|LVL|closed",
            "МЕКСИКАНСКО ПЕСО|MXN|closed",
            "МАЛАЙЗИЙСКИ РИНГИТ|MYR|closed",
            "НОРВЕЖКА КРОНА|NOK|closed",
            "НОВОЗЕЛАНДСКИ ДОЛАР|NZD|closed",
            "ФИЛИПИНСКО ПЕСО|PHP|closed",
            "ПОЛСКА ЗЛОТА|PLN|closed",
            "НОВА РУМЪНСКА ЛЕЯ|RON|closed",
            "РУСКА РУБЛА|RUB|closed",
            "ШВЕДСКА КРОНА|SEK|closed",
            "СИНГАПУРСКИ ДОЛАР|SGD|closed",
            "ТАЙЛАНДСКИ БАТ|THB|closed",
            "ТУРСКА ЛИРА|TRY|closed",
            "ЮЖНОАФРИКАНСКИ РАНД|ZAR|closed");
            
        $insertCnt = 0;
        
        foreach($currDefs as $c) {
            
            $rec = new stdClass();
            
            list($rec->name, $rec->code, $rec->state) = explode('|', $c);
            
            if (!$this->fetch("#code = '{$rec->code}'")){
                $rec->lastUpdate = dt::verbal2mysql();
                
                if($rec->code == 'EUR') {
                    $rec->lastRate = 1;
                }
                
                $this->save($rec);
                
                $insertCnt++;
            }
        }
        
        if($insertCnt) {
            $res .= "<li>Добавени са запис/и за {$insertCnt} валути.</li>";
        }

        return $res;
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
     * @see crm_ContragentAccRegIntf::getLinkToObj
     * @param int $objectId
     */
    static function getLinkToObj($objectId)
    {
        $self = cls::get(__CLASS__);
        
        if ($rec = $self->fetch($objectId)) {
            $result = ht::createLink(static::getVerbal($rec, 'name'), array($self, 'Single', $objectId));
        } else {
            $result = '<i>неизвестно</i>';
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
