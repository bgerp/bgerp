<?php


/**
 * Клас 'cat_DisassemblyBoms' - Документ за рецепта за разпад
 *
 * Съдържа един артикул за влагане (вложим и складируем) и няколко произведени
 * артикула (складируеми и нескладируеми)
 *
 * Себестойността на вложения артикул се разпределя между произведените по процент
 * на ред. Начинът на разпределяне е явен избор в самата рецепта - по ценова
 * политика, ръчно или по количество (@see $allocationBy, @see calcPercents).
 *
 * Процентите не се записват - смятат се на живо по актуалните цени, за да ги
 * ползва после и Протоколът за разпад.
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
class cat_DisassemblyBoms extends core_Master
{
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf';


    /**
     * Заглавие
     */
    public $title = 'Рецепти за разпад';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Рецепта за разпад';


    /**
     * Абревиатура
     */
    public $abbr = 'Bdm';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, cat_Wrapper, doc_DocumentPlg, doc_plg_MasterRevision, plg_Printing, doc_plg_Close, doc_plg_Prototype, acc_plg_DocumentSummary, doc_ActivatePlg, doc_plg_SingleActiveDoc, plg_Clone, cat_plg_AddSearchKeywords, plg_Search, plg_Sorting, change_Plugin, cat_plg_DisassemblyDoc, cond_plg_DefaultValues';


    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array('allocationBy' => 'lastDocUser|lastDoc');


    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'title,quantity,expenses,detailOrderBy,notes';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title=Документ,productId=Артикул,quantity=За,state,createdOn,createdBy';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'notes,title,productId';


    /**
     * Детайла на модела
     */
    public $details = 'cat_DisassemblyBomDetails';


    /**
     * Кой е основния детайл
     */
    public $mainDetail = 'cat_DisassemblyBomDetails';


    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'cat_DisassemblyBomDetails';


    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'title,lastUpdatedDetailOn,lastUpdatedDetailBy';


    /**
     * Кои полета да не бъдат презаписвани от шаблона - артикулът идва от URL-то
     * и не бива да се взима от образеца (@see doc_plg_Prototype)
     */
    public $fieldsNotToCopyFromTemplate = 'productId';


    /**
     * Дали да се показват последно видяните документи при избора на шаблонен
     * @see doc_plg_Prototype
     */
    public $showInPrototypesLastVisited = true;


    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsSingleField = 'title';


    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/article_decay.png';


    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'cat/tpl/SingleLayoutDisassemblyBom.shtml';


    /**
     * Да се показва ли антетката
     */
    public $showLetterHead = true;


    /**
     * Кой може да пише?
     */
    public $canEdit = 'ceo,production,store';


    /**
     * Кой може да добавя?
     */
    public $canAdd = 'ceo,production';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,production,store,planningAll';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,production,store';


    /**
     * Кой може да променя активирани записи
     *
     * @see change_Plugin
     */
    public $canChangerec = 'ceo,production,store';


    /**
     * Кой може да затваря?
     */
    public $canClose = 'ceo,production,store';


    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn,lastUpdatedDetailOn,modifiedOn';


    /**
     * Искаме ли в листовия филтър да е попълнен филтъра по дата
     *
     * @see acc_plg_DocumentSummary
     */
    public $filterAutoDate = false;


    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;


    /**
     * Поле, определящо групата за затваряне на "конкурентните" активни
     * рецепти - само 1 активна рецепта за даден артикул за разпад
     *
     * @see doc_plg_SingleActiveDoc
     */
    public $singleActiveDocRefField = 'productId';


    /**
     * Поле за подредбата на детайла (@see cat_plg_ShowCodes)
     */
    public $detailOrderByField = 'detailOrderBy';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('title', 'varchar(124,nullIfEmpty)', 'caption=Заглавие,tdClass=nameCell');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,input,silent,mandatory,input=hidden');
        $this->FLD('quantity', 'double(smartRound,Min=0)', 'caption=За,silent,mandatory');
        $this->FLD('expenses', 'percent(min=0)', 'caption=Реж. разходи,changeable');
        // Без „Ваш №“ - рецептата няма контрагент, а оттам и ценови лист
        $this->FLD('detailOrderBy', 'enum(auto=Автоматично,creation=Ред на създаване,code=Код)', 'caption=Влагане (на артикула за разпад)->Подреждане по,notNull,value=auto');

        $this->FLD('state', 'enum(draft=Чернова,active=Активирана,rejected=Оттеглена,closed=Затворена,template=Шаблон)', 'caption=Статус,input=none');
        $this->FLD('notes', 'richtext(rows=4,bucket=Notes)', 'caption=Допълнително->Забележки');
        $this->FLD('allProductsAreInTheSameUomId', 'enum(yes=Да,no=Не)', 'caption=Произведените артикули са в еднаква мярка,input=none,notNull,value=yes');
        $this->FLD('lastUpdatedDetailOn', 'datetime(format=smartTime)', 'caption=Промяна на детайла->На,silent,input=none');
        $this->FLD('lastUpdatedDetailBy', 'key(mvc=core_Users,select=nick)', 'caption=Промяна на детайла->От,input=none');

        $this->setDbIndex('productId');
        $this->setDbIndex('state');
    }


    /**
     * Преди показване на форма за добавяне/промяна - артикулът за разпад се
     * подава през URL-то и не се избира ръчно
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        $form->setReadOnly('productId');
        $productRec = cat_Products::fetch($rec->productId, 'measureId');
        $shortUom = cat_UoM::getShortName($productRec->measureId);
        $form->setField('quantity', "unit={$shortUom}");
        $form->setDefault('quantity', 1);

    }


    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        // Рецептата е винаги към артикул - не може да се създава в нова нишка
        if (!empty($data->form->toolbar->buttons['btnNewThread'])) {
            $data->form->toolbar->removeBtn('btnNewThread');
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($action == 'add' && isset($rec)) {

            if (isset($rec->originId)) {
                $origin = doc_Containers::getDocument($rec->originId);

                if($origin->isInstanceOf('planning_Jobs')){
                    $jobRec = $origin->fetch('threadId,state');
                    if(in_array($jobRec->state, array('draft', 'rejected', 'closed'))) {
                        $res = 'no_one';
                    } elseif(!doc_Threads::haveRightFor('single', $jobRec->threadId)){
                        $res = 'no_one';
                    }
                } elseif(!$origin->isInstanceOf('cat_Products')){
                    $res = 'no_one';
                }
            }

            // Артикулът за разпад винаги идва през URL-то
            if (empty($rec->productId)) {
                $res = 'no_one';
            } else {
                $productRec = cat_Products::fetch($rec->productId, 'state,canConvert,canStore,generic');
                if ($productRec->canConvert != 'yes' || $productRec->canStore != 'yes' || $productRec->generic == 'yes') {
                    $res = 'no_one';
                } elseif (!in_array($productRec->state, array('active', 'template'))) {
                    $res = 'no_one';
                }
            }
        }

        // Ако няма ид, не може да се активира
        if ($action == 'activate') {
            if (empty($rec->id)) {
                $res = 'no_one';
            } elseif (!cat_DisassemblyBomDetails::count("#bomId = {$rec->id} AND #type = 'production'")) {
                $res = 'no_one';
            }
        }

        // Затваря се само вече активирана рецепта (или се отваря затворена)
        if ($action == 'close' && isset($rec)) {
            if (!in_array($rec->state, array('active', 'closed'))) {
                $res = 'no_one';
            }
        }
    }


    /**
     * Сумата на артикула по подадената ценова политика
     *
     * @param int|null      $priceListId
     * @param int           $productId
     * @param float         $quantity
     * @param datetime|null $date - към коя дата е цената (null - към сега)
     *
     * @return float|null - null, ако няма политика или цена по нея
     */
    public static function getAmount($priceListId, $productId, $quantity, $date = null)
    {
        if (empty($priceListId)) return null;

        $price = price_ListRules::getPrice($priceListId, $productId, null, $date);
        if (!isset($price)) return null;

        return $price * $quantity;
    }


    /**
     * Сумата на реда във вербален вид - общо за рецептата и за Протокола за разпад
     *
     * @param int           $priceListId
     * @param int           $productId
     * @param float         $quantity
     * @param datetime|null $date      - към коя дата е цената (null - към сега)
     * @param null|string   $errorHint - грешка, защо няма цена
     *
     * @return string
     */
    public static function getAmountVerbal($priceListId, $productId, $quantity, $date = null, &$errorHint = null)
    {
        $amount = static::getAmount($priceListId, $productId, $quantity, $date);
        if (!isset($amount)) {
            $errorHint = 'Артикулът няма цена по избраната ценова политика|*! |За него се пада 0% от себестойността|*!';

            return ht::createHint("<span class='red'>???</span>", $errorHint, 'warning', false);
        }

        $amountVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($amount);
        $amountVerbal = currency_Currencies::decorate($amountVerbal, null, true);

        return "<span class='blueText'>{$amountVerbal}</span>";
    }


    /**
     * Процентът на реда във вербален вид - общо за рецептата и за Протокола за разпад
     *
     * @param float|null  $percent      - изчисленият процент (null - неопределим)
     * @param string      $allocationBy - 'price', 'manual' или 'quantity'
     * @param string|null $error        - защо процентът не може да се определи
     *
     * @return string|null - null, когато важи ръчно въведеният процент
     */
    public static function getPercentVerbal($percent, $allocationBy, $error = null)
    {
        if (!isset($percent)) {

            return ht::createHint("<span class='red'>n/a</span>", $error ?: 'Процентът не може да се определи', 'warning', false);
        }

        if ($allocationBy == 'manual') return null;

        $percentVerbal = core_Type::getByName('percent(decimals=2)')->toVerbal($percent);
        $hint = ($allocationBy == 'price') ? 'Изчислен по стойността на реда по избраната ценова политика' : 'Изчислен пропорционално на количеството на реда';

        return ht::createHint("<span class='blueText'>{$percentVerbal}</span>", $hint, 'notice', false);
    }


    /**
     * Изчислява какъв % от себестойността се пада на всеки от подадените редове.
     * Вложеният артикул не участва - той дава само себестойността
     *
     * @param array         $productsArr  - обекти с productId, quantity и costPercent
     * @param string        $allocationBy - 'price', 'manual' или 'quantity'
     * @param int|null      $priceListId  - ид на ценова политика (само при 'price')
     * @param datetime|null $date         - към коя дата са цените (null - към сега)
     * @param array         $statuses     - ['error'] спира активирането, ['warning'] е за сведение
     *
     * @return array $productsArr - същите обекти с попълнени percent и amount
     */
    public static function calcPercents($productsArr, $allocationBy, $priceListId = null, $date = null, &$statuses = array())
    {
        core_Debug::startTimer('DISASSEMBLY_CALC_PERCENTS');

        $statuses = array();
        foreach ($productsArr as $obj) {
            $obj->percent = $obj->amount = null;
        }

        if (countR($productsArr)) {
            if ($allocationBy == 'price') {
                static::calcPercentsByPrice($productsArr, $priceListId, $date, $statuses);
            } elseif ($allocationBy == 'manual') {
                static::calcPercentsManually($productsArr, $statuses);
            } else {
                static::calcPercentsByQuantity($productsArr, $statuses);
            }

            // Сборът е точно 100% при всяко разпределение
            if (!isset($statuses['error'])) {
                $sum = 0;
                foreach ($productsArr as $obj) {
                    $sum += $obj->percent ?? 0;
                }

                if (abs($sum - 1) > 0.0001) {
                    $percentVerbal = core_Type::getByName('percent')->toVerbal($sum);
                    $statuses['error'] = "Сумата на процентите от себестойността трябва да е 100%|*, |а е|*: {$percentVerbal}";
                }
            }
        }

        core_Debug::stopTimer('DISASSEMBLY_CALC_PERCENTS');

        return $productsArr;
    }


    /**
     * Разпределяне по стойността на реда по избраната политика (@see calcPercents)
     *
     * @param array         $productsArr
     * @param int|null      $priceListId
     * @param datetime|null $date
     * @param array         $statuses
     *
     * @return void
     */
    private static function calcPercentsByPrice($productsArr, $priceListId, $date, &$statuses)
    {
        if (empty($priceListId)) {
            $statuses['error'] = 'Не е избрана ценова политика за разпределяне на себестойността|*!';

            return;
        }

        $totalAmount = 0;
        $missingArr = array();

        foreach ($productsArr as $obj) {
            $obj->amount = static::getAmount($priceListId, $obj->productId, $obj->quantity, $date);

            // Липсва цена - изреждат се всички виновници, а не само първият
            if (!isset($obj->amount)) {
                $missingArr[$obj->productId] = cat_Products::getTitleById($obj->productId);
                continue;
            }

            $totalAmount += $obj->amount;
        }

        $listTitle = price_Lists::getTitleById($priceListId);
        if ($totalAmount <= 0) {
            $statuses['error'] = 'Себестойността не може да се разпредели|*, |защото по ценова политика|* "' . $listTitle . '" |произведените артикули са без цена или на нулева стойност|*!';

            return;
        }

        // Редът без цена няма дял - вместо да се пропусне, изрично получава 0%
        foreach ($productsArr as $obj) {
            $obj->percent = ($obj->amount ?? 0) / $totalAmount;
        }

        if (countR($missingArr)) {
            $statuses['warning'] = 'По ценова политика|* "' . $listTitle . '" |липсва цена за артикул|*: <b>' . implode(', ', $missingArr) . '</b>. |За тях се пада 0% от себестойността|*!';
        }
    }


    /**
     * Разпределяне по ръчно зададените проценти (@see calcPercents)
     *
     * @param array $productsArr
     * @param array $statuses
     *
     * @return void
     */
    private static function calcPercentsManually($productsArr, &$statuses)
    {
        $missingArr = array();
        foreach ($productsArr as $obj) {
            if (!isset($obj->costPercent)) {
                $missingArr[$obj->productId] = cat_Products::getTitleById($obj->productId);
                continue;
            }

            $obj->percent = $obj->costPercent;
        }

        if (countR($missingArr)) {
            $statuses['error'] = 'Няма ръчно зададен процент от себестойността за артикул|*: <b>' . implode(', ', $missingArr) . '</b>';
        }
    }


    /**
     * Разпределяне по количеството в основна мярка (@see calcPercents)
     *
     * @param array $productsArr
     * @param array $statuses
     *
     * @return void
     */
    private static function calcPercentsByQuantity($productsArr, &$statuses)
    {
        $productIds = array();
        foreach ($productsArr as $obj) {
            $productIds[$obj->productId] = $obj->productId;
        }

        $measureArr = array();
        if (!cat_Products::areProductsInTheSameUom($productIds, $measureArr)) {
            $statuses['error'] = 'Количествата не могат да се сравняват|*, |защото произведените артикули не са в производни една на друга мерки|*: <b>' . implode(', ', array_map('cat_Products::getTitleById', $productIds)) . '</b>';

            return;
        }

        $totalQuantity = 0;
        foreach ($productsArr as $obj) {
            $obj->_baseQuantity = cat_UoM::convertToBaseUnit($obj->quantity, $measureArr[$obj->productId]);
            $totalQuantity += $obj->_baseQuantity;
        }

        if ($totalQuantity <= 0) {
            $statuses['error'] = 'Количествата на произведените артикули са нулеви|*!';

            return;
        }

        foreach ($productsArr as $obj) {
            $obj->percent = $obj->_baseQuantity / $totalQuantity;
            unset($obj->_baseQuantity);
        }
    }



    /**
     * Обновява данни в мастъра при промяна по детайла - живо смятаната
     * себестойност зависи от редовете, затова кешът трябва да се инвалидира
     *
     * @param int $id
     * @return int
     */
    public function updateMaster_($id)
    {
        $rec = $this->fetchRec($id);

        doc_DocumentCache::cacheInvalidation($rec->containerId);
        $rec->lastUpdatedDetailOn = dt::now();
        $rec->lastUpdatedDetailBy = core_Users::getCurrent();

        // Информативно - всички ли ПРОИЗВЕДЕНИ артикули са в еднаква мярка
        $dQuery = cat_DisassemblyBomDetails::getQuery();
        $dQuery->where("#bomId = {$rec->id} AND #type = 'production'");
        $dQuery->where("#state != 'rejected'");
        $dQuery->show('productId');
        $productIds = arr::extractValuesFromArray($dQuery->fetchAll(), 'productId');

        $rec->allProductsAreInTheSameUomId = cat_Products::areProductsInTheSameUom($productIds) ? 'yes' : 'no';

        return $this->save_($rec, 'lastUpdatedDetailOn,lastUpdatedDetailBy,allProductsAreInTheSameUomId,modifiedOn,modifiedBy,searchKeywords');
    }


    /**
     * Функция, която се извиква преди активирането на документа
     *
     * Не се активира рецепта, по която себестойността не може да се разпредели
     */
    protected static function on_BeforeActivation($mvc, $res)
    {
        if (empty($res->id)) return;

        $statuses = array();
        cat_plg_DisassemblyDoc::getPercents($mvc, $res->id, null, $statuses);

        // Не спира активирането, но потребителят се уведомява
        if (isset($statuses['warning'])) {
            core_Statuses::newStatus($statuses['warning'], 'warning');
        }

        if (isset($statuses['error'])) {
            core_Statuses::newStatus($statuses['error'], 'error');

            return false;
        }
    }


    /**
     * Връща последната активна рецепта за разпад на артикула. Активната е
     * най-много една (@see doc_plg_SingleActiveDoc), подредбата е застраховка
     *
     * @param mixed $productId - ид или запис на артикул
     *
     * @return stdClass|false
     */
    public static function getLastActiveBom($productId)
    {
        $productRec = cat_Products::fetchRec($productId, 'id,canStore,canConvert');
        if (!is_object($productRec) || ($productRec->canStore != 'yes' || $productRec->canConvert != 'yes')) {
            return false;
        }

        $query = static::getQuery();
        $query->where("#productId = {$productRec->id} AND #state = 'active'");
        $query->orderBy('id', 'DESC');

        return $query->fetch();
    }


    /**
     * Заглавие на записа - хендълът (с префикса от $abbr), заглавието на
     * рецептата и артикула, който се разпада
     *
     * @param mixed $rec
     * @param bool  $escaped
     *
     * @return string
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $rec = static::fetchRec($rec);
        $title = static::getHandle($rec);
        if (!empty($rec->title)) {
            $title .= '/' . static::getVerbal($rec, 'title');
        }
        $title .= '/' . cat_Products::getTitleById($rec->productId);

        return str::limitLen($title, 94);
    }


    /**
     * Добавя допълнителни полета в антетката (@see doc_DocumentPlg)
     */
    protected static function on_AfterGetFieldForLetterHead($mvc, &$resArr, $rec, $row)
    {
        $resArr = arr::make($resArr);

        $resArr['quantity'] = array('name' => tr('Разпад'), 'val' => tr("|*<table class='docHeaderVal'>
                <tr><td style='font-weight:normal'>|За|*:</td><td>[#quantity#]</td></tr>
                <!--ET_BEGIN expenses--><tr><td style='font-weight:normal'>|Режийни разходи|*:</td><td>[#expenses#]</td></tr><!--ET_END expenses-->
                <tr><td style='font-weight:normal'>|Разпределяне на сб-ст|*:</td><td>[#allocationBy#]</td></tr>
                <!--ET_BEGIN priceListId--><tr><td style='font-weight:normal'>|Ценова политика|*:</td><td>[#priceListId#]</td></tr><!--ET_END priceListId-->
                <!--ET_BEGIN clonedFromId--><tr><td style='font-weight:normal'>|Клонирано от|*:</td><td>[#clonedFromId#]</td></tr><!--ET_END clonedFromId-->
        </table>"));

        $resArr['info'] = array('name' => tr('Информация'), 'val' => tr("|*<table class='docHeaderVal'>
                <tr><td style='font-weight:normal'>|Модифициранe|*:</td><td>[#modifiedOn#]</b> |от|* [#modifiedBy#]</td></tr>
                <!--ET_BEGIN lastUpdatedDetailOn--><tr><td style='font-weight:normal'>|Промяна на детайл|*:</td><td>[#lastUpdatedDetailOn#]</td></tr><!--ET_END lastUpdatedDetailOn-->
                <tr><td style='font-weight:normal'>|Произв. в еднаква мярка|*:</td><td>[#allProductsAreInTheSameUomId#]</td></tr>
                </table>"));
    }


    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow_($id)
    {
        $rec = $this->fetchRec($id);

        $row = new stdClass();
        $row->title = $this->getRecTitle($rec);
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $rec->title;

        return $row;
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($rec->productId)) {
            $row->productId = cat_Products::getHyperlink($rec->productId, true);
        }

        $shortUom = cat_UoM::getShortName(cat_Products::fetchField($rec->productId, 'measureId'));
        $row->quantity = ($row->quantity ?? '') . ' ' . $shortUom;

        // В сингъла заглавието се показва само ако е въведено ръчно (@see cat_Boms)
        if (isset($fields['-single'])) {
            $row->title = empty($rec->title) ? null : $mvc->getVerbal($rec, 'title');

            // Извън режима "по ценова политика" записаната политика е бездейна
            if ($rec->allocationBy == 'price' && !empty($rec->priceListId)) {
                $row->priceListId = price_Lists::getHyperlink($rec->priceListId, true);
            } else {
                unset($row->priceListId);
            }
        } else {
            $row->title = $mvc->getHyperlink($rec, true);
        }

        if (isset($rec->clonedFromId)) {
            $row->clonedFromId = $mvc->getLink($rec->clonedFromId, 0);
        }
    }
}