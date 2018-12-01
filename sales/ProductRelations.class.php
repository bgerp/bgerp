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
    public $listFields = 'docId,recId,fee,deliveryTime,explain';
    
    
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
        $this->FLD('nearIss', 'keylist(mvc=cat_Products,select=name)', 'mandatory,caption=Ид на документа');
        $this->setDbIndex('productId');
    }
  
    
 
    public function act_PDist()
    {
        return self::calcNearProducts();
    }


    /**
     * Генерира разстоянията между продуктите, на базата на последните записи в детайлите за продажби
     */
    private static function calcNearProducts()
    {
        requireRole('admin');
        
        $pQuery = cat_Products::getQuery();
        
        $pQuery->limit(1000000);

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
        
        $maxCnt = 0.2 * count($relations);
        
        $res = array();
        foreach($relations as $rel => $weight) {
            list($aId, $bId) = explode('|', $rel);
            $res[$aId][$bId] = $weight;
            $res[$bId][$aId] = $weight;
            $i++;
            if($i > $maxCnt) break;
        }

        bp($res);
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
}
