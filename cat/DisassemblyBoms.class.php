<?php


/**
 * Клас 'cat_DisassemblyBoms' - Документ за рецепта за разпад
 *
 * Съдържа един артикул за влагане (вложим и складируем) и няколко произведени
 * артикула (складируеми и нескладируеми) - виж #Tsk9167.
 *
 * Себестойността на вложения артикул се разпределя между произведените по
 * процент на ред. Ръчно зададеният процент винаги бие автоматично изчисления,
 * а сборът на важащите трябва да е точно 100% (@see getPercents).
 *
 * Автоматичният процент се определя от избраната ценова политика ($priceListId),
 * а не от мерките - количеството и мярката на вложения не участват в сметката:
 * - има избрана политика - по стойността на редовете (к-во х цена по нея),
 *   независимо от мерките на произведените, защото така е по-прецизно;
 * - няма избрана политика - потребителят е избрал да не се работи с цени и
 *   разпределението е пропорционално на количествата, което пък изисква всички
 *   произведени да са в еднаква мярка (@see $allProductsAreInTheSameUomId).
 *
 * Разпределянето по количества при ИЗБРАНА политика е само резервен вариант,
 * когато в нея липсват цени - и то задължително с предупреждение към
 * потребителя, защото по количества скъп и евтин артикул в една мярка получават
 * еднакъв дял от себестойността.
 *
 * Процентите не се "заковават" в рецептата - смятат се на живо по актуалните
 * цени (@see calcAutoPercents), за да ги ползва после и Протоколът за разпад.
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
    public $loadList = 'plg_RowTools2, cat_Wrapper, doc_DocumentPlg, plg_Printing, doc_plg_Close, doc_plg_Prototype, acc_plg_DocumentSummary, doc_ActivatePlg, doc_plg_SingleActiveDoc, plg_Clone, cat_plg_AddSearchKeywords, plg_Search, plg_Sorting, change_Plugin';


    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'title,expenses,notes';


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
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('title', 'varchar(124,nullIfEmpty)', 'caption=Заглавие,tdClass=nameCell');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,input,silent,mandatory,input=hidden');
        $this->FLD('quantity', 'double(smartRound,Min=0)', 'caption=За,silent,mandatory');
        $this->FLD('expenses', 'percent(min=0)', 'caption=Реж. разходи,changeable');

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
     * Формулата е една и съща - "тежестта" на реда спрямо сбора на тежестите;
     * различава се само как се смята тежестта, според това с какви данни
     * разполагаме. Кое от двете важи решава ЕДИНСТВЕНО дали е подадена ценова
     * политика, а не мерките на артикулите:
     * - има политика - стойността на реда (к-во х цена по нея). Мерките са без
     *   значение, а претеглянето по стойност е по-прецизно, защото в една и съща
     *   мярка може да има и скъп, и евтин артикул;
     * - няма политика - потребителят е избрал да не се работи с цени, затова
     *   тежестта е количеството, приведено към основната мярка. Това има смисъл
     *   само ако всички произведени са в еднаква (или производна) мярка.
     *
     * Липсват ли цени в избраната политика, разпределянето по количества е само
     * резервен вариант и то ако мерките са еднакви - тогава излиза предупреждение,
     * а при различни мерки резервен вариант няма и излиза грешка.
     *
     * Количеството на вложения артикул не участва във формулата, затова и
     * неговата мярка е без значение.
     *
     * Процентите не се записват - смятат се на живо по актуалните цени, за да
     * ползва Протоколът за разпад актуалния към момента % от рецептата.
     *
     * Смята се ред по ред - един и същ артикул може да е на няколко реда и всеки
     * ред си получава процент за своето количество.
     *
     * @param array         $productsArr - масив от обекти с productId и quantity;
     *                                     едно и също productId може да се повтаря
     * @param int|null      $priceListId  - ид на ценова политика
     * @param datetime|null $date         - към коя дата са цените (null - към сега)
     * @param array         $statuses     - какво има да се каже за изчислението,
     *                                      ключирано по вид съобщение:
     *                                      ['error']   - защо не е могло да се
     *                                                    изчисли;
     *                                      ['warning'] - изчислено е, но не по
     *                                                    стойност, а по количества,
     *                                                    защото липсват цени.
     *                                      Ключът липсва, ако няма такова
     *
     * @return array $productsArr - същите обекти с попълнено поле autoPercent.
     *                              Или всички редове получават процент, или нито
     *                              един - без общия сбор няма как да се смята
     */
    public static function calcAutoPercents($productsArr, $priceListId, $date = null, &$statuses = array())
    {
        $statuses = array();
        $productIds = $weightArr = array();
        foreach ($productsArr as $obj) {
            $obj->autoPercent = null;
            $productIds[$obj->productId] = $obj->productId;
        }

        if (!countR($productIds)) return $productsArr;

        // Мерките идват от проверката, за да не се фечват наново за всеки ред
        $measureArr = array();
        $sameUom = cat_Products::areProductsInTheSameUom($productIds, $measureArr);

        // Избрана политика - работи се по стойност, независимо от мерките
        $byAmount = !empty($priceListId);
        $missing = array();
        if ($byAmount) {
            foreach ($productsArr as $k => $obj) {
                $amount = static::getAmount($priceListId, $obj->productId, $obj->quantity, $date);

                // Липсва цена - изреждат се всички виновници, а не само първият
                if (!isset($amount)) {
                    $missing[$obj->productId] = cat_Products::getTitleById($obj->productId);
                    continue;
                }

                $weightArr[$k] = $amount;
            }
        }

        // Липсват цени в избраната политика - приема се, че потребителят иска да
        // работи с нея, но не я е попълнил правилно, затова задължително му се
        // казва. При различни мерки резервен вариант няма
        if (countR($missing)) {
            $listTitle = price_Lists::getTitleById($priceListId);
            $msg = 'За разпределянето на себестойността на произведените артикули е избрана ценова политика|* "' . $listTitle . '", |но в нея липсват цени за артикул|*: <b>' . implode(', ', $missing) . '</b>!';

            if (!$sameUom) {
                $statuses['error'] = $msg;

                return $productsArr;
            }

            $statuses['warning'] = $msg . ' |Себестойността ще бъде разпределена пропорционално на количествата|*!';
            $byAmount = false;
            $weightArr = array();
        }

        // Без политика (или като резервен вариант към нея) - по количества,
        // което изисква всички произведени да са в еднаква мярка
        if (!$byAmount) {
            if (!$sameUom) {
                $statuses['error'] = 'Произведените артикули са в различни мерки - изберете ценова политика за разпад|*!';

                return $productsArr;
            }

            foreach ($productsArr as $k => $obj) {
                $weightArr[$k] = cat_UoM::convertToBaseUnit($obj->quantity, $measureArr[$obj->productId]);
            }
        }

        $total = array_sum($weightArr);
        if (empty($total)) {
            $statuses['error'] = $byAmount ? 'Стойността на произведените артикули по избраната ценова политика е нулева|*!' : 'Количествата на произведените артикули са нулеви|*!';

            return $productsArr;
        }

        foreach ($productsArr as $k => $obj) {
            $obj->autoPercent = $weightArr[$k] / $total;
        }

        return $productsArr;
    }


    /**
     * Процентите от себестойността на вложения артикул, които се падат на всеки
     * от произведените редове на рецептата
     *
     * Това е ЕДИНСТВЕНОТО място, което решава кой процент важи - ръчно зададеният
     * или автоматично изчисленият. Ползва се и от активирането, и от показването
     * на детайла, а по-нататък и от Протокола за разпад, за да не се разминат.
     *
     * Правилото е едно: ръчно зададеният `costPercent` винаги бие автоматичния,
     * а редовете без ръчен ползват автоматичния. Сборът на важащите проценти
     * трябва да е точно 100% - иначе себестойността не се разпределя цялата.
     * Оттук следва и че рецепта с ръчни проценти на ВСИЧКИ редове не се нуждае
     * от ценова политика, дори произведените артикули да са в различни мерки.
     *
     * @param int           $bomId
     * @param datetime|null $date     - към коя дата са цените (null - към сега)
     * @param array         $statuses - какво има да се каже за процентите,
     *                                  ключирано по вид съобщение:
     *                                  ['error']       - защо важащите проценти
     *                                                    не са валидни (само този
     *                                                    ключ спира активирането);
     *                                  ['autoError']   - защо не са се изчислили
     *                                                    автоматичните (те се
     *                                                    смятат винаги, за
     *                                                    показване в детайла);
     *                                  ['autoWarning'] - изчислени са, но не по
     *                                                    стойност, а по количества
     *                                                    (@see calcAutoPercents).
     *                                  Ключът липсва, ако няма такова съобщение
     *
     * @return array - масив от обекти, ключирани по ид на ред от детайла (а не
     *                 по артикул - един артикул може да е на няколко реда, всеки
     *                 със своето количество и свой ръчен процент), с полета
     *                 productId, quantity, costPercent, autoPercent и percent.
     *                 Изчисленото се връща винаги - `percent` е null само на ред,
     *                 на който не може да се определи, а дали процентите са годни
     *                 за ползване казва $statuses['error']
     */
    public static function getPercents($bomId, $date = null, &$statuses = array())
    {
        $statuses = array();
        $rec = static::fetchRec($bomId);

        $dQuery = cat_DisassemblyBomDetails::getQuery();
        $dQuery->where("#bomId = {$rec->id} AND #type = 'production'");
        $dQuery->show('productId,quantity,costPercent');

        $res = array();
        while ($dRec = $dQuery->fetch()) {

            // Незададеният ръчен процент се нормализира до null, за да е една и
            // съща проверката навсякъде надолу (0% е валидно зададен процент)
            $costPercent = (isset($dRec->costPercent) && $dRec->costPercent !== '') ? (float) $dRec->costPercent : null;

            $res[$dRec->id] = (object) array('productId' => $dRec->productId,
                                             'quantity' => $dRec->quantity,
                                             'costPercent' => $costPercent,
                                             'autoPercent' => null,
                                             'percent' => null);
        }

        if (!countR($res)) {
            $statuses['error'] = 'Не е посочен нито един произведен артикул|*!';

            return $res;
        }

        // Автоматичните се смятат винаги - показват се в детайла, независимо
        // дали важат. Съобщенията от изчислението се преименуват на 'auto...',
        // за да не се бъркат с тези за важащите проценти
        $autoStatuses = array();
        static::calcAutoPercents($res, $rec->priceListId, $date, $autoStatuses);
        foreach ($autoStatuses as $type => $msg) {
            $statuses['auto' . ucfirst($type)] = $msg;
        }

        // Ръчният процент бие автоматичния, а без ръчен важи автоматичният.
        // Гледа се ред по ред - един и същ артикул може да е на няколко реда,
        // всеки със своето количество и свой ръчен процент
        $percentSum = 0;
        $hasUnknown = $usesAuto = false;
        foreach ($res as $obj) {
            $obj->percent = $obj->costPercent ?? $obj->autoPercent;
            if (!isset($obj->costPercent)) {
                $usesAuto = true;
            }

            // Ред без ръчен процент, на който и автоматичният не се е изчислил
            if (!isset($obj->percent)) {
                $hasUnknown = true;
                continue;
            }

            $percentSum += $obj->percent;
        }

        // На всички редове е зададен ръчен процент - тогава автоматичните не
        // важат никъде и няма за какво да се предупреждава
        if (!$usesAuto) {
            unset($statuses['autoWarning']);
        }

        // Изчисленото се връща каквото е - грешката казва дали е годно за ползване
        if ($hasUnknown) {
            $statuses['error'] = $statuses['autoError'] ?? 'Процентите от себестойността не могат да се изчислят|*!';
        } elseif (abs($percentSum - 1) > 0.0001) {
            $percentVerbal = core_Type::getByName('percent')->toVerbal($percentSum);
            $statuses['error'] = "Сумата на процентите от себестойността трябва да е 100%|*, |а е|*: {$percentVerbal}";
        }

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
        // на вложения артикул няма значение, защото не участва в сметката
        $dQuery = cat_DisassemblyBomDetails::getQuery();
        $dQuery->where("#bomId = {$rec->id} AND #type = 'production'");
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

        // Разпределението не е по стойност, а по количества - не спира
        // активирането, но потребителят задължително се уведомява
        if (isset($statuses['autoWarning'])) {
            core_Statuses::newStatus($statuses['autoWarning'], 'warning');
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
                </table>"));

        $resArr['info'] = array('name' => tr('Информация'), 'val' => tr("|*<table class='docHeaderVal'>
                <tr><td style='font-weight:normal'>|Модифициранe|*:</td><td>[#modifiedOn#]</b> |от|* [#modifiedBy#]</td></tr>
                <!--ET_BEGIN lastUpdatedDetailOn--><tr><td style='font-weight:normal'>|Промяна на детайл|*:</td><td>[#lastUpdatedDetailOn#]</td></tr><!--ET_END lastUpdatedDetailOn-->
                <!--ET_BEGIN clonedFromId--><tr><td style='font-weight:normal'>|Клонирано от|*:</td><td>[#clonedFromId#]</td></tr><!--ET_END clonedFromId-->
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

        // Полето се добавя от plg_Clone без 'select', затова му правим линк ръчно
        if (isset($rec->clonedFromId)) {
            $row->clonedFromId = $mvc->getLink($rec->clonedFromId, 0);
        }
    }
}