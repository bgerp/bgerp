<?php


/**
 * Клас 'cat_DisassemblyBomDetails'
 *
 * Детайли на рецептата за разпад. Два вида редове:
 * - 'input'      - допълнителни артикули за влагане. Основният артикул за разпад
 *                  стои в мастъра (@see cat_DisassemblyBoms), а добавянето на
 *                  допълнителни засега е забранено интерфейсно
 *                  (@see on_AfterGetRequiredRoles) - моделът ги поддържа за
 *                  когато бъдат разрешени
 * - 'production' - произведените от разпада артикули (могат да са няколко)
 *
 * Полето `quantity` е базовото количество на реда (в основна мярка), спрямо
 * което по-късно ще се мащабира Протоколът за разпад.
 *
 * За 'production' редовете се показват два процента от себестойността на
 * вложения артикул:
 * - `autoPercent` - изчислява се на живо върху всички редове наведнъж, защото
 *   зависи от общия сбор (@see cat_DisassemblyBoms::calcAutoPercents);
 * - `costPercent` - ръчно зададеният, който е само спомагателен и важи
 *   единствено ако за реда автоматичен не може да се изчисли.
 *
 * Кой от двата важи и дали изобщо се получава 100% решава единствено
 * @see cat_DisassemblyBoms::getPercents
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
    public $loadList = 'plg_RowTools2, plg_Created, doc_plg_DetailRevisions, cat_Wrapper, plg_Sorting, plg_SaveAndNew, cat_plg_ShowCodes, plg_AlignDecimals2, plg_PrevAndNext';


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
    public $listFields = 'tools=№,productId=Артикул,packagingId,packQuantity=К-во,autoPercent=% (сб-ст)->Авт.,costPercent=% (сб-ст)->Ръчно,amount=Сума';


    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях - сумата
     * я има само за произведените артикули
     */
    public $hideListFieldsIfEmpty = 'amount';


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
        $this->FLD('costPercent', 'percent(min=0,max=1,allowEmpty)', 'caption=% сб-ст,hint=Каква част от себестойноста на вложимия артикул е. Важи само ако автоматичният процент не може да се изчисли');
        $this->FLD('notes', 'richtext(rows=3,bucket=Notes)', 'caption=Описание');

        $this->FNC('autoPercent', 'percent(decimals=2)', 'caption=Авт. % от сб-ста,input=none,tdClass=accCell');
        $this->FNC('amount', 'double(decimals=2)', 'caption=Сума,input=none,tdClass=accCell');

        // Уникалност по артикул няма - при редакция на активна рецепта старият
        // ред се оттегля, а новият се записва със същия артикул
        // (@see doc_plg_DetailRevisions)
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

        $data->singleTitle = ($rec->type == 'input') ? 'допълнителен артикул за влагане' : 'произведен артикул';
        $data->defaultMeta = ($rec->type == 'input') ? 'canConvert,canStore' : 'canManifacture';
        $data->defaultNotHaveMeta = 'generic';
        $form->setFieldTypeParams('productId', array('notIn' => $data->masterRec->productId));

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

            // Артикулът може да е само на един активен ред в рецептата. Проверката
            // е тук, а не с уникален индекс, защото при редакция на активирана
            // рецепта се плодят оттеглени редове със същия артикул
            $exQuery = static::getQuery();
            $exQuery->where(array("#bomId = [#1#] AND #type = '[#2#]' AND #productId = [#3#]", $rec->bomId, $rec->type, $rec->productId));
            $exQuery->where("#state != 'rejected' OR #state IS NULL");
            if (isset($rec->id)) {
                $exQuery->where("#id != {$rec->id}");
            }

            if ($exQuery->fetch()) {
                $form->setError('productId', 'Артикулът вече е добавен в рецептата|*!');
            }

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
        // Детайлът се променя докато рецептата е чернова или активирана. В
        // чернова редакцията е на място, а в активирана старият ред се оттегля и
        // се записва нов (@see doc_plg_DetailRevisions). Оттеглена, затворена или
        // шаблонна рецепта не се пипа
        if (in_array($action, array('add', 'edit', 'delete')) && isset($rec->bomId)) {
            if (!in_array(cat_DisassemblyBoms::fetchField($rec->bomId, 'state'), array('draft', 'active'))) {
                $res = 'no_one';
            }
        }

        if ($action == 'add' && isset($rec->type) && $rec->type == 'input') {
            $res = 'no_one';
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

        // Вложимите редове са зелени като в технологичната рецепта, а произведените
        // се отличават с 'state-active' - и двата класа са налични в стиловете.
        // Оттеглените ревизии се открояват от doc_plg_DetailRevisions и не им се
        // слага класът за активен ред, за да не се бият стиловете
        if (($rec->state ?? null) != 'rejected') {
            $row->ROW_ATTR['class'] = ($rec->type == 'input') ? 'row-added' : 'state-active';
        }

        // Сумата по избраната ценова политика - по нея се разпределя
        // себестойността, когато има избрана политика
        static $masterCache = array();
        if (!array_key_exists($rec->bomId, $masterCache)) {
            $masterCache[$rec->bomId] = cat_DisassemblyBoms::fetch($rec->bomId);
        }
        $bomRec = $masterCache[$rec->bomId];

        // Без политика и с еднакви мерки разпределението е по количества и цени
        // изобщо не трябват - тогава сумата не се пълни и колоната отпада
        // (@see $hideListFieldsIfEmpty). При различни мерки обаче се показва
        // червено '???', за да е ясно защо процентите не могат да се изчислят
        $priceIsIrrelevant = is_object($bomRec) && empty($bomRec->priceListId) && $bomRec->allProductsAreInTheSameUomId == 'yes';

        if ($rec->type == 'production' && is_object($bomRec) && !$priceIsIrrelevant) {
            $errorHint = null;
            $row->amount = static::getAmountVerbal($bomRec->priceListId, $rec->productId, $rec->quantity, null, $errorHint);

            // Бърз линк за нова цена има смисъл само при избрана политика
            if (!empty($bomRec->priceListId) && price_ListRules::haveRightFor('add', (object) array('productId' => $rec->productId, 'listId' => $bomRec->priceListId)) && $bomRec->state == 'draft') {
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
     * Сумата на реда във вербален вид - в синьо, а при липсваща цена червено '???'
     *
     * @param int|null      $priceListId
     * @param int           $productId
     * @param float         $quantity
     * @param datetime|null $date - към коя дата е цената (null - към сега)
     * @param null|string $errorHint - грешка, защо няма цена
     *
     * @return string
     */
    private static function getAmountVerbal($priceListId, $productId, $quantity, $date = null, &$errorHint = null)
    {
        $amount = cat_DisassemblyBoms::getAmount($priceListId, $productId, $quantity, $date);
        if (!isset($amount)) {
            $errorHint = empty($priceListId) ? 'Не е избрана ценова политика за разпад' : 'Артикулът няма цена по избраната ценова политика за разпад|*!';

            return ht::createHint("<span class='red'>???</span>", $errorHint, 'warning', false);
        }

        $amountVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($amount);
        $amountVerbal = currency_Currencies::decorate($amountVerbal, null,true);

        return "<span style='color:blue'>{$amountVerbal}</span>";
    }


    /**
     * След подготовката на редовете
     *
     * Автоматичният % се смята тук, а не в recToVerbal, защото не зависи само от
     * реда, а от сбора по всички произведени редове. Не се записва в модела -
     * смята се на живо, за да е по актуалните цени (@see cat_DisassemblyBoms)
     */
    protected static function on_AfterPrepareListRows($mvc, &$res, &$data)
    {
        $data->totalPercent = 0;
        $data->autoWarning = null;
        if (!countR($data->recs ?? null)) return;

        // Групиране по рецепта - в детайла е винаги една, но листовият изглед
        // може да смеси редове от няколко. Процентите се вземат за цялата
        // рецепта, а не само за показаните редове - иначе при страниране сборът
        // няма да е върху всички произведени артикули
        $bomIds = array();
        foreach ($data->recs as $rec) {
            if ($rec->type == 'production') {
                $bomIds[$rec->bomId] = $rec->bomId;
            }
        }

        $Percent = core_Type::getByName('percent(decimals=2)');
        $warningArr = array();
        foreach ($bomIds as $bomId) {
            $statuses = array();
            $percentsArr = cat_DisassemblyBoms::getPercents($bomId, null, $statuses);

            // За кои артикули липсват цени и затова важи ръчният им процент -
            // показва се над таблицата (@see renderDetail_)
            if (isset($statuses['autoWarning'])) {
                $warningArr[$bomId] = tr($statuses['autoWarning']);
            }

            foreach ($percentsArr as $id => $obj) {
                if (!array_key_exists($id, $data->rows)) continue;

                if (isset($obj->autoPercent)) {

                    // Автоматичният бие ръчния - тогава ръчният е само за сведение
                    // и се приглушава, а важащият автоматичен е в синьо
                    $percentVerbal = $Percent->toVerbal($obj->autoPercent);
                    $data->rows[$id]->autoPercent = "<span style='color:blue'>{$percentVerbal}</span>";

                    if (isset($obj->costPercent)) {
                        $data->rows[$id]->costPercent = ht::createHint("<span class='quiet'>{$data->rows[$id]->costPercent}</span>", 'Не важи - за реда е изчислен автоматичен процент', 'notice', false);
                    }
                } elseif (isset($obj->costPercent)) {

                    // Няма цена по политиката, затова важи ръчният процент
                    $data->rows[$id]->autoPercent = ht::createHint("<span class='quiet'>n/a</span>", 'Няма как да се изчисли - за реда важи ръчният процент', 'notice', false);
                } else {

                    // Редът няма нито изчислен, нито ръчен процент - причината
                    // идва от самото изчисление
                    $data->rows[$id]->autoPercent = ht::createHint("<span class='red'>n/a</span>", $statuses['autoError'] ?? 'Автоматичният процент не може да се изчисли', 'warning', false);
                }

                $data->totalPercent += $obj->percent ?? 0;
            }
        }

        if (countR($warningArr)) {
            $data->autoWarning = implode('<br>', $warningArr);
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

        // Пази вече раздадения пореден номер на всяка ревизионна група (@see
        // doc_plg_DetailRevisions), за да не се пропуска номерацията заради
        // оттеглени редове от историята на един и същ логически ред
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

        // Мини-таблица с допълнителните артикули за влагане - засега винаги
        // празна, защото добавянето им е забранено интерфейсно
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

        // Празните колони се махат ръчно - таблицата се рендира директно през
        // core_TableView, а не през renderListTable, който го прави сам
        $pData->listFields = core_TableView::filterEmptyColumns($pData->rows, $pData->listFields, arr::make($this->hideListFieldsIfEmpty, true));

        $productionTable = cls::get('core_TableView', array('mvc' => $pData->listTableMvc));
        $productionTable->tableClass = 'listTable disassemblyBomTable';
        $productionTableTpl = $productionTable->get($pData->rows, $pData->listFields);
        $columns = countR($pData->listFields);

        $totalPercentVerbal = core_Type::getByName('percent')->toVerbal($data->totalPercent);
        if($data->totalPercent > 1){
            $totalPercentVerbal = ht::styleIfNegative($totalPercentVerbal, -1);
        } elseif($data->totalPercent == 1) {
            $totalPercentVerbal = "<span style='color:green'>{$totalPercentVerbal}</span>";
        }
        $productionTableTpl->append(tr("|*<tr style='background-color:#eee'><td colspan='{$columns}' style='text-align:right;'>|Общо|*: <b>{$totalPercentVerbal}</b></td></tr>"), 'ROW_AFTER');

        // Предупреждение, че себестойността не се разпределя по стойност, а по
        // количества - слага се най-горе, над името на артикула за разпад
        if (!empty($data->autoWarning)) {
            $tpl->append("<div class='richtext-message richtext-warning'>{$data->autoWarning}</div>", 'percentWarning');
        }

        $tpl->append($productionTableTpl, 'PRODUCED_PRODUCTS_TABLE');

        if (!Mode::isReadOnly() && $this->haveRightFor('add', (object) array('bomId' => $data->masterId, 'type' => 'production'))) {
            $tpl->append(ht::createBtn('Произвеждане', array($this, 'add', 'bomId' => $data->masterId, 'type' => 'production', 'ret_url' => true), null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/door_in.png', 'title' => 'Добавяне на произведен артикул')), 'PRODUCED_PRODUCTS_TABLE');
        }

        // Групово изтриване - само на произведените редове. Тулбарът на
        // core_Detail не се рендира тук, затова бутонът се слага ръчно
        if ($this->haveRightFor('selectrowstodelete', (object) array('bomId' => $data->masterId, '_filterFld' => 'type', '_filterFldVal' => 'production'))) {
            $tpl->append(ht::createBtn('Изтриване', array($this, 'selectRowsToDelete', 'bomId' => $data->masterId, '_filterFld' => 'type', '_filterFldVal' => 'production', 'ret_url' => true), null, null, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/delete.png', 'title' => 'Форма за избор на редове за изтриване', 'class' => 'selectDeleteRowsBtn')), 'PRODUCED_PRODUCTS_TABLE');
        }

        return $tpl;
    }
}