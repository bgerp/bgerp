<?php


/**
 * Клас 'cat_DisassemblyBomDetails'
 *
 * Детайли на рецептата за разпад. Два вида редове:
 * - 'input'      - допълнителни артикули за влагане. Основният артикул за
 *                  разпад стои в мастъра (@see cat_DisassemblyBoms), а
 *                  добавянето на допълнителни засега е забранено интерфейсно
 *                  (@see on_AfterGetRequiredRoles) - моделът ги поддържа за
 *                  когато бъдат разрешени
 * - 'production' - произведените от разпада артикули (могат да са няколко)
 *
 * Полето `quantity` е базовото количество на реда (в основна мярка), спрямо
 * което по-късно ще се мащабира Протоколът за разпад. `costPercent` е
 * незадължителен ръчно зададен % от себестойността на вложения артикул,
 * ползван само за 'production' редове, когато произведените артикули не са
 * в еднаква мярка (@see cat_DisassemblyBoms::calcAllocation).
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
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, cat_Wrapper, plg_Sorting, plg_SaveAndNew, cat_plg_ShowCodes, plg_AlignDecimals2, plg_PrevAndNext';


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
    public $listFields = 'tools=№,productId=Артикул,packagingId,packQuantity=К-во,costPercent=%,allocatedCost=Разпред. себестойност';


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
        $this->FNC('packQuantity', 'double(min=0)', 'caption=Количество,input=input,mandatory,smartCenter');
        $this->FLD('quantityInPack', 'double(smartRound)', 'input=none,notNull,value=1');
        $this->FLD('quantity', 'double', 'caption=Количество,input=none,smartCenter');

        $this->FLD('costPercent', 'percent(min=0,max=1,allowEmpty)', 'caption=% от себестойността,tdClass=accCell,placeholder=Автоматично');
        $this->FNC('allocatedCost', 'double(decimals=2)', 'caption=Разпределена себестойност,input=none,tdClass=accCell');

        $this->setDbIndex('productId');
        $this->setDbIndex('bomId,type');
    }


    /**
     * Изчисляване на количеството на реда в брой опаковки (за FNC полето `packQuantity`)
     */
    public static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
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

        $data->singleTitle = ($rec->type == 'input') ? 'допълнителен артикул за влагане' : 'произведен артикул';
        $data->defaultMeta = ($rec->type == 'input') ? 'canConvert,canStore' : 'canManifacture';
        $data->defaultNotHaveMeta = 'generic';

        // % от себестойността има смисъл само за произведените артикули
        if ($rec->type != 'production') {
            $form->setField('costPercent', 'input=none');
        }
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
            $productInfo = cat_Products::getProductInfo($rec->productId);
            $rec->quantityInPack = isset($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
            $rec->quantity = $rec->packQuantity * $rec->quantityInPack;
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     *
     * Основният артикул за влагане е в мастъра (@see cat_DisassemblyBoms).
     * Моделът поддържа и допълнителни артикули за влагане ('input' редове),
     * но добавянето им засега е забранено интерфейсно
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($action == 'add' && isset($rec->type) && $rec->type == 'input') {
            $res = 'no_one';
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->productId = cat_Products::getVerbal($rec->productId, 'name');
        if (!(Mode::is('text', 'xhtml') || Mode::is('printing') || Mode::is('pdf'))) {
            $singleUrl = cat_Products::getSingleUrlArray($rec->productId);
            $row->productId = ht::createLinkRef($row->productId, $singleUrl);
        }

        // Вложимите редове са зелени като в технологичната рецепта, а произведените
        // се отличават с 'state-active' - и двата класа са налични в стиловете
        $row->ROW_ATTR['class'] = ($rec->type == 'input') ? 'row-added' : 'state-active';

        if ($rec->type == 'production') {
            static $costCache = array();
            if (!array_key_exists($rec->bomId, $costCache)) {
                $costCache[$rec->bomId] = cat_DisassemblyBoms::getProductionCosts($rec->bomId);
            }
            $costs = $costCache[$rec->bomId];

            if (is_array($costs) && isset($costs[$rec->id])) {
                $row->allocatedCost = cls::get('type_Double', array('params' => array('decimals' => 2)))->toVerbal($costs[$rec->id]);
            } else {
                $row->allocatedCost = ht::createHint("<span class='red'>???</span>", 'Себестойността не може да се изчисли', 'warning', false);
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

                $row->tools->append($Int->toVerbal($count++), 'TOOLS');
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

        // Мини-таблица с допълнителните артикули за влагане - засега винаги
        // празна, защото добавянето им е забранено интерфейсно
        // (@see on_AfterGetRequiredRoles), основният е в мастъра
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
        $pData = clone $data;
        $pData->listTableMvc = clone $data->listTableMvc;
        $pData->listFields = $commonListFields;
        $pData->listFields['productId'] = 'Произведени артикули|* ';
        $pData->rows = $data->productionArr;
        $pData->recs = array_intersect_key($pData->recs, $pData->rows);

        $this->invoke('BeforeRenderListTable', array(&$tpl, &$pData));
        $productionTable = cls::get('core_TableView', array('mvc' => $pData->listTableMvc));
        $productionTable->tableClass = 'listTable disassemblyBomTable';
        $tpl->append($productionTable->get($pData->rows, $pData->listFields), 'PRODUCED_PRODUCTS_TABLE');

        if (!Mode::isReadOnly() && $this->haveRightFor('add', (object) array('bomId' => $data->masterId, 'type' => 'production'))) {
            $tpl->append(ht::createBtn('Произвеждане', array($this, 'add', 'bomId' => $data->masterId, 'type' => 'production', 'ret_url' => true), null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/door_in.png', 'title' => 'Добавяне на произведен артикул')), 'PRODUCED_PRODUCTS_TABLE');
        }

        return $tpl;
    }
}