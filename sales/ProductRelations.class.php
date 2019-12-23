<?php
/**
 * Клас 'sales_ProductRelations'
 *
 * Отношения между продукти
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sales_ProductRelations extends core_Manager
{
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета, които се виждат
     */
    // public $listFields = '';
    
    
    /**
     * Наименование
     */
    public $title = 'Отнощения между продукти';
    
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'mandatory,caption=Продуцт');
        $this->FLD('data', 'blob(serialize)', 'mandatory,caption=Релации');
        
        $this->setDbUnique('productId');
    }
  
    
    /**
     * Записва изчислените данни за релациите
     */
    public static function saveRels($rels)
    {
        foreach($rels as $productId => $data) {
            $rec = new stdClass();
            $rec->productId = $productId;
            $rec->data = $data;

            self::save($rec, null, 'replace');
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(is_array($rec->data)) {
            $row->data = '';
            foreach($rec->data as $productId => $weight) {
                $row->data .= "<li>" . cat_Products::getTitleById($productId) . ' - ' . $weight . "</li>";
            }
            $row->data = "<ul>" . $row->data . "</ul>";
        }
    }


    /**
     * Генерира разстоянията между продуктите, на базата на последните записи в детайлите за продажби
     */
    private static function calcNearProducts()
    {
        $pQuery = cat_Products::getQuery();
        
        $pQuery->limit(1000000);
        $pArr = array();
        $pSdRec = $ppSdRec = $pppSdRec = null;
        while($pRec = $pQuery->fetch("#state = 'active' AND #isPublic = 'yes' AND #code IS NOT NULL")) {
            $pArr[$pRec->id] = array();
        }

        $sdQuery = sales_SalesDetails::getQuery();
        $sdQuery->orderBy("#saleId,#id", 'DESC');
        $sdQuery->EXT('state', 'sales_Sales', 'externalName=state,externalKey=saleId');
        $relations = array();
        while($sdRec = $sdQuery->fetch("#state = 'active' OR #state = 'closed'")) {
            if(isset($pArr[$sdRec->productId])) {  
                if($pSdRec && isset($pArr[$pSdRec->productId]) && $sdRec->saleId == $pSdRec->saleId) {
                    self::addPoints($pSdRec->productId, $sdRec->productId, 3, $relations);
                }
                if($ppSdRec && isset($pArr[$ppSdRec->productId]) && $sdRec->saleId == $ppSdRec->saleId) {
                    self::addPoints($ppSdRec->productId, $pSdRec->productId, 2, $relations);
                }
                if($pppSdRec && isset($pArr[$pppSdRec->productId]) && $sdRec->saleId == $pppSdRec->saleId) {
                    self::addPoints($pppSdRec->productId, $ppSdRec->productId, 1, $relations);
                }
            }
            $pppSdRec = $ppSdRec;
            $ppSdRec  = $pSdRec;
            $pSdRec    = $sdRec;
        }
        
        arsort($relations);
                
        $res = array();
        foreach($relations as $rel => $weight) {
            list($aId, $bId) = explode('|', $rel);
            $res[$aId][$bId] = $weight;
            $res[$bId][$aId] = $weight;
        }

        foreach($res as $prodId => $wArr) {
            $res[$prodId] = array_slice($res[$prodId], 0, 10, true);
        }

        return $res;
    }


    /**
     * Помощна функция за увеличаване на теглото на свързаност между два продукта
     */
    private static function addPoints($aId, $bId, $points, &$res)
    {
        if($aId == $bId) return;

        $a = min($aId, $bId);
        $b = max($aId, $bId);

        $res["{$a}|{$b}"] += $points;
    }


    /**
     * Метод за периодично изчисляване на разстоянията между продуктите
     */
    public function cron_CalcNearProducts()
    {
        core_App::setTimeLimit(360);

        $rels = self::calcNearProducts();
        
        self::saveRels($rels);
        
        if(core_Packs::isInstalled('eshop')) {
            eshop_Products::saveNearProducts();
        }
    }
}
