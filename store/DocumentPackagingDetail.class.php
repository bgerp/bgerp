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
    public $loadList = 'plg_RowTools2, store_Wrapper, plg_SaveAndNew,plg_AlignDecimals2, LastPricePolicy=sales_SalesLastPricePolicy';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId=Артикул, packagingId, packQuantity,type,packPrice, amount';
    
    
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
        parent::setFields($this);
        $this->setField('amount', 'smartCenter');
        $this->FLD('type', 'enum(in=Приемане,out=Предаване)', 'column=none,notNull,silent,mandatory,caption=Действие,after=productId,input=hidden');
        $this->setDbUnique('documentClassId,documentId,productId,packagingId,type');
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
        $data->form->setFieldTypeParams('productId', array('customerClass' => $Cover->getClassId(), 'customerId' => $Cover->that, 'hasProperties' => 'canStore', 'groups' => $groupId));
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
     * 		Dt: 323. СМЗ на отговорно пазене				    (Контрагенти, Артикули)
     *      Ct: 321. Суровини, материали, продукция, стоки	    (Складове, Артикули)
     *
     * За върнатите артикули:
     * 		Dt: 321. Суровини, материали, продукция, стоки		(Складове, Артикули)
     *      Ct: 323. СМЗ на отговорно пазене					(Контрагенти, Артикули)
     */
    public static function getEntries($mvc, $rec, $isReverse = false)
    {
        $entries = array();
        $sign = 1;
        
        $dRecs = self::getRecs($mvc->getClassId(), $rec->id);
        foreach ($dRecs as $dRec) {
            $quantity = $dRec->quantityInPack * $dRec->packQuantity;
            $arr323 = array('323', array($rec->contragentClassId, $rec->contragentId),
                array('cat_Products', $dRec->productId),
                'quantity' => $sign * $quantity);
            
            $arr321 = array('321', array('store_Stores', $rec->storeId),
                array('cat_Products', $dRec->productId),
                'quantity' => $sign * $quantity);
            
            if ($dRec->type == 'in') {
                $entry = array('debit' => $arr321, 'credit' => $arr323);
            } else {
                $entry = array('debit' => $arr323, 'credit' => $arr321);
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
}
