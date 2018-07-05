<?php



/**
 * История с кеширани цени
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     История с кеширани цени
 */
class price_History extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Кеширани цени';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Кеширана цена';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, price_Wrapper';
                    
    
    /**
     * Детайла, на модела
     */
    public $details = 'price_ListRules';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, listId, validFrom, productId, price';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Кой може да го прочете?
     */
    public $canRead = 'ceo';
    
    
    /**
     * Кой може да го промени?
     */
    public $canWrite = 'ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin,debug';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'priceMaster,ceo';

    
    /**
     * Масив с всички ремена, които имат отношение към историята на цените
     */
    protected static $timeline = array();


    /**
     * Масив с кеш на изчислените стойности
     */
    protected static $cache = array();


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('listId', 'key(mvc=price_Lists,select=title)', 'caption=Ценоразпис, autoFilter');
        $this->FLD('validFrom', 'datetime', 'caption=В сила от');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Продукт,mandatory,silent, autoFilter');
        $this->FLD('price', 'double(decimals=5)', 'caption=Цена');

        $this->setDbUnique('listId,validFrom,productId');
    }

    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $form = $data->listFilter;
        
        // В хоризонтален вид
        $form->view = 'horizontal';
        
        // Добавяме бутон
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета
        // на модела ще се появят
        $form->showFields = 'listId, productId';
        
        $form->fields['productId']->mandatory = false;
        
        $form->input('listId, productId', 'silent');
        
        if ($form->rec->listId) {
            $data->query->where(array("#listId = '{$form->rec->listId}'"));
        }
        
        if ($form->rec->productId) {
            $data->query->where(array("#productId = '{$form->rec->productId}'"));
        }
    }
    
    
    /**
     * Връща началото на най-близкия исторически интервал до посоченото време
     */
    public static function canonizeTime($datetime)
    {
        $timeline = &self::$timeline;
        
        // Ако тази стойност вече е извлечена, директно я връщаме
        if (self::$cache[$datetime]) {
            return self::$cache[$datetime];
        }

        // Ако времевата линия липсва, опитваме се да я извадим от кеша
        if (!count($timeline)) {
            self::$timeline = core_Cache::get('price_History', 'timeline');
        }
        
        // Ако времевата линия пак липсва, генерираме я и я записваме в кеша
        if (!is_array($timeline) || !count($timeline)) {
            $timeline = array();
            
            // Вземаме всички времена от правилата
            $query = price_ListRules::getQuery();
            $query->show('validFrom,validUntil');
            while ($rec = $query->fetch()) {
                $timeline[$rec->validFrom] = true;
                if ($rec->validUntil) {
                    $timeline[$rec->validUntil] = true;
                }
            }

            // Вземаме всички времена от ценоразписите на клиентите
            $query = price_ListToCustomers::getQuery();
            $query->show('validFrom');
            while ($rec = $query->fetch()) {
                $timeline[$rec->validFrom] = true;
            }
  
            // Сортираме обратно масива, защото очакваме да търсим предимно съвременни цени
            krsort($timeline);
            $timeline = array_keys($timeline);
            core_Cache::set('price_History', 'timeline', $timeline, 300000);
        }
       
        // Връщаме първото срещнато време, което е по-малко от аргумента
        foreach ($timeline as $t) {
            if ($datetime >= $t) {
                self::$cache[$datetime] = $t;

                return $t;
            }
        }
    }


    /**
     * Инвалидира кеша с времевата линия
     */
    public static function removeTimeline()
    {
        // Изтриваме кеша
        core_Cache::remove('price_History', 'timeline');
    }


    /**
     * Връща кешираната цена за продукта
     */
    public static function getPrice($listId, $datetime, $productId, $packagingId = null)
    {
        $validFrom = self::canonizeTime($datetime);
        if (!$validFrom) {
            return;
        }
        
        $cond = "#listId = {$listId} AND #validFrom = '{$validFrom}' AND #productId = {$productId}";

        $price = self::fetchField($cond, 'price');

        return $price;
    }
    
    
    /**
     * Записва кеш за цената на продукта
     */
    public static function setPrice($price, $listId, $datetime, $productId)
    {
        $validFrom = self::canonizeTime($datetime);
        
        if (!$validFrom) {
            return;
        }
        
        $rec = new stdClass();
        $rec->listId = $listId;
        $rec->validFrom = $validFrom;
        $rec->productId = $productId;
        $rec->price = $price;
        self::save($rec, null, 'REPLACE');

        return $rec;
    }

    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if (haveRole('admin,debug')) {
            $data->toolbar->addBtn('Изтриване', array($mvc, 'Truncate', 'ret_url' => true), 'ef_icon=img/16/sport_shuttlecock.png, title=Премахване на кешираните записи');
        }
    }
    
    
    /**
     * Екшън за изтриване на всички кеширани цени
     */
    public function act_Truncate()
    {
        requireRole('admin,debug');
        
        self::truncate();
        core_Statuses::newStatus('Кешираните цени са изтрити');
        
        followRetUrl();
    }
}
