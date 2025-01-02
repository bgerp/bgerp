<?php


/**
 * Клас 'cat_products_VatGroups'
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class cat_products_VatGroups extends core_Detail
{
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'productId';
    
    
    /**
     * Заглавие
     */
    public $title = 'ДДС групи на артикулите';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId,vatGroup,exceptionId,validFrom';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'cat_Wrapper, plg_Created, plg_Sorting,plg_LastUsedKeys';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'ceo,cat,priceDealer';
    
    
    /**
     * Кой може да качва файлове
     */
    public $canDelete = 'ceo,cat';
    
    
    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'ДДС група на артикул';


    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 20;


    /**
     * Кой може да листва
     */
    public $canList = 'debug';


    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'exceptionId';


    /**
     * Временен кеш
     */
    protected static $tempCache = array();


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty)', 'caption=Артикул,input=hidden,silent,mandatory');
        $this->FLD('vatGroup', 'key(mvc=acc_VatGroups,select=title,allowEmpty)', 'caption=ДДС група,mandatory');
        $this->FLD('exceptionId', 'key(mvc=cond_VatExceptions,select=title,allowEmpty)', 'caption=Изключение');
        $this->FLD('validFrom', 'date', 'caption=В сила от');

        $this->setDbIndex('productId');
        $this->setDbIndex('productId,validFrom');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        if ($form->isSubmitted()) {
            $today = dt::today();
            $validFrom = ($rec->validFrom) ? $rec->validFrom : $today;

            if ($validFrom < $today) {

                // Проверка дали вече артикула учавства в документи след тази дата
                $interfaces = core_Classes::getOptionsByInterface('cat_interface_DocumentVatIntf');
                foreach ($interfaces as $iFace){
                    $Interface = cls::getInterface('cat_interface_DocumentVatIntf', $iFace);
                    if($Interface->isUsedAfterInVatDocument($rec->productId, $validFrom)){
                        $form->setError('validFrom', 'Групата не може да се сменя с минала дата, защото артикула вече участва в документи след нея');
                    }
                }
            } elseif($validFrom == $today){
                $form->setWarning('validFrom', 'Ще се отрази на вече създадените документи с този и следващи вальори|*!');
            }

            $where = "#productId = {$rec->productId} AND #validFrom = '{$validFrom}' AND";
            $where .= isset($rec->exceptionId) ? "#exceptionId = '{$rec->exceptionId}'" : "#exceptionId IS NULL";
            if(static::fetchField($where)){
                $form->setError('validFrom', 'Има вече зададена ДДС група за тази дата');
            }

            if(!$form->gotErrors()){
                if(empty($rec->validFrom)){
                    $rec->validFrom = $validFrom;
                }
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        $row->vatGroup = acc_VatGroups::getTitleById($rec->vatGroup);
        $row->vatGroupPurchase = acc_VatGroups::getTitleById($rec->vatGroupPurchase);

        $vatPercent = acc_VatGroups::getVerbal($rec->vatGroup, 'vat');
        $row->vatGroup = "{$row->vatGroup} <span class='quiet'>[{$vatPercent}]</span>";

        $vatPercentPurchase = acc_VatGroups::getVerbal($rec->vatGroupPurchase, 'vat');
        $row->vatGroupPurchase = "{$row->vatGroupPurchase}  <span class='quiet'>[{$vatPercentPurchase}]</span>";
    }
    
    
    /**
     * Извиква се след подготовка на заявката за детайла
     */
    protected static function on_AfterPrepareDetailQuery(core_Detail $mvc, $data)
    {
        // Историята на ценовите групи на продукта - в обратно хронологичен ред.
        $data->query->orderBy('validFrom,id', 'DESC');
    }
    
    
    /**
     * Подготовка на файловете
     */
    public function prepareVatGroups($data)
    {
        $today = dt::today();
        $currentGroup = array();
        $data->recs = $data->rows = array();
        
        $query = $this->getQuery();
        $query->where("#productId = {$data->masterId}");
        $query->orderBy('#validFrom', 'DESC');
        while ($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $row = $this->recToVerbal($rec);
            
            $row->createdOn .= ' ' . tr('от') . ' ' . $row->createdBy;
            $data->rows[$rec->id] = $row;
        }

        if (countR($data->rows)) {
            foreach ($data->rows as $id => &$row) {
                $rec = $data->recs[$id];

                if ($rec->validFrom > $today) {
                    $row->ROW_ATTR['class'] = 'state-draft';
                } elseif (is_null($currentGroup[$rec->exceptionId])) {
                    $currentGroup[$rec->exceptionId] = $rec->validFrom;
                    $row->ROW_ATTR['class'] = 'state-active';
                } else {
                    $row->ROW_ATTR['class'] = 'state-closed';
                }

                // Показване докога е валидно изключението
                if(isset($rec->exceptionId)){
                    $exceptionValidTo = cond_VatExceptions::fetchField($rec->exceptionId, 'validTo');
                    if(!empty($exceptionValidTo) && $exceptionValidTo <= $today){
                        $exceptionValidToVerbal = dt::mysql2verbal($exceptionValidTo, 'd.m.Y');
                        $row->validFrom = tr("|*{$row->validFrom} ( |до|* {$exceptionValidToVerbal} )");
                        $row->ROW_ATTR['class'] = 'state-closed';
                    }
                }
            }
        }
        
        if(static::haveRightFor('add', (object) array('productId' => $data->masterId))) {
            $data->addUrl = array($this, 'add', 'productId' => $data->masterId, 'ret_url' => true);
        }
    }
    
    
    /**
     * Рендиране на файловете
     */
    public function renderVatGroups($data)
    {
        $wrapTpl = getTplFromFile('cat/tpl/ProductDetail.shtml');
        $table = cls::get('core_TableView', array('mvc' => $this));
        $data->listFields = array('vatGroup' => 'ДДС група', 'exceptionId' => 'Изключение', 'validFrom' => 'В сила от', 'createdOn' => 'Създаване');
        $tpl = $table->get($data->rows, $data->listFields);
        
        $title = 'ДДС';
        if ($data->addUrl && !Mode::isReadOnly()) {
            $title .= ht::createLink('<img src=' . sbf('img/16/add.png') . " style='vertical-align: middle; margin-left:5px;'>", $data->addUrl, false, 'title=Избор на ДДС група');
        }
        
        $wrapTpl->append($title, 'TITLE');
        $wrapTpl->append($tpl, 'CONTENT');
        
        return $wrapTpl;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'edit' || $action == 'delete') && isset($rec)) {
            if ($rec->validFrom <= dt::today()) {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'add' && isset($rec->productId)) {
            $productRec = cat_Products::fetch($rec->productId, 'state,createdBy');
            if ($productRec->state != 'active') {
                $requiredRoles = 'no_one';
            } elseif (!cat_Products::haveRightFor('single', $rec->productId)) {
                $requiredRoles = 'no_one';
            } elseif ($productRec->createdBy == core_Users::SYSTEM_USER) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Коя е активната данъчна група към дата
     *
     * @param int       $productId   - ид на артикул
     * @param date|NULL $date        - към дата
     * @param int|null  $exceptionId - ДДС изключение
     * @return float|FALSE $value
     */
    public static function getCurrentGroup($productId, $date = null, $exceptionId = null)
    {
        $date = (!empty($date)) ? dt::verbal2mysql($date, false) : dt::today();

        // Кои са валидните ДДС изключения към датата
        if(!array_key_exists($date, static::$tempCache)){
            $exQuery = cond_VatExceptions::getQuery();
            $exQuery->where("#validTo IS NULL OR #validTo >= '{$date}'");
            $exQuery->show('id');
            $exceptionIds = arr::extractValuesFromArray($exQuery->fetchAll(), 'id');
            static::$tempCache[$date] = countR($exceptionIds) ? implode(',', $exceptionIds) : '-1';
        }

        // Извличат се активните записи (ако има ддс изключение - само за него, ако няма това без изключения)
        $query = cat_products_VatGroups::getQuery();
        $query->XPR('orderExceptionId', 'int', "COALESCE(#exceptionId, '')");
        $query->where("#productId = {$productId} AND #validFrom <= '{$date}'");
        $query->where("#exceptionId IN (" . static::$tempCache[$date] . ") OR #exceptionId IS NULL");
        $query->orderBy('orderExceptionId,#validFrom', 'DESC');
        $query->limit(1);

        $value = false;
        if ($rec = $query->fetch()) {
            $value = acc_VatGroups::fetch($rec->vatGroup);
        }

        return $value;
    }
    
    
    /**
     * Връща от подадените артикули тези с посочената ДДС ставка към датата
     *
     * @param double        $percent     - търсен процент
     * @param datetime|NULL $date        - към коя дата
     * @param array|NULL    $productIds  - сред кои артикули да се търси, null за всички
     * @param int|null      $exceptionId - ид на ДДС изключение
     * @return array        $products    - намерените артикули
     */
    public static function getByVatPercent($percent, $date = null, $productIds = null, $exceptionId = null)
    {
        $products = array();
        $date = (!empty($date)) ? dt::verbal2mysql($date, false) : dt::today();
        $gQuery = acc_VatGroups::getQuery();
        $gQuery->where(array("#vat = '[#1#]'", $percent));
        $groups = arr::extractValuesFromArray($gQuery->fetchAll(), 'id');
        if (!countR($groups)) return $products;

        if(!isset($productIds)){
            $vQuery = cat_products_VatGroups::getQuery();
            $vQuery->show('productId');
            $productIds = arr::extractValuesFromArray($vQuery->fetchAll(), 'productId');
        }

        foreach ($productIds as $pId){
            if($currentGroup = static::getCurrentGroup($pId, $date, $exceptionId)){
                if($currentGroup->vat == $percent){
                    $products[$pId] = $pId;
                }
            }
        }

        // Ако дефолтното ддс за периода е колкото търсеното, се извличат и
        // всички които нямат записи в модела за конкретна ддс група
        $vatRate = acc_Periods::fetchByDate($date)->vatRate;
        if ($vatRate == $percent) {
            $pQuery = cat_Products::getQuery();
            $pQuery->show('id');
            $pQuery->notIn('id', $products);
            $productsDefArr = arr::extractValuesFromArray($pQuery->fetchAll(), 'id');
            $products = $productsDefArr + $products;
        }

        return $products;
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        price_Cache::invalidateProduct($rec->productId);
    }


    /**
     * Подготовка на филтър формата
     *
     * @param core_Mvc $mvc
     * @param StdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->setField('productId', 'input');
        $data->listFilter->showFields = 'productId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();

        if($filter = $data->listFilter->rec){
            if(isset($filter->productId)){
                $data->query->where("#productId = {$filter->productId}");
            }
        }
    }
}
