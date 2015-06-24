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
    var $title = 'Кеширани цени';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Кеширана цена";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools, price_Wrapper, plg_AutoFilter';
                    
    
    /**
     * Детайла, на модела
     */
    var $details = 'price_ListRules';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, listId, validFrom, productId, price';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'ceo';
    
    
    /**
     * Кой може да го промени?
     */
    var $canWrite = 'ceo';
    
    
     /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'price,ceo';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'price,ceo';

	
    /**
     * Масив с всички ремена, които имат отношение към историята на цените
     */
    static $timeline = array();


    /**
     * Масив с кеш на изчислените стойности
     */
    static $cache = array();


    /**
     * Описание на модела (таблицата)
     */
    function description()
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
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $form = $data->listFilter;
        
        // В хоризонтален вид
        $form->view = 'horizontal';
        
        // Добавяме бутон
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $form->showFields = 'listId, productId';
        
        $form->fields['productId']->mandatory = FALSE;
        
        $form->input('listId, productId', 'silent');
		
        if($form->rec->listId){
        	$data->query->where(array("#listId = '{$form->rec->listId}'"));
        }
        
        if($form->rec->productId){
        	$data->query->where(array("#productId = '{$form->rec->productId}'"));
        }
        
    }
    
    
    /**
     * Връща началото на най-близкия исторически интервал до посоченото време
     */
    static function canonizeTime($datetime)
    {   
        $timeline = &self::$timeline;
        
        // Ако тази стойност вече е извлечена, директно я връщаме
        if(self::$cache[$datetime]) {

            return self::$cache[$datetime];
        }

        // Ако времевата линия липсва, опитваме се да я извадим от кеша
        if(!count($timeline)) {
            self::$timeline = core_Cache::get('price_History', 'timeline');
        }
        
        // Ако времевата линия пак липсва, генерираме я и я записваме в кеша
        if(!is_array($timeline) || !count($timeline)) {
            
            $timeline = array();
            
            // Вземаме всички времена от правилата
            $query = price_ListRules::getQuery();
            $query->show('validFrom,validUntil');
            while($rec = $query->fetch()) {  
                $timeline[$rec->validFrom] = TRUE;
                if($rec->validUntil) {
                    $timeline[$rec->validUntil] = TRUE;
                }
            }

            // Вземаме всички времена от групите на продуктите
            $query = price_GroupOfProducts::getQuery();
            $query->show('validFrom');
            while($rec = $query->fetch()) {
                $timeline[$rec->validFrom] = TRUE;
            }

            // Вземаме всички времена от ценоразписите на клиентите
            $query = price_ListToCustomers::getQuery();
            $query->show('validFrom');
            while($rec = $query->fetch()) {
                $timeline[$rec->validFrom] = TRUE;
            }
  
            // Сортираме обратно масива, защото очакваме да търсим предимно съвременни цени
            krsort($timeline);
            $timeline = array_keys($timeline);
            core_Cache::set('price_History', 'timeline', $timeline, 300000);
        }
       
        // Връщаме първото срещнато време, което е по-малко от аргумента
        foreach($timeline as $t) {
            if($datetime >= $t) {
                self::$cache[$datetime] = $t;

                return $t;
            }
        }
    }


    /**
     * Инвалидира кеша с времевата линия
     */
    static function removeTimeline()
    {
        // Изтриваме кеша
        core_Cache::remove('price_History', 'timeline');

    }


    /**
     * Връща кешираната цена за продукта
     */
    static function getPrice($listId, $datetime, $productId, $packagingId = NULL)
    {
        $validFrom = self::canonizeTime($datetime);
        if(!$validFrom) return;
        
        $cond = "#listId = {$listId} AND #validFrom = '{$validFrom}' AND #productId = {$productId}";

        $price = self::fetchField($cond, 'price');

        return $price;
    }
    
    
    /**
     * Записва кеш за цената на продукта
     */
    static function setPrice($price, $listId, $datetime, $productId)
    {
        $validFrom = self::canonizeTime($datetime);
        
        if(!$validFrom) return;
        
        $rec = new stdClass();
        $rec->listId      = $listId;
        $rec->validFrom   = $validFrom;
        $rec->productId   = $productId;
        $rec->price       = $price;
        self::save($rec);

        return $rec;
    }

    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
    	if(haveRole('admin,debug')){
    		$data->toolbar->addBtn('Изтриване', array($mvc, 'Truncate', 'ret_url' => TRUE), 'ef_icon=img/16/sport_shuttlecock.png, title=Премахване на кешираните записи');
    	}
    }
    
    
    /**
     * Екшън за изтриване на всички кеширани цени
     */
    public function act_Truncate()
    {
    	requireRole('admin,debug');
    	
    	self::truncate();
    	core_Statuses::newStatus(tr('Кешираните цени са изтрити'));
    	
    	followRetUrl();
    }
 }