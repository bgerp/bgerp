<?php


/**
 * Клас 'cat_products_Vat'
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
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
    public $title = 'ДДС групи';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'vatGroup,vatPercent=ДДС (%),validFrom';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'cat_Wrapper, plg_Created';
    
    
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
    public $singleTitle = 'ДДС група';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden,silent,mandatory');
        $this->FLD('vatGroup', 'key(mvc=acc_VatGroups,select=title,allowEmpty)', 'caption=Група,mandatory');
        $this->FLD('validFrom', 'date', 'caption=В сила от');
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
                $form->setError('validFrom', 'Групата не може да се сменя с минала дата');
            } elseif($validFrom == $today){
                $form->setWarning('validFrom', 'Ще се отрази на вече създадените документи с този и следващи вальори|*!');
            }

            if(static::fetchField("#productId = {$rec->productId} AND #validFrom = '{$validFrom}'")){
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
        $row->vatGroup = acc_VatGroups::getTitleById($rec->vatGroup);
        $row->vatPercent = acc_VatGroups::getVerbal($rec->vatGroup, 'vat');
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
        $currentGroup = null;
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
                    $data->rows[$id]->ROW_ATTR['class'] = 'state-draft';
                } elseif (is_null($currentGroup)) {
                    $currentGroup = $rec->validFrom;
                    $data->rows[$id]->ROW_ATTR['class'] = 'state-active';
                } else {
                    $data->rows[$id]->ROW_ATTR['class'] = 'state-closed';
                }
            }
        }
        
        if (static::haveRightFor('add', (object) array('productId' => $data->masterId))) {
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
        $data->listFields = array('vatGroup' => 'Група', 'vatPercent' => 'ДДС|* (%)', 'validFrom' => 'В сила от', 'createdOn' => 'Създаване');
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
            if (cat_Products::fetchField($rec->productId, 'state') != 'active') {
                $requiredRoles = 'no_one';
            } elseif (!cat_Products::haveRightFor('single', $rec->productId)) {
                $requiredRoles = 'no_one';
            } elseif (cat_Products::fetchField($rec->productId, 'createdBy') == core_Users::SYSTEM_USER) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Коя е активната данъчна група към дата
     *
     * @param int       - $productId
     * @param date|NULL - $date
     *
     * @return float|FALSE $value
     */
    public static function getCurrentGroup($productId, $date = null)
    {
        $date = (!empty($date)) ? dt::verbal2mysql($date, false) : dt::today();

        // Кеширане активната данъчна група на артикула в текущия хит
        $query = cat_products_VatGroups::getQuery();
        $query->where("#productId = {$productId}");
        $query->where("#validFrom <= '{$date}'");
        $query->orderBy('#validFrom', 'DESC');
        $query->limit(1);
        
        $value = false;
        if ($rec = $query->fetch()) {
            $value = acc_VatGroups::fetch($rec->vatGroup);
        }
        
        return $value;
    }
    
    
    /**
     * Намира артикулите с посочена ДДС ставка към подадената дата
     *
     * @param double     $percent - търсен процент
     * @param datetime|NULL $date - към коя дата
     * @return array $products    - намерените артикули
     */
    public static function getByVatPercent($percent, $date = null)
    {
        $products = array();
        $date = (!empty($date)) ? dt::verbal2mysql($date, false) : dt::today();
        $gQuery = acc_VatGroups::getQuery();
        $gQuery->where(array("#vat = '[#1#]'", $percent));
        $groups = arr::extractValuesFromArray($gQuery->fetchAll(), 'id');
        if (!countR($groups)) {
            
            return $products;
        }
        
        $query = self::getQuery();
        $query->where("#validFrom <= '{$date}'");
        $query->orderBy('#validFrom', 'DESC');
        $query->show('vatGroup,productId');
        
        while ($rec = $query->fetch()) {
            if (!array_key_exists($rec->productId, $products)) {
                $products[$rec->productId] = $rec;
            }
        }
        
        $products = array_filter($products, function ($obj) use ($groups) {
            if (in_array($obj->vatGroup, $groups)) return true;
        });

        $products = arr::extractValuesFromArray($products, 'productId');
        
        // Ако дефолтното ддс за периода е колкото търсеното, се извличат и всички които нямат записи в модела
        // за конкретна ддс група
        $vatRate = acc_Periods::fetchByDate($date)->vatRate;
        if ($vatRate === $percent) {
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
}
