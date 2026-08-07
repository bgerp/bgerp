<?php


/**
 * Клас 'cat_DisassemblyBoms' - Документ за рецепта за разпад
 *
 * Съдържа един артикул за влагане (вложим и складируем) и няколко произведени
 * артикула (складируеми и нескладируеми)
 *
 * Себестойността на вложения артикул се разпределя между произведените по процент
 * на ред. За реда важи най-високото ниво, за което има данни - цена по избраната
 * политика, ръчно зададен процент, количество (@see calcAutoPercents).
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
    public $loadList = 'plg_RowTools2, cat_Wrapper, doc_DocumentPlg, doc_plg_MasterRevision, plg_Printing, doc_plg_Close, doc_plg_Prototype, acc_plg_DocumentSummary, doc_ActivatePlg, doc_plg_SingleActiveDoc, plg_Clone, cat_plg_AddSearchKeywords, plg_Search, plg_Sorting, change_Plugin';


    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'title,expenses,detailOrderBy,notes';


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
     *
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
        $this->FLD('detailOrderBy', 'enum(auto=Автоматично,creation=Ред на създаване,code=Код,reff=Ваш №)', 'caption=Влагане (на артикула за разпад)->Подреждане по,notNull,value=auto');

        $this->FLD('priceListId', 'key(mvc=price_Lists,select=title,allowEmpty)', 'caption=Ценова политика за разпад->Избор');
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

        $listOptions = price_Lists::getAccessibleOptions(null, null, true);
        $form->setOptions('priceListId', array('' => '') + $listOptions);
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
     * Изчислява какъв % от себестойността се пада на всеки от подадените редове
     *
     * За реда важи най-високото ниво, за което има данни - нивата се смесват
     * свободно:
     * 1. Цената по избраната политика - бие ръчния процент на същия ред;
     * 2. Ръчно зададеният процент - абсолютен е, затова се "заковава" и се вади
     *    от 100%;
     * 3. Количеството в основна мярка - за редовете без нищо от горните.
     *
     * Изчисленото по цени и по количества е относително (важи само в рамките на
     * своята група), затова остатъкът след ръчните отива или към едните, или към
     * другите. Има ли и от двата вида - връща се грешка, но изчислимите редове
     * пак получават процент, за да се вижда в детайла докъде се е стигнало.
     *
     * Еднаква мярка се изисква само от редовете, които реално се разпределят по
     * количество. Вложеният артикул не участва във формулата.
     *
     * @param array         $productsArr - обекти с productId, quantity и евентуално
     *                                     costPercent; productId може да се повтаря
     * @param int|null      $priceListId  - ид на ценова политика
     * @param datetime|null $date         - към коя дата са цените (null - към сега)
     * @param array         $statuses     - ['error'] - защо разпределението не е
     *                                      годно (само той спира активирането);
     *                                      ['warning'] - кои редове не се разпределят
     *                                      по избраната политика
     *
     * @return array $productsArr - същите обекти с попълнени autoPercent (изчисленият
     *                              за реда), percent (важащият) и source ('price',
     *                              'manual', 'quantity' или null)
     */
    public static function calcAutoPercents($productsArr, $priceListId, $date = null, &$statuses = array())
    {
        core_Debug::startTimer('DISASSEMBLY_CALC_AUTO_PERCENTS');

        $statuses = array();
        foreach ($productsArr as $obj) {
            $obj->autoPercent = $obj->percent = $obj->source = null;
        }

        if (!countR($productsArr)) {
            core_Debug::stopTimer('DISASSEMBLY_CALC_AUTO_PERCENTS');

            return $productsArr;
        }

        // Ниво 1 - стойността на реда по избраната политика (к-во х цена по нея)
        $amountArr = $missingArr = array();
        if (!empty($priceListId)) {
            foreach ($productsArr as $k => $obj) {
                $amount = static::getAmount($priceListId, $obj->productId, $obj->quantity, $date);

                // Липсва цена - изреждат се всички виновници, а не само първият
                if (!isset($amount)) {
                    $missingArr[$obj->productId] = cat_Products::getTitleById($obj->productId);
                    continue;
                }

                $amountArr[$k] = $amount;
            }

            // Нулева обща стойност не носи информация - все едно няма цени
            if (array_sum($amountArr) <= 0) {
                $amountArr = array();
            }
        }

        // Ниво 2 - ръчните проценти на редовете, за които няма цена. Те са
        // абсолютни и се вадят от 100%, а остатъкът остава за изчислимите
        $fixedSum = 0;
        $restArr = array();
        foreach ($productsArr as $k => $obj) {
            if (isset($amountArr[$k])) continue;

            if (isset($obj->costPercent)) {
                $fixedSum += $obj->costPercent;
            } else {
                $restArr[$k] = $obj;
            }
        }

        // Ниво 1 и ниво 3 са относителни - едновременно не могат да се ползват
        if (countR($amountArr) && countR($restArr)) {
            $restTitles = array();
            foreach ($restArr as $obj) {
                $restTitles[$obj->productId] = cat_Products::getTitleById($obj->productId);
            }
            $statuses['error'] = 'Няма как да се разпредели себестойността на артикул|*: <b>' . implode(', ', $restTitles) . '</b> - |нямат нито цена по избраната ценова политика, нито ръчно зададен процент|*!';
        }

        $remaining = 1 - $fixedSum;
        if ($remaining < -0.0001) {
            $percentVerbal = core_Type::getByName('percent')->toVerbal($fixedSum);
            $statuses['error'] = "Сумата на ръчно зададените проценти надхвърля 100%|*, |а е|*: {$percentVerbal}";

            core_Debug::stopTimer('DISASSEMBLY_CALC_AUTO_PERCENTS');

            return $productsArr;
        }

        if (countR($amountArr) || countR($restArr)) {

            // Ръчните са изяли всичко - за останалите редове не остава дял
            if ($remaining <= 0.0001) {
                $statuses['error'] = 'Ръчно зададените проценти изчерпват 100%|*, |а има артикули без зададен процент|*!';
            } elseif (countR($amountArr)) {

                // Остатъкът се дели между редовете с цена, пропорционално на стойността
                $totalAmount = array_sum($amountArr);
                foreach ($amountArr as $k => $amount) {
                    $productsArr[$k]->autoPercent = $remaining * ($amount / $totalAmount);
                    $productsArr[$k]->source = 'price';
                }
            } else {

                // Остатъкът се дели между редовете без никакви данни, пропорционално
                // на количеството. Еднаква мярка се иска само от тях, а не от всички
                $restProductIds = array();
                foreach ($restArr as $obj) {
                    $restProductIds[$obj->productId] = $obj->productId;
                }

                $measureArr = array();
                if (!cat_Products::areProductsInTheSameUom($restProductIds, $measureArr)) {
                    $statuses['error'] = 'В различни мерки са артикулите, които се разпределят по количество|*: <b>' . implode(', ', array_map('cat_Products::getTitleById', $restProductIds)) . '</b>';
                } else {
                    $quantityArr = array();
                    foreach ($restArr as $k => $obj) {
                        $quantityArr[$k] = cat_UoM::convertToBaseUnit($obj->quantity, $measureArr[$obj->productId]);
                    }

                    $totalQuantity = array_sum($quantityArr);
                    if ($totalQuantity <= 0) {
                        $statuses['error'] = 'Количествата на артикулите, които се разпределят по количество, са нулеви|*!';
                    } else {
                        foreach ($quantityArr as $k => $quantity) {
                            $productsArr[$k]->autoPercent = $remaining * ($quantity / $totalQuantity);
                            $productsArr[$k]->source = 'quantity';
                        }
                    }
                }
            }
        } elseif (abs($fixedSum - 1) > 0.0001) {

            // Всичко се крепи на ръчните проценти - те трябва да покриват 100%
            $percentVerbal = core_Type::getByName('percent')->toVerbal($fixedSum);
            $statuses['error'] = "Сумата на процентите от себестойността трябва да е 100%|*, |а е|*: {$percentVerbal}";
        }

        // Важащият процент - ръчният важи само там, където изчислен няма
        foreach ($productsArr as $obj) {
            if (isset($obj->autoPercent)) {
                $obj->percent = $obj->autoPercent;
            } elseif (isset($obj->costPercent)) {
                $obj->percent = $obj->costPercent;
                $obj->source = 'manual';
            }
        }

        // Част от редовете се разпределят по друга логика, а не по избраната
        // политика - за неразпределимите говори грешката
        if (countR($missingArr)) {
            $redistributedArr = array();
            foreach ($productsArr as $obj) {
                if (isset($missingArr[$obj->productId]) && in_array($obj->source, array('manual', 'quantity'))) {
                    $redistributedArr[$obj->productId] = $missingArr[$obj->productId];
                }
            }

            if (countR($redistributedArr)) {
                $listTitle = price_Lists::getTitleById($priceListId);
                $statuses['warning'] = 'Избрана е ценова политика|* "' . $listTitle . '", |но в нея липсва цена за артикул|*: <b>' . implode(', ', $redistributedArr) . '</b>. |Себестойността за тях се разпределя по ръчно зададения процент или пропорционално на количеството|*!';
            }
        }

        core_Debug::stopTimer('DISASSEMBLY_CALC_AUTO_PERCENTS');

        return $productsArr;
    }


    /**
     * Процентите от себестойността на вложения артикул, които се падат на всеки
     * от произведените редове на рецептата
     *
     * Ползва се и от активирането, и от показването на детайла, а по-нататък и от
     * Протокола за разпад, за да не се разминат (@see calcAutoPercents)
     *
     * @param int           $bomId
     * @param datetime|null $date     - към коя дата са цените (null - към сега)
     * @param array         $statuses - ['error'] и ['warning'] (@see calcAutoPercents)
     *
     * @return array - обекти, ключирани по ид на ред от детайла, с полета productId,
     *                 quantity, costPercent, autoPercent, percent и source
     */
    public static function getPercents($bomId, $date = null, &$statuses = array())
    {
        core_Debug::startTimer('DISASSEMBLY_GET_PERCENTS');

        $statuses = array();
        $rec = static::fetchRec($bomId);

        // Оттеглените ревизии се изключват изрично - doc_plg_DetailRevisions ги
        // пропуска в режим "Ревизии", а сметката не бива да зависи от това
        $dQuery = cat_DisassemblyBomDetails::getQuery();
        $dQuery->where("#bomId = {$rec->id} AND #type = 'production'");
        $dQuery->where("#state != 'rejected' OR #state IS NULL");
        $dQuery->show('productId,quantity,costPercent');

        $res = array();
        while ($dRec = $dQuery->fetch()) {

            // Незададеният ръчен процент се нормализира до null (0% е валидно зададен)
            $costPercent = (isset($dRec->costPercent) && $dRec->costPercent !== '') ? (float) $dRec->costPercent : null;

            $res[$dRec->id] = (object) array('productId' => $dRec->productId,
                                             'quantity' => $dRec->quantity,
                                             'costPercent' => $costPercent,
                                             'autoPercent' => null,
                                             'percent' => null,
                                             'source' => null);
        }

        if (!countR($res)) {
            $statuses['error'] = 'Не е посочен нито един произведен артикул|*!';
            core_Debug::stopTimer('DISASSEMBLY_GET_PERCENTS');

            return $res;
        }

        // Разпределянето е на едно място - и решава кой процент важи за всеки ред
        static::calcAutoPercents($res, $rec->priceListId, $date, $statuses);

        core_Debug::stopTimer('DISASSEMBLY_GET_PERCENTS');

        return $res;
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

        // Всички ли ПРОИЗВЕДЕНИ артикули са в еднаква мярка помежду си - мярката
        // на вложения артикул няма значение, защото не участва в сметката.
        // Оттеглените ревизии се изключват изрично (@see getPercents)
        $dQuery = cat_DisassemblyBomDetails::getQuery();
        $dQuery->where("#bomId = {$rec->id} AND #type = 'production'");
        $dQuery->where("#state != 'rejected' OR #state IS NULL");
        $dQuery->show('productId');
        $productIds = arr::extractValuesFromArray($dQuery->fetchAll(), 'productId');

        $rec->allProductsAreInTheSameUomId = cat_Products::areProductsInTheSameUom($productIds) ? 'yes' : 'no';

        return $this->save_($rec, 'lastUpdatedDetailOn,lastUpdatedDetailBy,allProductsAreInTheSameUomId,modifiedOn,modifiedBy,searchKeywords');
    }


    /**
     * Функция, която се извиква преди активирането на документа
     *
     * Не може да се активира рецепта, по която не може да се разпредели
     * себестойността - причината идва от единственото място, което знае кои
     * проценти важат (@see getPercents)
     */
    protected static function on_BeforeActivation($mvc, $res)
    {
        if (empty($res->id)) return;

        $statuses = array();
        static::getPercents($res->id, null, $statuses);

        // Част от артикулите не се разпределят по избраната политика - не спира
        // активирането, но потребителят задължително се уведомява
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
        $productRec = cat_Products::fetchRec($productId, 'id');
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
                <!--ET_BEGIN priceListId--><tr><td style='font-weight:normal'>|Политика за разпад|*:</td><td>[#priceListId#]</td></tr><!--ET_END priceListId-->
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

        // В сингъла заглавието се показва само ако е въведено ръчно - иначе
        // заглавният ред би повторил getRecTitle (@see cat_Boms)
        if (isset($fields['-single'])) {
            $row->title = empty($rec->title) ? null : $mvc->getVerbal($rec, 'title');

            if(!empty($rec->priceListId)){
                $row->priceListId = price_Lists::getHyperlink($rec->priceListId, true);
            } else {
                $row->priceListId = "<span class='red'>n/a</span>";
            }
        } else {
            $row->title = $mvc->getHyperlink($rec, true);
        }

        if (isset($rec->clonedFromId)) {
            $row->clonedFromId = $mvc->getLink($rec->clonedFromId, 0);
        }
    }
}