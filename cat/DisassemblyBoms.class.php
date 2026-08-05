<?php


/**
 * Клас 'cat_DisassemblyBoms' - Документ за рецепта за разпад
 *
 * Съдържа един артикул за влагане (вложим и складируем) и няколко произведени
 * артикула (складируеми и нескладируеми) - виж #Tsk9167.
 *
 * Как се разпределя себестойността на вложения артикул зависи от мерките на
 * произведените (@see $allProductsAreInTheSameUomId, updateMaster_):
 * - еднаква (или производна) мярка - по ръчно зададените проценти, които при
 *   активиране трябва да правят точно 100%; цената не участва;
 * - различни мерки - по стойността на артикулите (к-во х цена) по избраната
 *   ценова политика ($priceListId), затова при активиране се изисква и
 *   вложеният, и всички произведени артикули да имат цена по нея.
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
        $this->FLD('allProductsAreInTheSameUomId', 'enum(yes=Да,no=Не)', 'caption=Всички артикули са в еднаква мярка,input=none,notNull,value=yes');
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

        $productInfo = cat_Products::getProductInfo($rec->productId);
        $shortUom = cat_UoM::getShortName($productInfo->productRec->measureId);
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
     * @param int|null $priceListId
     * @param int      $productId
     * @param float    $quantity
     *
     * @return float|null - null, ако няма политика или цена по нея
     */
    public static function getAmount($priceListId, $productId, $quantity)
    {
        if (empty($priceListId)) return null;

        $price = price_ListRules::getPrice($priceListId, $productId, null, dt::now());
        if (!isset($price)) return null;

        return $price * $quantity;
    }


    /**
     * Сумата във вербален вид - в синьо, а при липсваща цена червено '???'
     *
     * @param int|null $priceListId
     * @param int      $productId
     * @param float    $quantity
     *
     * @return string
     */
    public static function getAmountVerbal($priceListId, $productId, $quantity)
    {
        $amount = static::getAmount($priceListId, $productId, $quantity);
        if (!isset($amount)) {
            $hint = empty($priceListId) ? 'Не е избрана ценова политика за разпад' : 'Артикулът няма цена по избраната ценова политика';

            return ht::createHint("<span class='red'>???</span>", $hint, 'warning', false);
        }

        $amountVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($amount);

        return "<span style='color:blue'>{$amountVerbal}</span>";
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

        // Всички ли артикули от детайла са в мярка, производна на тази на артикула
        // за разпад - напр. при основна мярка килограм минават кг/тон/грам, но не и брой
        $rec->allProductsAreInTheSameUomId = 'yes';
        $measureId = cat_Products::fetchField($rec->productId, 'measureId');
        if (!empty($measureId)) {
            $sameTypeMeasures = cat_UoM::getSameTypeMeasures($measureId);

            $dQuery = cat_DisassemblyBomDetails::getQuery();
            $dQuery->EXT('measureId', 'cat_Products', 'externalName=measureId,externalKey=productId');
            $dQuery->where("#bomId = {$rec->id}");
            $dQuery->show('measureId');
            while ($dRec = $dQuery->fetch()) {
                if (!array_key_exists($dRec->measureId, $sameTypeMeasures)) {
                    $rec->allProductsAreInTheSameUomId = 'no';
                    break;
                }
            }
        }

        return $this->save_($rec, 'lastUpdatedDetailOn,lastUpdatedDetailBy,allProductsAreInTheSameUomId,modifiedOn,modifiedBy,searchKeywords');
    }


    /**
     * Функция, която се извиква преди активирането на документа
     */
    protected static function on_BeforeActivation($mvc, $res)
    {
        if (empty($res->id)) return;

        $rec = $mvc->fetchRec($res->id);

        $dQuery = cat_DisassemblyBomDetails::getQuery();
        $dQuery->where("#bomId = {$rec->id} AND #type = 'production'");
        $prodRecs = $dQuery->fetchAll();

        if (!countR($prodRecs)) {
            core_Statuses::newStatus('Не е посочен нито един произведен артикул', 'error');

            return false;
        }

        if ($rec->allProductsAreInTheSameUomId == 'yes') {

            // Еднакви мерки - себестойността се разпределя по ръчно зададените
            // проценти, затова те трябва да покриват точно 100%
            $percentSum = 0;
            foreach ($prodRecs as $dRec) {
                $percentSum += (float) $dRec->costPercent;
            }

            if (abs($percentSum - 1) > 0.0001) {
                $percentVerbal = core_Type::getByName('percent')->toVerbal($percentSum);
                core_Statuses::newStatus("Сумата на процентите от себестойността трябва да е 100%|*, |а е|*: {$percentVerbal}", 'error');

                return false;
            }
        } else {

            // Различни мерки - разпределя се по стойност, значи и вложеният, и
            // всички произведени артикули трябва да имат цена по избраната ЦП
            $missing = array();
            if (static::getAmount($rec->priceListId, $rec->productId, $rec->quantity) === null) {
                $missing[] = cat_Products::getTitleById($rec->productId);
            }

            foreach ($prodRecs as $dRec) {
                if (static::getAmount($rec->priceListId, $dRec->productId, $dRec->quantity) === null) {
                    $missing[] = cat_Products::getTitleById($dRec->productId);
                }
            }

            if (countR($missing)) {
                $msg = empty($rec->priceListId) ? 'Артикулите са в различни мерки - изберете ценова политика за разпад' : ('Артикулите са в различни мерки, а следните нямат цена по избраната ценова политика|*: <b>' . implode(', ', $missing) . '</b>');
                core_Statuses::newStatus($msg, 'error');

                return false;
            }
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
                <tr><td style='font-weight:normal'>|Еднаква мярка|*:</td><td>[#allProductsAreInTheSameUomId#]</td></tr>
                <!--ET_BEGIN amount--><tr><td style='font-weight:normal'>|Сума|*:</td><td>[#amount#]</td></tr><!--ET_END amount-->
                </table>"));

        $resArr['info'] = array('name' => tr('Информация'), 'val' => tr("|*<table class='docHeaderVal'>
                <tr><td style='font-weight:normal'>|Модифициранe|*:</td><td>[#modifiedOn#]</b> |от|* [#modifiedBy#]</td></tr>
                <!--ET_BEGIN lastUpdatedDetailOn--><tr><td style='font-weight:normal'>|Промяна на детайл|*:</td><td>[#lastUpdatedDetailOn#]</td></tr><!--ET_END lastUpdatedDetailOn-->
                <!--ET_BEGIN clonedFromId--><tr><td style='font-weight:normal'>|Клонирано от|*:</td><td>[#clonedFromId#]</td></tr><!--ET_END clonedFromId-->
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

        // Сумата на вложения артикул по ЦП - показва се само при различни мерки,
        // защото само тогава по нея се разпределя себестойността
        if ($rec->allProductsAreInTheSameUomId == 'no') {
            $row->amount = static::getAmountVerbal($rec->priceListId, $rec->productId, $rec->quantity);
        }

        // Полето се добавя от plg_Clone без 'select', затова му правим линк ръчно
        if (isset($rec->clonedFromId)) {
            $row->clonedFromId = $mvc->getLink($rec->clonedFromId, 0);
        }
    }
}