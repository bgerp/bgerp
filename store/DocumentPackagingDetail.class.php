<?php


/**
 * Клас 'store_DocumentPackagingDetail'
 *
 * Детайли за амбалажи към складови документи
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_DocumentPackagingDetail extends store_InternalDocumentDetail
{
    /**
     * Заглавие
     */
    public $title = 'Амбалажи към складови документи';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'documentId';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Амбалаж';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_Wrapper, plg_SaveAndNew,plg_AlignDecimals2, LastPricePolicy=sales_SalesLastPricePolicy, cat_plg_ShowCodes, plg_RowNumbering';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId=Артикул, packagingId, packQuantity,type,productType,packPrice,amount';
    
    
    /**
     * Кой има право да добавя?
     *
     * @var string|array
     */
    public $canAdd = 'ceo,store,sales,purchase';
    
    
    /**
     * Кой има право да редактира?
     *
     * @var string|array
     */
    public $canEdit = 'ceo,store,sales,purchase';
    
    
    /**
     * Кой има право да листва?
     *
     * @var string|array
     */
    public $canList = 'ceo,store,sales,purchase';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('documentClassId', 'class', 'column=none,notNull,silent,input=hidden,mandatory');
        $this->FLD('documentId', 'int', 'column=none,notNull,silent,input=hidden,mandatory');
        $this->FLD('productType', 'enum(ours=Наш артикул,other=Чужд артикул)', 'silent,caption=Вид,mandatory,notNull,default=ours,removeAndRefreshForm=productId|packagingId|quantity|quantityInPack,smartCenter');
        parent::setFields($this);
        $this->setField('amount', 'smartCenter');
        $this->FLD('type', 'enum(in=Приемане,out=Предаване)', 'column=none,notNull,silent,mandatory,caption=Действие,after=productId,input=hidden');
        $this->setDbUnique('documentClassId,documentId,productId,packagingId,type,productType');
    }


    /**
     * Извиква се преди подготовката на колоните
     */
    protected static function on_BeforePrepareListFields($mvc, &$res, $data)
    {
        $data->showCodeColumn = true;
    }


    /**
     * Подготвя заявката за данните на детайла
     */
    public function prepareDetailQuery_($data)
    {
        // Създаваме заявката
        $data->query = $this->getQuery();
        $data->query->where("#{$data->masterKey} = {$data->masterId} AND #documentClassId = {$data->masterMvc->getClassId()}");
        
        return $data;
    }
    
    
    /**
     * Взима наличните записи за модела
     *
     * @param mixed $mvc
     * @param int   $id
     */
    public static function getRecs($mvc, $id)
    {
        $class = cls::get($mvc);
        $query = self::getQuery();
        $query->where("#documentId = '{$id}' AND #documentClassId = {$class->getClassId()}");
        
        return $query->fetchAll();
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'add') { 
            if ((empty($rec->documentClassId) || empty($rec->documentId))) {
                $requiredRoles = 'no_one';
            } elseif (isset($rec->documentClassId, $rec->documentId)) {
                $Document = new core_ObjectReference($rec->documentClassId, $rec->documentId);
                $dRec = $Document->fetch('state,contragentClassId,contragentId,folderId');
                $isCons = cond_Parameters::getParameter($dRec->contragentClassId, $dRec->contragentId, 'consignmentContragents');
                
                if (!$Document->isInstanceOf('store_DocumentMaster')) {
                    $requiredRoles = 'no_one';
                } elseif ($isCons !== 'yes') {
                    $requiredRoles = 'no_one';
                } elseif ($dRec->state != 'draft') {
                    $requiredRoles = 'no_one';
                } else {
                    $groupId = cat_Groups::fetchField("#sysId = 'packagings'", 'id');
                    $Cover = doc_Folders::getCover($dRec->folderId);
                    if(!cat_Products::getProducts($Cover->getClassId(), $Cover->that, null, 'canStore', null, 1, false, $groupId)){
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
        
        // Да не може да се променя ако документа не е чернова
        if (($action == 'edit' || $action == 'delete') && isset($rec->documentClassId, $rec->documentId)) {
            $Document = new core_ObjectReference($rec->documentClassId, $rec->documentId);
            if ($Document->fetchField('state') != 'draft') {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Рендиране на детайла
     */
    public function renderDetail_($data)
    {
        if (!countR($data->recs)) {
            
            return new core_ET('');
        }

        return parent::renderDetail_($data);
    }
    
    
    /**
     * Връща съответния мастер
     */
    public function getMasterMvc_($rec)
    {
        return cls::get($rec->documentClassId);
    }
    
    
    /**
     * Преди подготовка на заглавието на формата
     */
    protected static function on_BeforePrepareEditTitle($mvc, &$res, $data)
    {
        $rec = &$data->form->rec;
        $data->singleTitle = ($rec->type == 'out') ? 'предаден амбалаж' : 'приет амбалаж';
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm(core_Mvc $mvc, &$data)
    {
        $form = &$data->form;
        $masterRec = $data->masterRec;
        
        if (isset($form->rec->id)) {
            $form->setField('type', 'input');
        }
        
        $groupId = cat_Groups::fetchField("#sysId = 'packagings'", 'id');
        $Cover = doc_Folders::getCover($masterRec->folderId);
        $form->setDefault('productType', 'ours');

        $params = array('customerClass' => $Cover->getClassId(), 'customerId' => $Cover->that, 'groups' => $groupId, 'hasnotProperties' => 'generic');
        $params['hasProperties'] = ($form->rec->type == 'in') ? 'canBuy,canStore' : 'canSell,canStore';
        if($form->rec->productType == 'other'){
            $params['isPublic'] = 'no';
        }

        $data->form->setFieldTypeParams('productId', $params);
    }
    
    
    /**
     * Метод по реализация на определянето на движението генерирано от реда
     *
     * @param stdClass $rec
     *
     * @return string
     */
    public function getBatchMovementDocument($rec)
    {
        return isset($rec->type) ? $rec->type : 'out';
    }
    
    
    /**
     * Подготвя записите
     *
     * За предадените артикули:
     * 		Dt: 3231. СМЗ на отговорно пазене				    (Контрагенти, Артикули)
     *      Ct: 321. Суровини, материали, продукция, стоки	    (Складове, Артикули)
     *
     * За върнатите артикули:
     * 		Dt: 321. Суровини, материали, продукция, стоки		(Складове, Артикули)
     *      Ct: 3231. СМЗ на отговорно пазене					(Контрагенти, Артикули)
     */
    public static function getEntries($mvc, $rec, $isReverse = false)
    {
        $entries = array();
        $sign = 1;
        
        $dRecs = self::getRecs($mvc->getClassId(), $rec->id);

        // Ако е за "Наши артикули"
        $theirs = $ours = array();
        array_walk($dRecs, function($a) use(&$theirs, &$ours){if($a->productType == 'other') {$theirs[] = $a;} else {$ours[] = $a;}});
        $combined = array_values($theirs);

        $ourCombined = array();
        foreach ($ours as $ourRec) {
            if(!array_key_exists($ourRec->productId, $ourCombined)){
                $ourCombined[$ourRec->productId] = (object)array('productId' => $ourRec->productId, 'productType' => 'ours');
            }

            $signOurs = ($ourRec->type == 'in') ? 1 : -1;
            $ourCombined[$ourRec->productId]->quantity += $signOurs * $ourRec->quantity;
        }

        foreach ($ourCombined as $ourRec1) {
            $clone = clone $ourRec1;
            $clone->type = ($ourRec1->quantity > 0) ? 'in' : 'out';
            $clone->quantity = abs($ourRec1->quantity);
            $combined[] = $clone;
        }

        foreach ($combined as $dRec) {
            $acc323Id = ($dRec->productType == 'ours') ? '3231' : '3232';

            $arr323 = array($acc323Id, array($rec->contragentClassId, $rec->contragentId),
                array('cat_Products', $dRec->productId),
                'quantity' => $sign * $dRec->quantity);

            $arr321 = array('321', array('store_Stores', $rec->storeId),
                array('cat_Products', $dRec->productId),
                'quantity' => $sign * $dRec->quantity);

            if ($dRec->type == 'in') {
                $entry = array('debit' => $arr321, 'credit' => $arr323);
            } else {
                $entry = array('debit' => $arr323, 'credit' => $arr321);
            }

            if($acc323Id == '3232'){
                $amount = round($dRec->amount * $rec->currencyRate, 2);
                $entry['amount'] = $amount;
            }

            $entries[] = $entry;
        }
        
        return $entries;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->type = "<div class='centered'>{$row->type}</div>";
    }


    /**
     * Добавя бутони към тулбара
     *
     * @param core_Toolbar $toolbar
     * @param core_Master $mvc
     * @param int $documentId
     */
    public static function addBtnsToToolbar(&$toolbar, $mvc, $documentId)
    {
        if (store_DocumentPackagingDetail::haveRightFor('add', (object)array('documentClassId' => $mvc->getClassId(), 'documentId' => $documentId))) {
            $toolbar->addBtn('Отг.пазене: ПРЕДАВАНЕ', array('store_DocumentPackagingDetail', 'add', 'documentClassId' => $mvc->getClassId(), 'documentId' => $documentId, 'type' => 'out', 'ret_url' => true), null, 'title=Отговорно пазене: предаване КЪМ Контрагент,ef_icon=img/16/lorry_add.png,row=2');
            $toolbar->addBtn('Отг.пазене: ПРИЕМАНЕ', array('store_DocumentPackagingDetail', 'add', 'documentClassId' => $mvc->getClassId(), 'documentId' => $documentId, 'type' => 'in', 'ret_url' => true), null, 'title=Отговорно пазене: приемане ОТ Контрагент,ef_icon=img/16/lorry_add.png,row=2');
        }
    }
}
