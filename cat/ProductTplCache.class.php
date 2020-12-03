<?php


/**
 * Кеш на изгледа на частните артикули
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_ProductTplCache extends core_Master
{
    /**
     * Необходими плъгини
     */
    public $loadList = 'plg_RowTools2, cat_Wrapper, plg_Select';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Кеш на изгледа на артикулите';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Права за запис
     */
    public $canDelete = 'ceo, debug, cat';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, debug, cat';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, debug, cat';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, productId, lang, time, type, documentType';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'productId';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'cat/tpl/SingleLayoutTplCache.shtml';
    

    /**
     * На участъци от по колко записа да се бекъпва?
     */
    public $backupMaxRows = 10000;
    
    
    /**
     * Кои полета да определят рзличността при backup
     */
    public $backupDiffFields = 'time';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=none,caption=Артикул');
        $this->FLD('type', 'enum(title=Заглавие,description=Описание)', 'input=none,caption=Тип');
        $this->FLD('documentType', 'enum(public=Външни документи,internal=Вътрешни документи,invoice=Фактура,job=Задание)', 'input=none,caption=Документ тип');
        $this->FLD('lang', 'varchar(2)', 'input=none,caption=Език');
        
        $this->FLD('cache', 'blob(1000000, serialize, compress)', 'input=none,caption=Html,column=none');
        $this->FLD('time', 'datetime', 'input=none,caption=Дата');
        
        $this->setDbIndex('productId');
        $this->setDbIndex('time');
        $this->setDbIndex('productId, type, lang, documentType, time');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($fields['-single'])) {
            if ($rec->type == 'description') {
                $Driver = cls::get('cat_Products')->getDriver($rec->productId);
                $row->cache = $Driver->renderProductDescription($rec->cache);
                
                $componentTpl = cat_Products::renderComponents($rec->cache->components);
                $row->cache->append($componentTpl, 'COMPONENTS');
            } else {
                if ($rec->cache instanceof core_ET) {
                    $row->cache = cls::get('type_Varchar')->toVerbal($rec->cache);
                } else {
                    if (is_array($rec->cache)) {
                        $row->cache->append('<br>' . $rec->cache['subTitle']);
                        $row->cache = cls::get('type_Html')->toVerbal($row->cache);
                    } else {
                        $row->cache = cls::get('type_Varchar')->toVerbal($rec->cache);
                    }
                }
            }
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->FLD('docId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax)', 'input,caption=Артикул,removeAndRefreshForm');
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'docId';
        $data->listFilter->input(null, 'silent');
        
        if (isset($data->listFilter->rec->docId)) {
            $data->query->where("#productId = '{$data->listFilter->rec->docId}'");
        }
        
        $data->query->orderBy('id', "DESC");
    }
    
    
    /**
     * Връща кешираните данни на артикула за дадено време ако има
     *
     * @param int           $productId - ид на артикул
     * @param datetime|NULL $time      - време
     *
     * @return mixed
     */
    public static function getCache($productId, $time, $type, $documentType, $lang)
    {
        // Кога артикула е бил последно модифициран
        $productModifiedOn = cat_Products::fetchField($productId, 'modifiedOn');
        
        if(!empty($time)){
            $query = self::getQuery();
            $query->where("#productId = {$productId} AND #type = '{$type}' AND #lang = '{$lang}' AND #documentType = '{$documentType}' AND #time <= '{$time}'");
            $query->orderBy('time', 'DESC');
            $query->limit(1);
            $rec = $query->fetch();
            
            if (!empty($rec)) {
                $res = array("{$productModifiedOn}" => null, "{$rec->time}" => $rec->cache);
                krsort($res);
                foreach ($res as $cTime => $cache) {
                    if ($cTime <= $time) {
                        
                        return $cache;
                    }
                }
            }
        }
    }
    
    
    /**
     * Кешира заглавието на артикула
     *
     * @param int                   $productId
     * @param datetime|NULL         $time
     * @param enum(internal,public) $documentType
     *
     * @return string - заглавието на артикула
     */
    public static function cacheTitle($rec, $time, $documentType, $lang)
    {
        $rec = cat_Products::fetchRec($rec);
        
        $cacheRec = new stdClass();
        
        // Ако няма кеш досега записваме го с датата за която проверяваме за да се върне винаги
        if(empty($time)){
            $cacheRec->time = $time;
        } elseif (!self::fetch(("#productId = {$rec->id} AND #type = 'title' AND #documentType = '{$documentType}' AND #time <= '{$time}'"))) {
            $cacheRec->time = $time;
        } else {
            
            // Ако записваме нов кеш той е с датата на модифициране на артикула
            $cacheRec->time = $rec->modifiedOn;
        }
        
        $cacheRec->productId = $rec->id;
        $cacheRec->type = 'title';
        $cacheRec->documentType = $documentType;
        
        Mode::push('text', 'plain');
        $cacheRec->cache = cat_Products::getVerbal($rec->id, 'name');
        
        if ($Driver = cat_Products::getDriver($rec->id)) {
            $additionalNotes = $Driver->getAdditionalNotesToDocument($rec->id, $documentType);
            if (!empty($additionalNotes)) {
                $cacheRec->cache = array('title' => $cacheRec->cache, 'subTitle' => $additionalNotes);
            }
        }
        
        Mode::pop('text');
        $cacheRec->lang = $lang;
        
        if (isset($time)) {
            self::save($cacheRec);
        }
        
        return $cacheRec->cache;
    }
    
    
    /**
     * Кешира описанието на артикула
     *
     * @param int                   $productId
     * @param datetime|NULL         $time
     * @param enum(public,internal) $documentType
     *
     * @return core_ET
     */
    public static function cacheDescription($productId, $time, $documentType, $lang, $componentQuantity)
    {
        $pRec = cat_Products::fetchRec($productId);
        
        $data = cat_Products::prepareDescription($pRec->id, $documentType);
        
        $data->components = array();
        cat_Products::prepareComponents($pRec->id, $data->components, $documentType, $componentQuantity);
        
        $cacheRec = new stdClass();
        
        // Ако няма кеш досега записваме го с датата за която проверяваме за да се върне винаги
        if(empty($time)){
            $cacheRec->time = $time;
        } elseif (!self::fetch(("#productId = {$pRec->id} AND #type = 'description' AND #documentType = '{$documentType}' AND #time <= '{$time}'"))) {
            $cacheRec->time = $time;
        } else {
            
            // Ако записваме нов кеш той е с датата на модифициране на артикула
            $cacheRec->time = $pRec->modifiedOn;
        }
        
        $cacheRec->productId = $pRec->id;
        $cacheRec->type = 'description';
        $cacheRec->documentType = $documentType;
        $cacheRec->cache = $data;
        $cacheRec->lang = $lang;
        
        if (isset($time)) {
            self::save($cacheRec);
        }
        
        return $cacheRec->cache;
    }
}
