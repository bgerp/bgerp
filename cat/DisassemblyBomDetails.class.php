<?php


/**
 * Клас 'cat_DisassemblyBomDetails'
 *
 * Детайли на рецептата за разпад. Два вида редове:
 * - 'input'      - допълнителни артикули за влагане. Основният артикул за разпад
 *                  стои в мастъра (@see cat_DisassemblyBoms), а добавянето на
 *                  допълнителни засега е забранено интерфейсно
 * - 'production' - произведените от разпада артикули (могат да са няколко)
 *
 * Полето `quantity` е базовото количество на реда (в основна мярка), спрямо
 * което по-късно ще се мащабира Протоколът за разпад.
 *
 * За 'production' редовете има една колона с процент от себестойността - при ръчно
 * разпределяне се въвежда, иначе се смята на живо (@see cat_plg_DisassemblyDocDetail)
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_DisassemblyBomDetails extends doc_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на рецептата за разпад';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Артикул';


    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'bomId';


    /**
     * Интерфейс на драйверите за импортиране
     */
    public $importInterface = 'cat_interface_BomDetailImportIntf';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, doc_plg_DetailRevisions, cat_Wrapper, plg_Sorting, plg_SaveAndNew, cat_plg_ShowCodes, plg_AlignDecimals2, plg_PrevAndNext, cat_plg_DisassemblyDocDetail, import2_Plugin';


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
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=№,productId=Артикул,packagingId,packQuantity=К-во,costPercent=% (сб-ст),amount=Сума';


    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях - сумата
     * я има само за произведените артикули
     */
    public $hideListFieldsIfEmpty = 'amount';


    /**
     * Поле с артикула - за подредбата на бутоните напред/назад (@see cat_plg_ShowCodes)
     */
    public $productFieldName = 'productId';


    /**
     * Активен таб
     */
    public $currentTab = 'Рецепти->Разпад';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('bomId', 'key(mvc=cat_DisassemblyBoms)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('type', 'enum(input=Влагане,production=Произвеждане)', 'caption=Действие,silent,input=hidden');

        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax,titleFld=name,forceOpen)', 'class=w100,tdClass=productCell leftCol wrap,caption=Артикул,mandatory,silent,removeAndRefreshForm=packagingId|packQuantity');
        $this->FLD('packagingId', 'key(mvc=cat_UoM,select=shortName,select2MinItems=0)', 'caption=Мярка,tdClass=small-field nowrap,smartCenter,mandatory,input=hidden,silent');
        $this->FNC('packQuantity', 'double(Min=0)','caption=Количество,input=input,mandatory,smartCenter');
        $this->FLD('quantityInPack', 'double(smartRound)', 'input=none,notNull,value=1');
        $this->FLD('quantity', 'double', 'caption=Количество,input=none,smartCenter');
        $this->FLD('notes', 'richtext(rows=3,bucket=Notes)', 'caption=Описание');

        $this->FNC('amount', 'double(decimals=2)', 'caption=Сума,input=none,tdClass=accCell');

        // Без уникалност по артикул - оттеглените ревизии повтарят артикула
        $this->setDbIndex('productId');
        $this->setDbIndex('bomId,type');
    }


    /**
     * Изчисляване на количеството на реда в брой опаковки (за FNC полето `packQuantity`)
     */
    protected static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (!isset($rec->quantity) || !isset($rec->quantityInPack)) return;

        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        if(isset($rec->id)){
            $form->setReadOnly('productId');
        }

        $data->singleTitle = ($rec->type == 'input') ? 'допълнителен артикул за влагане' : 'произведен артикул';
        $data->defaultMeta = ($rec->type == 'input') ? 'canConvert,canStore' : 'canManifacture';
        $data->defaultNotHaveMeta = 'generic';
        $form->setFieldTypeParams('productId', array('notIn' => $data->masterRec->productId));
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        $rec = &$form->rec;

        if (isset($rec->productId)) {
            $packs = cat_Products::getPacks($rec->productId, $rec->packagingId ?? null);
            $form->setOptions('packagingId', $packs);
            $form->setDefault('packagingId', key($packs));
            $form->setField('packagingId', 'input');
        }

        if ($form->isSubmitted()) {

            // Артикулът е само на един неоттеглен ред - оттам и проверката тук,
            // вместо с уникален индекс
            $exQuery = static::getQuery();
            $exQuery->where(array("#bomId = [#1#] AND #type = '[#2#]' AND #productId = [#3#]", $rec->bomId, $rec->type, $rec->productId));
            $exQuery->where("#state != 'rejected'");
            if (isset($rec->id)) {
                $exQuery->where("#id != {$rec->id}");
            }

            if ($exQuery->fetch()) {
                $form->setError('productId', 'Артикулът вече е добавен в рецептата|*!');
            }

            // При влагане к-то не може да е нула, нито закръглено
            $warning = null;
            if ($rec->type == 'input') {
                if (empty($rec->packQuantity)) {
                    $form->setError('packQuantity', 'Количеството за влагане не може да е нула|*!');
                } elseif (!deals_Helper::checkQuantity($rec->packagingId, $rec->packQuantity, $warning)) {
                    $form->setError('packQuantity', $warning);
                }
            } elseif (!deals_Helper::checkQuantity($rec->packagingId, $rec->packQuantity, $warning)) {
                $form->setWarning('packQuantity', $warning);
            }

            $productInfo = cat_Products::getProductInfo($rec->productId);
            $rec->quantityInPack = isset($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
            $rec->quantity = $rec->packQuantity * $rec->quantityInPack;
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     *
     * Добавянето на допълнителни артикули за влагане засега е забранено
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        // Само докато рецептата е чернова или активирана (@see doc_plg_DetailRevisions)
        if (in_array($action, array('add', 'edit', 'delete')) && isset($rec->bomId)) {
            if (!in_array(cat_DisassemblyBoms::fetchField($rec->bomId, 'state'), array('draft', 'active'))) {
                $res = 'no_one';
            }
        }

        if ($action == 'add' && isset($rec->type) && $rec->type == 'input') {
            $res = 'no_one';
        }

        if($action == 'delete' && isset($rec)){
            if(cat_DisassemblyBomDetails::count("#bomId={$rec->bomId} AND #type = 'production'") == 1){
                $res = 'no_one';
            }
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->productId = cat_Products::getTitleById($rec->productId);
        if (!Mode::isReadOnly()) {
            $singleUrl = cat_Products::getSingleUrlArray($rec->productId);
            $row->productId = ht::createLinkRef($row->productId, $singleUrl);
        }
        deals_Helper::addNotesToProductRow($row->productId, $rec->notes);

        // Вложимите са зелени, произвежданите - жълти, като в технологичната рецепта.
        // Оттеглените ги оцветява doc_plg_DetailRevisions
        if (($rec->state ?? null) != 'rejected') {
            $row->ROW_ATTR['class'] = ($rec->type == 'input') ? 'row-added' : 'row-subProduct';
        }

        // Сумата по избраната ценова политика - по нея се разпределя себестойността
        static $masterCache = array();
        if (!array_key_exists($rec->bomId, $masterCache)) {
            $masterCache[$rec->bomId] = cat_DisassemblyBoms::fetch($rec->bomId);
        }
        $bomRec = $masterCache[$rec->bomId];

        // Сумата има смисъл само при разпределяне по политика
        if ($rec->type == 'production' && is_object($bomRec) && $bomRec->allocationBy == 'price' && $rec->state != 'rejected') {
            $errorHint = null;
            $row->amount = cat_DisassemblyBoms::getAmountVerbal($bomRec->priceListId, $rec->productId, $rec->quantity, null, $errorHint);

            // Бърз линк за нова цена има смисъл само при избрана политика
            if (!empty($bomRec->priceListId) && price_ListRules::haveRightFor('add', (object) array('productId' => $rec->productId, 'listId' => $bomRec->priceListId))) {
                $addPriceUrl = array('price_ListRules', 'add', 'type' => 'value', 'listId' => $bomRec->priceListId, 'productId' => $rec->productId, 'priority' => 1, 'ret_url' => true);
                if(empty($errorHint)){
                    core_RowToolbar::createIfNotExists($row->_rowTools);
                    $row->_rowTools->addLink('Нова цена', $addPriceUrl, 'ef_icon=img/16/add.png,title=Промяна на цената по избраната ценова политика за разпад');
                } else {
                    $row->amount .= ht::createLink('', $addPriceUrl, false, 'ef_icon=img/16/add.png,title=Промяна на цената по избраната ценова политика за разпад');
                }
            }
        }
    }



    /**
     * След подготовка на детайлите - разделяне на редовете по вид (за 2-те таблици)
     */
    protected static function on_AfterPrepareDetail($mvc, $res, $data)
    {
        $data->inputArr = $data->productionArr = array();
        $count = 1;
        $Int = cls::get('type_Int');

        // Номер на ревизионна група (@see doc_plg_DetailRevisions), за да не се
        // пропуска номерацията заради оттеглените редове
        $numByGroup = array();

        if (countR($data->rows)) {
            foreach ($data->rows as $id => $row) {
                $rec = $data->recs[$id];

                if (!is_object($row->tools ?? null)) {
                    $row->tools = new ET('[#TOOLS#]');
                }

                if ($rec->type == 'input') {
                    $data->inputArr[$id] = $row;
                    continue;
                }

                $groupKey = ($rec->revisionRootId ?? null) ?: $rec->id;
                if (!isset($numByGroup[$groupKey])) {
                    $numByGroup[$groupKey] = $count++;
                }

                $row->tools->append($Int->toVerbal($numByGroup[$groupKey]), 'TOOLS');
                $data->productionArr[$id] = $row;
            }
        }
    }


    /**
     * Променяме рендирането на детайлите - 2 отделни таблици (допълнителни
     * артикули за влагане / произведени артикули), опростен вариант на
     * planning_DisassemblyNoteDetails::renderDetail_ (без ревизии/партиди -
     * тук няма реално складово движение)
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

        $data->listTableMvc = clone $this;
        $data->listTableMvc->FNC('tools', 'int', 'tdClass=rowNumColumn');

        // Мини-таблица с допълнителните артикули за влагане - засега винаги празна
        if (countR($data->inputArr)) {
            $iData = clone $data;
            $iData->listTableMvc = clone $data->listTableMvc;
            $iData->listFields = arr::make('productId=Допълнителни артикули за влагане,packagingId,packQuantity=К-во', true);
            $iData->rows = $data->inputArr;
            $iData->recs = array_intersect_key($iData->recs, $iData->rows);

            $this->invoke('BeforeRenderListTable', array(&$tpl, &$iData));
            $inputTable = cls::get('core_TableView', array('mvc' => $iData->listTableMvc));
            $inputTable->tableClass = 'listTable disassemblyBomTable';
            $tpl->append($inputTable->get($iData->rows, $iData->listFields), 'INPUT_PRODUCT_TABLE');
        }

        // Таблица с произведените артикули
        $commonListFields = arr::make($data->listFields, true);

        // Сумата има смисъл само при разпределяне по политика
        if (cat_DisassemblyBoms::fetchField($data->masterId, 'allocationBy') != 'price') {
            unset($commonListFields['amount']);
        }

        $pData = clone $data;
        $pData->listTableMvc = clone $data->listTableMvc;
        $pData->listFields = $commonListFields;
        $pData->listFields['productId'] = 'Произведени артикули|* ';
        $pData->rows = $data->productionArr;
        $pData->recs = array_intersect_key($pData->recs, $pData->rows);

        $this->invoke('BeforeRenderListTable', array(&$tpl, &$pData));

        // Празните колони се махат ръчно - рендира се директно през core_TableView
        $pData->listFields = core_TableView::filterEmptyColumns($pData->rows, $pData->listFields, arr::make($this->hideListFieldsIfEmpty, true));

        $productionTable = cls::get('core_TableView', array('mvc' => $pData->listTableMvc));
        $productionTable->tableClass = 'listTable disassemblyBomTable';
        $productionTableTpl = $productionTable->get($pData->rows, $pData->listFields);

        cat_plg_DisassemblyDocDetail::appendTotalRow($productionTableTpl, $pData);

        // Предупреждението се слага най-горе, над името на артикула за разпад
        if (!empty($data->percentWarning)) {
            $tpl->append("<div class='richtext-message richtext-warning'>{$data->percentWarning}</div>", 'percentWarning');
        }

        $tpl->append($productionTableTpl, 'PRODUCED_PRODUCTS_TABLE');

        if (!Mode::isReadOnly() && $this->haveRightFor('add', (object) array('bomId' => $data->masterId, 'type' => 'production'))) {
            $tpl->append(ht::createBtn('Артикул', array($this, 'add', 'bomId' => $data->masterId, 'type' => 'production', 'ret_url' => true), null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/door_in.png', 'title' => 'Добавяне на произведен артикул')), 'PRODUCED_PRODUCTS_TABLE');
        }

        $importRec = (object) array('bomId' => $data->masterId);
        if (!Mode::isReadOnly() && $this->haveRightFor('import2', $importRec)) {
            $tpl->append(ht::createBtn('Импорт', array($this, 'import2', 'bomId' => $data->masterId, 'ret_url' => true), null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/import.png', 'title' => 'Импортиране на произведени артикули')), 'PRODUCED_PRODUCTS_TABLE');
        }

        // При бутоните под таблицата, както е и в Протокола за разпад
        cat_plg_DisassemblyDocDetail::appendAllocateBtn($tpl, $this->Master, $data->masterId, 'PRODUCED_PRODUCTS_TABLE', 'margin-top:5px;margin-bottom:15px;');

        // Групово изтриване само на произведените - тулбарът на core_Detail не е тук
        if(!Mode::isReadOnly()){
            if ($this->haveRightFor('selectrowstodelete', (object) array('bomId' => $data->masterId, '_filterFld' => 'type', '_filterFldVal' => 'production'))) {
                $tpl->append(ht::createBtn('Изтриване', array($this, 'selectRowsToDelete', 'bomId' => $data->masterId, '_filterFld' => 'type', '_filterFldVal' => 'production', 'ret_url' => true), null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/delete.png', 'title' => 'Форма за избор на редове за изтриване', 'class' => 'selectDeleteRowsBtn')), 'PRODUCED_PRODUCTS_TABLE');
            }
        }

        return $tpl;
    }
}