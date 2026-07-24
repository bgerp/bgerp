<?php


/**
 * Клас 'planning_DisassemblyNoteDetails'
 *
 * Детайли на мениджъра на детайлите на протокола за разпад. Два вида редове:
 * - 'input'      - артикулът за разпад (в тази версия - само 1 ред, точно артикула
 *                  от Заданието за разпад)
 * - 'production' - произведените от разпада артикули (могат да са няколко)
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_DisassemblyNoteDetails extends deals_ManifactureDetail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на протокола за разпад';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Артикул';


    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'noteId';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, doc_plg_DetailRevisions, planning_Wrapper, plg_Sorting, plg_SaveAndNew, cat_plg_LogPackUsage, plg_PrevAndNext, plg_AlignDecimals2, cat_plg_ShowCodes';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,production,store';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,production,store';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,production';


    /**
     * Може ли да се импортират цени
     */
    public $allowPriceImport = false;


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=№,productId=Артикул,packagingId,packQuantity=К-во,storeId=Склад';


    /**
     * Активен таб
     */
    public $currentTab = 'Протоколи->Разпад';


    /**
     * В кои състояния на мастъра може да се редактира детайла
     */
    protected $allowedInMasterStates = array('draft', 'pending', 'active');


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=planning_DisassemblyNote)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('type', 'enum(input=Влагане,production=Произвеждане)', 'caption=Действие,silent,input=hidden');
        $this->FLD('isMainInput', 'enum(no=Не,yes=Да)', 'caption=Основен артикул за влагане,input=none,notNull,value=no');
        parent::setDetailFields($this);
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty,mandatory)', 'caption=Склад,input=none,tdClass=custom-field nowrap', array('thAttr' => array('style' => 'width:160px')));

        $this->setDbIndex('productId');
        $this->setDbIndex('noteId,type');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        $data->singleTitle = ($rec->type == 'input') ? 'артикул за разпад' : 'произведен артикул';
        $data->defaultMeta = ($rec->type == 'input') ? 'canConvert,canStore' : 'canManifacture';
        $data->defaultNotHaveMeta = 'generic';

        if (empty($rec->id)) {
            $form->setFieldType('packQuantity', 'double(Min=0)');
        }

        $jobRec = planning_DisassemblyNote::getJobRec($rec->noteId);
        if ($rec->type == 'input') {

            // В тази версия само 1 артикул за влагане - точно този от Заданието
            if (is_object($jobRec)) {
                $form->setFieldTypeParams('productId', array('onlyIn' => array($jobRec->productId)));
                $form->setDefault('productId', $jobRec->productId);
                if (empty($rec->id)) {
                    $form->setReadOnly('productId');
                }
            }
        }

        // Показване на поле за избор на склад при нужда
        if(isset($rec->productId)){
            $productRec = cat_Products::fetch($rec->productId, 'canStore');
            if($productRec->canStore == 'yes'){
                $form->setField('storeId', 'input,mandatory');
                if(isset($data->masterRec->storeId)){
                    $form->setDefault('storeId', $data->masterRec->storeId);
                }
            }
        }
    }


    /**
     * Определя посоката на партидното движение
     */
    public function getBatchMovementDocument($rec)
    {
        return $rec->type == 'production' ? 'in' : 'out';
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'delete' && isset($rec)) {
            $rec = $mvc->fetchRec($rec);
            if ($rec->isMainInput == 'yes') {
                $requiredRoles = 'no_one';
            } elseif(planning_DisassemblyNoteDetails::count("#type = 'production' AND #noteId = {$rec->noteId}") == 1){
                $requiredRoles = 'no_one';
            }
        }

        // В тази версия - само 1 артикул за влагане (точно от Заданието)
        if ($action == 'add' && isset($rec->type) && $rec->type == 'input') {
            if ($mvc->count("#noteId = {$rec->noteId} AND #type = 'input'")) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Преди подготвяне на едит формата
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if (isset($rec->storeId)) {
            $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
        } else {
            $productRec = cat_Products::fetch($rec->productId, 'canStore');
            if($productRec->canStore == 'yes'){
                $row->storeId = "<span class='red'>n/a</span>";
            }
        }
    }


    /**
     * doc_plg_DetailRevisions вече празни productId за оттеглените редове, но
     * batch_plg_DocumentMovementDetail го презаписва (с "Без партида") на
     * СЪЩОТО събитие СЛЕД него. Класовите хукове се изпълняват последни (@see
     * core_BaseClass::invoke), затова тук - гарантирано най-накрая - пак го празним
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $activeGroups = doc_plg_DetailRevisions::groupsWithActiveRow($data->recs);

        foreach ($data->rows as $id => $row) {
            $rec = $data->recs[$id];
            if ((($rec->state ?? null) == 'rejected') && isset($activeGroups[$rec->revisionRootId ?: $rec->id])) {
                $row->productId = '';
            }
        }
    }


    /**
     * След подготовка на детайлите - разделяне на редовете по вид (за 2-те таблици)
     */
    protected static function on_AfterPrepareDetail($mvc, $res, $data)
    {
        $data->inputArr = $data->productionArr = $data->mainInputArr = array();
        $countInput = $countProduction = 1;
        $Int = cls::get('type_Int');

        // Пази вече раздадения пореден номер на всяка ревизионна група (@see
        // doc_plg_DetailRevisions), за да не се пропуска номерацията заради
        // оттеглени редове от историята на един и същ логически ред
        $inputNumByGroup = $productionNumByGroup = array();

        if (countR($data->rows)) {
            foreach ($data->rows as $id => $row) {
                $rec = $data->recs[$id];

                if (!is_object($row->tools ?? null)) {
                    $row->tools = new ET('[#TOOLS#]');
                }

                // Иконка по вид на реда - артикулите за влагане/разпад имат
                // отделна иконка от произведените/върнатите артикули
                $icon = ($rec->type == 'input')
                    ? "<span class='green' style='font-weight:bold;' title='" . tr('Влагане') . "'>⇩</span>"
                    : "<span class='red' style='font-weight:bold;' title='" . tr('Връщане') . "'>⇧</span>";

                // Основният вложен артикул се показва отделно, в собствена таблица
                // над таблицата с произведените артикули (@see renderDetail_/MAIN_INPUT_PRODUCT_TABLE),
                // а не в таблицата с (други) артикули за влагане
                if ($rec->type == 'input' && $rec->isMainInput == 'yes') {
                    $row->tools->append("{$icon} " . $Int->toVerbal(1), 'TOOLS');
                    $data->mainInputArr[$id] = $row;
                    continue;
                }

                $groupKey = $rec->revisionRootId ?: $rec->id;

                if ($rec->type == 'input') {
                    if (!isset($inputNumByGroup[$groupKey])) {
                        $inputNumByGroup[$groupKey] = $countInput++;
                    }
                    $num = $Int->toVerbal($inputNumByGroup[$groupKey]);
                    $data->inputArr[$id] = $row;
                } else {
                    if (!isset($productionNumByGroup[$groupKey])) {
                        $productionNumByGroup[$groupKey] = $countProduction++;
                    }
                    $num = $Int->toVerbal($productionNumByGroup[$groupKey]);
                    $data->productionArr[$id] = $row;
                }

                $row->tools->append("{$icon} {$num}", 'TOOLS');
            }
        }
    }


    /**
     * Променяме рендирането на детайлите - 2 отделни таблици
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

        // Мини-таблица с ОСНОВНИЯ артикул за разпад - показва се отделно, над
        // таблицата с произведените артикули (виж MAIN_INPUT_PRODUCT_TABLE в
        // шаблона), не заедно с (евентуални) други артикули за влагане
        if (countR($data->mainInputArr)) {
            $data->listFields['productId'] = 'Артикули за разпад|* ';
            $mData = clone $data;
            $mData->listTableMvc = clone $this;
            $mData->rows = $data->mainInputArr;
            $mData->recs = array_intersect_key($mData->recs, $mData->rows);
            unset($mData->listFields['tools']);

            $this->invoke('BeforeRenderListTable', array(&$tpl, &$mData));
            $mData->listFields['storeId'] = 'От склад';
            $mData->listFields = core_TableView::filterEmptyColumns($mData->rows, $mData->listFields, '*');

            $mainInputTable = cls::get('core_TableView', array('mvc' => $mData->listTableMvc));
            $detailsMainInput = $mainInputTable->get($mData->rows, $mData->listFields);
            $tpl->append($detailsMainInput, 'MAIN_INPUT_PRODUCT_TABLE');
        }

        // Таблица с (други) артикули за разпад (влагане) - само ако има такива
        if (countR($data->inputArr)) {
            $data->listFields['productId'] = 'Артикули за разпад|* ';
            $iData = clone $data;
            $iData->listTableMvc = clone $this;
            $iData->rows = $data->inputArr;
            $iData->recs = array_intersect_key($iData->recs, $iData->rows);

            $this->invoke('BeforeRenderListTable', array(&$tpl, &$iData));
            $iData->listTableMvc->FNC('tools', 'int', 'tdClass=rowNumColumn');
            $iData->listFields['storeId'] = 'От склад';
            $iData->listFields = core_TableView::filterEmptyColumns($iData->rows, $iData->listFields, '*');

            $inputTable = cls::get('core_TableView', array('mvc' => $iData->listTableMvc));
            $detailsInput = $inputTable->get($iData->rows, $iData->listFields);
            $tpl->append($detailsInput, 'INPUT_PRODUCTS_TABLE');
        }

        if ($this->haveRightFor('add', (object) array('noteId' => $data->masterId, 'type' => 'input'))) {
            $tpl->append(ht::createBtn('Артикули за разпад', array($this, 'add', 'noteId' => $data->masterId, 'type' => 'input', 'ret_url' => true), null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/wooden-box.png', 'title' => 'Добавяне на артикула за разпад')), 'INPUT_PRODUCTS_TABLE');
        }

        // Таблица с произведените от разпада артикули
        $data->listFields['productId'] = 'Произведени артикули|* ';
        $pData = clone $data;
        $pData->listTableMvc = clone $this;
        $pData->rows = $data->productionArr;
        $pData->recs = array_intersect_key($pData->recs, $pData->rows);
        $pData->listFields['storeId'] = 'В склад';

        $this->invoke('BeforeRenderListTable', array(&$tpl, &$pData));
        $pData->listTableMvc->FNC('tools', 'int', 'tdClass=rowNumColumn');
        $pData->listFields = core_TableView::filterEmptyColumns($pData->rows, $pData->listFields, '*');

        $productionTable = cls::get('core_TableView', array('mvc' => $pData->listTableMvc));
        $detailsProduction = $productionTable->get($pData->rows, $pData->listFields);
        $tpl->append($detailsProduction, 'PRODUCED_PRODUCTS_TABLE');

        if ($this->haveRightFor('add', (object) array('noteId' => $data->masterId, 'type' => 'production'))) {
            if(!Mode::isReadOnly()){
                $tpl->append(ht::createBtn('Произведен артикул', array($this, 'add', 'noteId' => $data->masterId, 'type' => 'production', 'ret_url' => true), null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/door_in.png', 'title' => 'Добавяне на произведен артикул')), 'PRODUCED_PRODUCTS_TABLE');
            }
        }

        return $tpl;
    }


    /**
     * да Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        $rec = &$form->rec;
        if ($rec->type == 'input' && isset($rec->productId) && !empty($rec->storeId)) {
            $masterRec = planning_DisassemblyNote::fetch($rec->noteId, 'deadline,valior');
            $deliveryDate = (!empty($masterRec->deadline)) ? $masterRec->deadline : $masterRec->valior;
            $storeInfo = deals_Helper::checkProductQuantityInStore($rec->productId, $rec->packagingId, $rec->packQuantity, $rec->storeId, $deliveryDate);
            $form->info = $storeInfo->formInfo;
        }
    }


    /**
     * Метод по пдоразбиране на getRowInfo за извличане на информацията от реда
     */
    protected static function on_AfterGetRowInfo($mvc, &$res, $rec)
    {
        $rec = $mvc->fetchRec($rec);
        $res->operation = array();
        if($rec->type == 'input') {
            $res->operation['out'] = $rec->storeId;
        } else {
            $res->operation['in'] = $rec->storeId;
        }
    }


    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        if($data->masterRec->state == 'active'){
            $data->form->toolbar->setWarning('save', 'Протоколът е вече контиран, при запис ще бъде реконтиран|*!');
            if ($data->form->toolbar->haveButton('saveAndNew')) {
                $data->form->toolbar->setWarning('saveAndNew', 'Протоколът е вече контиран, при запис ще бъде реконтиран|*!');
            }
        }
    }
}
