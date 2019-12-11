<?php


/**
 * Мениджър за "Средства за плащане"
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.11
 */
class cond_Payments extends core_Manager
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'cond_PaymentAccRegIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Безналични средства за плащане';
    
    
    /**
     * Заглавие на единичния обект
     */
    public $singleTitle = 'Безналично средство за плащане';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, plg_State2, cond_Wrapper, acc_plg_Registry';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, title, currencyCode, code, change, state, synonym, createdOn,createdBy';
    
    
    /**
     * Кой може да променя?
     */
    public $canWrite = 'ceo,admin';
    
    
    /**
     * Кой може да променя състоянието
     */
    public $canChangestate = 'ceo,admin';
    
    
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
     * Дали при обновяване от импорт на същестуващ запис да се запази предишното състояние или не
     * 
     * @see plg_State2
     */
    public $updateExistingStateOnImport = false;
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('title', 'varchar(255)', 'caption=Наименование,mandatory');
        $this->FLD('code', 'int(Min=0)', 'caption=Код,mandatory,tdClass=centerCol');
        $this->FLD('change', 'enum(yes=Да,no=Не)', 'caption=Ресто?,value=no,tdClass=centerCol');
        $this->FLD('currencyCode', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'caption=Валута,smartCenter');
        $this->FLD('synonym', 'varchar(120)', 'caption=Имена във ФУ');
        
        $this->setDbUnique('title');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        if($form->isSubmitted()){
            if(!empty($rec->synonym)){
                $arr = explode('|', $rec->synonym);
                array_walk($arr, function(&$a){$a = plg_Search::normalizeText($a);});
                $rec->synonym = (countR($arr) == 1) ? "|" . $arr[0] . "|" : implode('|', $arr);
            }
            
            $titleNorm = plg_Search::normalizeText($rec->title);
            if(strpos($rec->synonym, $titleNorm) === false){
                $rec->synonym .= "|{$titleNorm}|";
            }
        }
    }
    
    
    /**
     * След вербализиране на данните
     *
     * @param stdCLass $row
     * @param stdCLass $rec
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if(empty($rec->currencyCode)){
            $row->currencyCode = ht::createHint(acc_Periods::getBaseCurrencyCode(), 'Текущата валута за периода', 'notice', false);
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $file = 'cond/csv/Pospayments.csv';
        $fields = array(0 => 'title', 1 => 'change', 2 => 'code', 3 => 'currencyCode', '4' => 'synonym');
        
        $cntObj = csv_Lib::importOnce($this, $file, $fields);
        $res = $cntObj->html;
        
        return $res;
    }
    
    
    
    /**
     *  Метод отговарящ дали даден платежен връща ресто
     *
     *  @param int $id - ид на метода
     *
     *  @return bool $res - дали връща или не връща ресто
     */
    public static function returnsChange($id)
    {
        expect($rec = static::fetch($id), 'Няма такъв платежен метод');
        ($rec->change == 'yes') ? $res = true : $res = false;
        
        return $res;
    }
    
    
    /**
     *  Равностойноста на платената сума, в основната валута към дата
     *
     *  @param int $id - ид на метода
     *  @param double  $amount
     *  @param string $toCurrencyCode
     *  @return double
     */
    public static function toBaseCurrency($id, $amount, $date = null, $toCurrencyCode = null)
    {
        $fromCurrencyCode = self::fetchField($id, currencyCode);
        $fromCurrencyCode = !empty($fromCurrencyCode) ? $fromCurrencyCode : acc_Periods::getBaseCurrencyCode($date);
        
        return currency_CurrencyRates::convertAmount($amount, $date, $fromCurrencyCode, $toCurrencyCode);
    }
    
    
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
                'num' => $rec->id . ' pm',
                'title' => $rec->title,
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
}
