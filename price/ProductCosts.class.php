<?php


/**
 * Кеширани последни цени за артикулите
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class price_ProductCosts extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Кеширани последни цени на артикулите';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Кеширани последни цени на артикулите';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, price_Wrapper, plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, classId, price, valior, quantity, sourceId=Документ, updatedOn';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да редактира?
     */
    public $canEdit = 'debug';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'debug';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,onlyPublic)', 'caption=Артикул,mandatory');
        $this->FLD('classId', 'class(interface=price_CostPolicyIntf,select=title,allowEmpty)', 'caption=Алгоритъм,mandatory');
        $this->FLD('price', 'double', 'caption=Ед. цена,mandatory,mandatory');
        $this->FLD('quantity', 'double', 'caption=К-во,input=none,mandatory');
        $this->FLD('sourceClassId', 'class(allowEmpty)', 'caption=Документ->Клас');
        $this->FLD('sourceId', 'varchar', 'caption=Документ->Ид');
        $this->FLD('valior', 'date', 'caption=Вальор');
        $this->FLD('updatedOn', 'datetime(format=smartTime)', 'caption=Обновено на');
        
        $this->setDbUnique('productId,classId');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;

        $form->setField('price', "unit=" . acc_Periods::getBaseCurrencyCode());
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        $row->price = price_Lists::roundPrice(price_ListRules::PRICE_LIST_COST, $rec->price, true);
        $row->ROW_ATTR = array('class' => 'state-active');
        
        if (!empty($rec->sourceId)) {
            $Source = cls::get($rec->sourceClassId);
            if (cls::haveInterface('doc_DocumentIntf', $Source)) {
                $row->sourceId = $Source->getLink($rec->sourceId, 0);
            } elseif ($Source instanceof core_Master) {
                $row->sourceId = $Source->getHyperlink($rec->sourceId, true);
            } else {
                $row->sourceId = $Source->getRecTitle($rec->sourceId);
            }
            
            if($Source->getField('state', false)){
                $sState = $Source->fetchField($rec->sourceId, 'state');
                if($sState == 'rejected'){
                    $row->sourceId = "<span class= 'state-{$sState} document-handler'>{$row->sourceId}</span>";
                }
            }
        }
        
        try{
            $row->classId = cls::get($rec->classId)->getName(true);
            $row->classId = trim($row->classId, ' "');
        } catch(core_exception_Expect $e){
            $row->classId = "<span class='red'>" . tr('Проблем при показването') . "</span>";
        }
    }
    
    
    /**
     * Рекалкулира себестойностите
     */
    public function act_Recalc()
    {
        expect(haveRole('debug'));


        $form = cls::get('core_Form');
        $form->title = 'Преизчисляване на кеширани себестойности';
        $form->FLD('classes', 'classes(interface=price_CostPolicyIntf,select=title,allowEmpty)', 'caption=Вид,mandatory');
        $form->FLD('datetime', 'datetime', 'caption=Време,mandatory');

        $form->setDefault('datetime', '2026-01-01 00:00:00');
        $form->input();
        if($form->isSubmitted()){
            $rec = $form->rec;
            self::saveCalcedCosts($rec->datetime, $rec->classes);

            followRetUrl();
        }


        $form->toolbar->addSbBtn('Промяна', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');

        $res = $this->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($res, $form);

        return $res;







        $datetime = dt::addSecs(-1 * 60);




        self::saveCalcedCosts($datetime);
        
        return followRetUrl(null, '|Преизчислени са данните за последния час');
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (haveRole('debug')) {
            $data->toolbar->addBtn('Преизчисли', array($mvc, 'Recalc', 'ret_url' => true), null, 'ef_icon = img/16/arrow_refresh.png,title=Преизчисляване на себестойностите,target=_blank');
        }
    }
    
    
    /**
     * Обновяване на себестойностите по разписание
     */
    public static function saveCalcedCosts($datetime, $classIds = array())
    {
        $self = cls::get(get_called_class());
        $PolicyOptions = core_Classes::getOptionsByInterface('price_CostPolicyIntf');

        // Изчисляване на всяка от засегнатите политики, себестойностите на засегнатите пера
        $totalProducts = $update = array();

        // Ако има филтър по класове - само те
        $classIds = arr::make($classIds, true);
        if(countR($classIds)){
            $PolicyOptions = array_intersect_key($PolicyOptions, $classIds);
        }

        foreach ($PolicyOptions as $policyId) {
            if (cls::load($policyId, true)) {
                core_Debug::startTimer("CALC {$policyId}");

                // Ако няма отделен крон процес
                $Interface = cls::getInterface('price_CostPolicyIntf', $policyId);
                if($Interface->hasSeparateCalcProcess()) continue;
                
                // Кои са засегнатите артикули, касаещи политиката
                core_Debug::startTimer("CALC GET AFFECTED {$policyId}");
                $affectedProducts = $Interface->getAffectedProducts($datetime);
                core_Debug::stopTimer("CALC GET AFFECTED {$policyId}");
                $totalProducts += $affectedProducts;

                // Ако има такива, ще се прави опит за изчисляване на себестойносттите
                $count = countR($affectedProducts);
                if($count){
                    core_App::setTimeLimit($count * 0.5, false,60);
                    core_Debug::startTimer("CALC COSTS {$policyId}");
                    $calced = $Interface->calcCosts($affectedProducts);
                    core_Debug::stopTimer("CALC COSTS {$policyId}");
                    $update = array_merge($update, $calced);
                }

                core_Debug::stopTimer("CALC {$policyId}");
            }
        }

        if(!countR($update)){
           
            return;
        }
        
        $now = dt::now();
        array_walk($update, function (&$a) use ($now) {
            $a->updatedOn = $now;
        });
        
        // Синхронизиране на новите записи със старите записи на засегнатите пера
        $exQuery = self::getQuery();
        $exQuery->in('productId', $totalProducts);
        $exRecs = $exQuery->fetchAll();

        $res = arr::syncArrays($update, $exRecs, 'productId,classId', 'price,quantity,sourceClassId,sourceId,valior');

        core_Debug::startTimer("SAVE");

        // Добавяне на нови записи
        if (countR($res['insert'])) {
            $self->saveArray($res['insert']);
        }
        
        // Обновяване на съществуващите записи
        if (countR($res['update'])) {
            $self->saveArray($res['update'], 'id,price,quantity,sourceClassId,sourceId,updatedOn,valior');
        }

        core_Debug::stopTimer("SAVE");
    }
    
    
    /**
     * Намира себестойността на артикула по вида
     *
     * @param int   $productId - ид на артикула
     * @param mixed $source    - източник
     *
     * @return float $price     - намерената себестойност
     */
    public static function getPrice($productId, $source)
    {
        expect($productId);
        
        $price = null;
        if(cls::load($source, true)){
            $Source = cls::get($source);
            $price = static::fetchField("#productId = {$productId} AND #classId = '{$Source->getClassId()}'", 'price');
        }
        
        return $price;
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->setOptions('classId', price_Updates::getCostPoliciesOptions());
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'productId,classId';
        $data->listFilter->input();
        
        if ($filterRec = $data->listFilter->rec) {
            if (isset($filterRec->productId)) {
                $data->query->where("#productId = {$filterRec->productId}");
            }
            if (isset($filterRec->classId)) {
                $data->query->where("#classId = {$filterRec->classId}");
            }
        }
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');

        $data->listFields['price'] .= "|* <small>(" . acc_Periods::getBaseCurrencyCode() . ")</small>";
    }
}
