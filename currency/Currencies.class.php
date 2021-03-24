<?php


/**
 * Валутите
 *
 *
 * @category  bgerp
 * @package   currency
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class currency_Currencies extends core_Master
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'acc_RegisterIntf, currency_CurrenciesAccRegIntf, acc_RegistryDefaultCostIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, currency_Wrapper, acc_plg_Registry,
                     plg_Sorting, plg_State2';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'currency/tpl/SingleLayoutCurrency.shtml';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Валута';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/zone_money.png';
    
    
    /**
     * Кой може да изтрива
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да го прочете?
     */
    public $canRead = 'ceo,admin,cash,bank,currency,acc';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'ceo,admin,cash,bank,currency,acc';
    
    
    /**
     * Кой може да редактира системните данни
     */
    public $canEditsysdata = 'ceo,admin,cash,bank,currency,acc';
    
    
    /**
     * Кой може да променя?
     */
    public $canEdit = 'ceo,admin,cash,bank,currency,acc';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,cash,bank,currency,acc';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin,cash,bank,currency,acc';
    
    
    /**
     * Кой може да променя състоянието на валутата
     */
    public $canChangestate = 'ceo,currency,admin';
    
    
    /**
     * Заглавие
     */
    public $title = 'Списък с всички валути';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name, code, lastUpdate, lastRate, state';
    
    
    /**
     * Полето "name" да е хипервръзка към единичния изглед
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Полетата, които ще се показват в единичния изглед
     */
    public $singleFields = 'name, code, lastUpdate, lastRate, groups';
    
    
    /**
     * Детайли на модела
     */
    public $details = 'currency_CurrencyRates';
    
    
    /**
     * В коя номенклатура да се добави при активиране
     */
    public $addToListOnActivation = 'currencies';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Наименование,mandatory');
        $this->FLD('code', 'varchar(3)', 'caption=Код,mandatory,smartCenter');
        $this->FLD('lastUpdate', 'date', 'caption=Последно->Обновяване, input=none');
        $this->FLD('lastRate', 'double(decimals=5)', 'caption=Последно->Курс, input=none,smartCenter');
        
        $this->setDbUnique('code');
    }
    
    
    /**
     * Връща id-то на валутата с посочения трибуквен ISO код
     *
     * @param string $code трибуквен ISO код
     *
     * @return int key(mvc=currency_Currencies)
     */
    public static function getIdByCode($code)
    {
        expect($id = self::fetchField(array("#code = '[#1#]'", $code), 'id'));
        
        return $id;
    }
    

    /**
     * Декорира сумата с показване на валутата след сумата
     * 
     * @param string $amount - сума
     * @param mixed $currency - ид или код на валута
     * @return string $amount - декорираната сума
     */
    public static function decorate($amount, $currency = null)
    {
        if(is_numeric($currency)) {
            $currency = self::getCodeById($currency);
        } 

        if(!strlen($currency) == 3) {
            $currency = acc_Periods::getBaseCurrencyCode();
        }

        $currency = strtoupper($currency);

        switch($currency) {
            case 'BGN': 
                $amount .= '&nbsp;' . tr('лв');
                break;
            case 'USD': 
                $amount = "\$&nbsp;{$amount}";
                break;
            case "EUR":
                $amount = "€&nbsp;{$amount}";
                break;
            default: 
                $amount .= '&nbsp;<span class="cCode">' . $currency . '</span>';
        }

        return $amount;
    }
    
    /**
     * Връща кода на валутата по зададено id
     *
     * @param int $id key(mvc=currency_Currencies)
     *
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
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        if ($groupId = Request::get('groupId', 'int')) {
            $groupRec = $mvc->CurrencyGroups->fetch($groupId);
            
            // Полето 'groups' е keylist и затова имаме LIKE
            $data->query->where("#groups LIKE '%|{$groupId}|%'");
            
            // Сменяме заглавието
            $data->title = 'Валути в група "|*' . $groupRec->name . '"';
        }
    }
    
    
    /**
     *
     *
     * @param currency_Currencies $mvc
     * @param object              $data
     * @param object              $data
     */
    public static function on_AfterPrepareListRecs($mvc, &$res, $data)
    {
        $accConf = core_Packs::getConfig('acc');
        
        $bgnRate = $mvc->fetchField(array("#code = '[#1#]'", $accConf->BASE_CURRENCY_CODE), 'lastRate');
        
        if (!$bgnRate) {
            
            return ;
        }
        
        foreach ((array) $data->recs as $rec) {
            if (!$rec->lastRate) {
                continue;
            }
            
            $rec->lastRate = $bgnRate / $rec->lastRate;
        }
    }
    
    
    /**
     * Преди рендиране на детайлите
     */
    public static function on_BeforeRenderDetails($mvc, $res, &$data)
    {
        return false;
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
        
        $data->toolbar->addBtn('Нова валута', array($mvc, 'Add', 'groupId' => Request::get('groupId', 'int')), null, 'title=Създаване на нова валута');
    }
    
    
    /**
     * Слагаме default за checkbox-овете на полето 'groups', когато редактираме групи на дадена валута
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        if (empty($data->form->rec->id) && ($groupId = Request::get('groupId', 'int'))) {
            $data->form->setDefault('groups', '|' . $groupId . '|');
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $file = 'currency/csv/Currencies.csv';
        $fields = array(
            0 => 'name',
            1 => 'csv_code',
            2 => 'state',);
        
        $cntObj = csv_Lib::importOnce($this, $file, $fields);
        $res = $cntObj->html;
        
        return $res;
    }
    
    
    /**
     * Изпълнява се преди импортирването на данните
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
        if (isset($rec->csv_code) && strlen($rec->csv_code) != 0) {
            
            // Ако данните идват от csv файл
            $rec->code = $rec->csv_code;
            
            if (!$rec->id) {
                $rec->lastUpdate = dt::verbal2mysql();
            }
            
            if ($rec->code == 'EUR') {
                $rec->lastRate = 1;
            }
        }
    }
    
    
    /**
     * Функция за закръгляне на валута, която
     * трябва да се използва във всички бизнес документи за показване на суми
     *
     * @param float     $amount - сума
     * @param string(3) $code   -трибуквен код на валута
     */
    public static function round($amount, $code = null)
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
     *
     * @param int $objectId
     */
    public static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = null;
        
        if ($rec = $self->fetch($objectId)) {
            $result = (object) array(
                'num' => $rec->code,
                'title' => $rec->name,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     *
     * @param int $objectId
     */
    public static function itemInUse($objectId)
    {
        // @todo!
    }
    
    
    /**
     * КРАЙ НА интерфейса @see acc_RegisterIntf
     */
    
    
    /**
     * Връща дефолтната единична цена отговаряща на количеството
     *
     * @param mixed $id       - ид/запис на обекта
     * @param float $quantity - За какво количество
     *
     * @return float|NULL - дефолтната единична цена
     */
    public function getDefaultCost($id, $quantity)
    {
        $today = dt::now();
        $code = static::getCodeById($id);
        $toCode = acc_Periods::getBaseCurrencyCode($today);
        
        return currency_CurrencyRates::getRate($today, $code, $toCode);
    }
}
