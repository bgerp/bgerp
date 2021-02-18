<?php


/**
 * Клас 'planning_DirectProductNoteDetails'
 *
 * Детайли на мениджър на детайлите на протокола за производство
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_DirectProductNoteDetails extends deals_ManifactureDetail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на протокола за производство';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Ресурс';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'noteId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_SaveAndNew,deals_plg_ImportDealDetailProduct, plg_Created, planning_Wrapper, plg_Sorting, 
                        planning_plg_ReplaceEquivalentProducts, plg_PrevAndNext,cat_plg_ShowCodes';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,planning,store,production';
    
    
    /**
     * Кой има право да променя взаимно заменяемите артикули?
     */
    public $canReplaceproduct = 'ceo,planning,store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,planning,store,production';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,planning,store,production';
    
    /**
     * Кой може да го импортира артикули?
     *
     * @var string|array
     */
    public $canImport = 'user';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=№,productId=Материал, packagingId, packQuantity=За влагане,quantityFromBom=От рецептата,storeId';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     *
     *  @var string
     */
    public $hideListFieldsIfEmpty = 'quantityFromBom';
    
    
    /**
     * Активен таб
     */
    public $currentTab = 'Протоколи->Производство';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=planning_DirectProductionNote)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('type', 'enum(input=Влагане,pop=Отпадък)', 'caption=Действие,silent,input=hidden');
        parent::setDetailFields($this);
        $this->setField('quantity', 'caption=Количества');
        $this->FLD('quantityFromBom', 'double', 'caption=От рецепта,input=none,smartCenter');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Изписване от,input=none,tdClass=small-field nowrap,placeholder=Незавършено производство');
        
        $this->setDbIndex('productId');
        $this->setDbIndex('noteId,type');
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
        $rec = &$form->rec;
        $data->singleTitle = ($rec->type == 'pop') ? 'отпадък' : 'материал';
        $data->defaultMeta = ($rec->type == 'pop') ? 'canConvert,canStore' : 'canConvert';
        
        if (isset($rec->productId)) {
            $storable = cat_Products::fetchField($rec->productId, 'canStore');
            if ($storable == 'yes') {
                $form->setField('storeId', 'input');
                
                if (empty($rec->id) && isset($data->masterRec->inputStoreId)) {
                    $form->setDefault('storeId', $data->masterRec->inputStoreId);
                }
            }
        }
        
        if ($rec->type == 'pop') {
            $form->setField('storeId', 'input=none');
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        $rec = &$form->rec;
        
        if (isset($rec->productId)) {
            if ($form->isSubmitted()) {
                
                // Проверка на к-то
                $warning = null;
                if (!deals_Helper::checkQuantity($rec->packagingId, $rec->packQuantity, $warning)) {
                    $form->setWarning('packQuantity', $warning);
                }
                
                // Ако добавяме отпадък, искаме да има себестойност
                if ($rec->type == 'pop') {
                    $selfValue = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $rec->productId);
                    
                    if (!isset($selfValue)) {
                        $form->setError('productId', 'Отпадъкът няма себестойност');
                    }
                }
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows($mvc, &$data)
    {
        if (!countR($data->recs)) {
            
            return;
        }
        
        foreach ($data->rows as $id => &$row) {
            $rec = &$data->recs[$id];
            $row->ROW_ATTR['class'] = ($rec->type == 'input') ? 'row-added' : 'row-removed';
            if (isset($rec->storeId)) {
                $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
            }
            
            if ($rec->type == 'pop') {
                $row->packQuantity .= " {$row->packagingId}";
            }
        }
    }
    
    
    /**
     * След подготовка на детайлите, изчислява се общата цена
     * и данните се групират
     */
    protected static function on_AfterPrepareDetail($mvc, $res, $data)
    {
        $data->inputArr = $data->popArr = array();
        $countInputed = $countPoped = 1;
        $Int = cls::get('type_Int');
        
        // За всеки детайл (ако има)
        if (countR($data->rows)) {
            foreach ($data->rows as $id => $row) {
                $rec = $data->recs[$id];
                if (!is_object($row->tools)) {
                    $row->tools = new ET('[#TOOLS#]');
                }
                
                // Разделяме записите според това дали са вложими или не
                if ($rec->type == 'input') {
                    $num = $Int->toVerbal($countInputed);
                    $data->inputArr[$id] = $row;
                    $countInputed++;
                } else {
                    $num = $Int->toVerbal($countPoped);
                    $data->popArr[$id] = $row;
                    $countPoped++;
                }
                
                $row->tools->append($num, 'TOOLS');
            }
        }
    }


    /**
     * Помощна ф-я за модифициране на записите
     */
    private function modifyRows($data)
    {
        if(!countR($data->rows)) return;

        $origin = doc_Containers::getDocument($data->masterData->rec->originId);
        if($origin->isInstanceOf('planning_Tasks')){
            $origin = doc_Containers::getDocument($origin->fetchField('originId'));
        }

        foreach ($data->rows as $id => &$row) {
            $rec = $data->recs[$id];
            if (empty($rec->storeId)) {
                $row->storeId = "<span class='quiet'>"  . tr('Незавършено производство') . '</span>';
            } elseif($rec->type != 'pop') {
                $threadId = $origin->fetchField('threadId');
                $deliveryDate = (!empty($data->masterData->rec->deadline)) ? $data->masterData->rec->deadline : $data->masterData->rec->valior;
                deals_Helper::getQuantityHint($row->packQuantity, $rec->productId, $rec->storeId, $rec->quantity, $data->masterData->rec->state, $deliveryDate, $threadId);
            }

            if(!empty($rec->quantityFromBom)){
                $rec->quantityFromBom /= $rec->quantityInPack;
                $row->quantityFromBom = $this->getFieldType('quantityFromBom')->fromVerbal($rec->quantityFromBom);
            }
        }
    }


    /**
     * Променяме рендирането на детайлите
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    public function renderDetail_($data)
    {
        $tpl = new ET('');
        
        if (Mode::is('printing')) {
            unset($data->listFields['tools']);
        }
        
        // Рендираме таблицата с вложените материали
        $data->listFields['productId'] = 'Вложени артикули|* ';
        
        $fieldset = clone $this;
        $fieldset->FNC('num', 'int');
        $table = cls::get('core_TableView', array('mvc' => $fieldset));
        
        $iData = clone $data;
        $iData->listTableMvc = clone $this;
        $iData->rows = $data->inputArr;
        $iData->recs = array_intersect_key($iData->recs, $iData->rows);

        $this->invoke('BeforeRenderListTable', array(&$tpl, &$iData));
        plg_AlignDecimals2::alignDecimals($this, $iData->recs, $iData->rows);
        
        $iData->listFields = core_TableView::filterEmptyColumns($iData->rows, $iData->listFields, $this->hideListFieldsIfEmpty);
        $this->modifyRows($iData);
        $detailsInput = $table->get($iData->rows, $iData->listFields);
        $tpl->append($detailsInput, 'planning_DirectProductNoteDetails');
        
        // Добавяне на бутон за нов материал
        if ($this->haveRightFor('add', (object) array('noteId' => $data->masterId, 'type' => 'input'))) {
            $tpl->append(ht::createBtn('Артикул', array($this, 'add', 'noteId' => $data->masterId, 'type' => 'input', 'ret_url' => true), null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/wooden-box.png', 'title' => 'Добавяне на нов материал')), 'planning_DirectProductNoteDetails');
            $tpl->append(ht::createBtn('Импортиране', array($this, 'import', 'noteId' => $data->masterId, 'type' => 'input', 'ret_url' => true), null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/wooden-box.png', 'title' => 'Добавяне на нов материал')), 'planning_DirectProductNoteDetails');
        }

        // Рендираме таблицата с отпадъците
        if (countR($data->popArr) || $data->masterData->rec->state == 'draft') {
            $data->listFields['productId'] = "Отпадъци|* <small style='font-weight:normal'>( |остават в незавършеното производство|* )</small>";
            unset($data->listFields['storeId']);
            
            $pData = clone $data;
            $pData->listTableMvc = clone $this;
            $pData->rows = $data->popArr;
            $pData->recs = array_intersect_key($pData->recs, $pData->rows);

            $this->invoke('BeforeRenderListTable', array(&$tpl, &$pData));
            plg_AlignDecimals2::alignDecimals($this, $pData->recs, $pData->rows);
            $pData->listFields = core_TableView::filterEmptyColumns($pData->rows, $pData->listFields, $this->hideListFieldsIfEmpty);
            $this->modifyRows($pData);

            $popTable = $table->get($pData->rows, $pData->listFields);
            $detailsPop = new core_ET("<span style='margin-top:5px;'>[#1#]</span>", $popTable);
            
            $tpl->append($detailsPop, 'planning_DirectProductNoteDetails');
        }
        
        // Добавяне на бутон за нов отпадък
        if ($this->haveRightFor('add', (object) array('noteId' => $data->masterId, 'type' => 'pop'))) {
            $tpl->append(ht::createBtn('Отпадък', array($this, 'add', 'noteId' => $data->masterId, 'type' => 'pop', 'ret_url' => true), null, null, array('style' => 'margin-top:5px;;margin-bottom:10px;', 'ef_icon' => 'img/16/recycle.png', 'title' => 'Добавяне на нов отпадък')), 'planning_DirectProductNoteDetails');
        }
        
        // Връщаме шаблона
        return $tpl;
    }
    
    
    /**
     * Метод по пдоразбиране на getRowInfo за извличане на информацията от реда
     */
    protected static function on_AfterGetRowInfo($mvc, &$res, $rec)
    {
        $rec = $mvc->fetchRec($rec);
        if (empty($rec->storeId)) {
            unset($res->operation);
        } else {
            $res->operation[key($res->operation)] = $rec->storeId;
        }
    }
}
