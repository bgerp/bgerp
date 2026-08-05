<?php


/**
 * Клас 'cat_DisassemblyBoms' - Документ за рецепта за разпад
 *
 * Съдържа един артикул за влагане (вложим и складируем) и няколко произведени
 * артикула (складируеми и нескладируеми) - виж #Tsk9167. Себестойността на
 * произведените артикули се изчислява живо (@see getProductionCosts) по
 * правилата, обсъдени в нишката на задачата:
 * - ако всички произведени артикули са в еднаква (или производна) мярка -
 *   пропорционално на количествата им;
 * - иначе, ако всички имат зададен ръчно % и сборът им е точно 100% -
 *   по зададения %;
 * - иначе, ако всички имат цена по избраната ценова политика ($priceListId) -
 *   пропорционално на стойността им (к-во х цена);
 * - иначе рецептата не може да се активира.
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
    public $loadList = 'plg_RowTools2, cat_Wrapper, doc_DocumentPlg, plg_Printing, doc_plg_Close, doc_ActivatePlg, doc_plg_SingleActiveDoc, plg_Clone, cat_plg_AddSearchKeywords, plg_Search, plg_Sorting, change_Plugin';


    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'title,expenses,priceListId,notes';


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
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsSingleField = 'title';


    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/protocol_decay.png';


    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'cat/tpl/SingleLayoutDisassemblyBom.shtml';


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
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn,modifiedOn';


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
        $this->FLD('state', 'enum(draft=Чернова,active=Активирана,rejected=Оттеглена,closed=Затворена)', 'caption=Статус,input=none');
        $this->FLD('notes', 'richtext(rows=4,bucket=Notes)', 'caption=Допълнително->Забележки');

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
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($action == 'add' && isset($rec)) {

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
    }


    /**
     * Функция, която се извиква преди активирането на документа
     */
    protected static function on_BeforeActivation($mvc, $res)
    {
        if (empty($res->id)) return;

        $allocation = static::calcAllocation($res->id);
        if (!$allocation->ok) {
            core_Statuses::newStatus($allocation->error, 'warning');

            return false;
        }
    }


    /**
     * Пропорционално разпределя $totalValue между записите на $recs, по тегло,
     * върнато от $weightFn за всеки от тях. Remainder-safe - последният ред
     * поема остатъка от закръгляне (@see planning_transaction_DisassemblyNote)
     *
     * @param stdClass[] $recs
     * @param float      $totalValue
     * @param callable   $weightFn
     *
     * @return array $id => float
     */
    private static function allocateByRatio($recs, $totalValue, $weightFn)
    {
        $rows = array();
        $totalWeight = 0;
        foreach ($recs as $dRec) {
            $totalWeight += $weightFn($dRec);
        }

        if (!$totalWeight) {
            foreach ($recs as $dRec) {
                $rows[$dRec->id] = 0.0;
            }

            return $rows;
        }

        $allocated = 0;
        $count = countR($recs);
        $i = 0;
        foreach ($recs as $dRec) {
            $i++;
            if ($i == $count) {
                $rows[$dRec->id] = round($totalValue - $allocated, 2);
            } else {
                $rows[$dRec->id] = round($totalValue * $weightFn($dRec) / $totalWeight, 2);
            }
            $allocated += $rows[$dRec->id];
        }

        return $rows;
    }


    /**
     * Изчислява живо разпределението на себестойността на вложения артикул
     * между произведените артикули, по правилата от нишката на #Tsk9167
     *
     * @param int $id
     *
     * @return stdClass ->ok bool, ->mode string|null (quantity|percent|value),
     *                  ->error string|null, ->rows array [detailId => float]
     */
    public static function calcAllocation($id)
    {
        $res = (object) array('ok' => false, 'mode' => null, 'error' => null, 'rows' => array());

        $rec = static::fetchRec($id);
        if (empty($rec->productId) || empty($rec->quantity)) {
            $res->error = 'Не е зададено количество на артикула за разпад';

            return $res;
        }

        $prodQuery = cat_DisassemblyBomDetails::getQuery();
        $prodQuery->where("#bomId = {$rec->id} AND #type = 'production' AND #quantity != 0");
        $prodQuery->orderBy('id', 'ASC');
        $prodRecs = $prodQuery->fetchAll();

        if (!countR($prodRecs)) {
            $res->error = 'Не е посочен нито един произведен артикул';

            return $res;
        }

        // Стойността на вложеното - засега само основният артикул от мастъра;
        // когато се разрешат допълнителни артикули за влагане в детайла,
        // тяхната стойност трябва да се натрупа тук
        $unitPrice = cat_Products::getWacAmountInStore(1, $rec->productId, dt::now());
        $totalValue = ((float) $unitPrice) * $rec->quantity;

        // Режийните разходи оскъпяват вложеното и се разпределят заедно с него,
        // както при контирането на Протокола за разпад
        // (@see planning_transaction_DisassemblyNote)
        if (!empty($rec->expenses)) {
            $totalValue = $totalValue * (1 + $rec->expenses);
        }

        $totalValue = round($totalValue, 4);

        // 1) Ако всички произведени артикули са в еднаква (или производна) мярка -
        // разпределяме пропорционално на количествата им (мярката на вложения е без значение)
        $baseMeasureId = null;
        $sameUom = true;
        foreach ($prodRecs as $dRec) {
            $measureId = cat_Products::fetchField($dRec->productId, 'measureId');
            if ($baseMeasureId === null) {
                $baseMeasureId = $measureId;
                continue;
            }
            if ($measureId != $baseMeasureId && !cat_Products::convertToUom($dRec->productId, $baseMeasureId)) {
                $sameUom = false;
                break;
            }
        }

        if ($sameUom) {
            $res->mode = 'quantity';
            $res->rows = static::allocateByRatio($prodRecs, $totalValue, function ($dRec) {
                return $dRec->quantity;
            });
            $res->ok = true;

            return $res;
        }

        // 2) Иначе, ако всички имат ръчно зададен % и сборът им е точно 100% - по %
        $allHavePercent = true;
        $percentSum = 0.0;
        foreach ($prodRecs as $dRec) {
            if (!strlen((string) ($dRec->costPercent ?? ''))) {
                $allHavePercent = false;
                break;
            }
            $percentSum += (float) $dRec->costPercent;
        }

        if ($allHavePercent && abs($percentSum - 1) < 0.0001) {
            $res->mode = 'percent';
            $res->rows = static::allocateByRatio($prodRecs, $totalValue, function ($dRec) {
                return (float) $dRec->costPercent;
            });
            $res->ok = true;

            return $res;
        }

        // 3) Иначе, ако всички имат цена по избраната ценова политика - по стойност (к-во х цена)
        $missingNames = array();
        $values = array();
        $date = dt::now();
        foreach ($prodRecs as $dRec) {
            $price = (!empty($rec->priceListId)) ? price_ListRules::getPrice($rec->priceListId, $dRec->productId, null, $date) : null;
            if (!isset($price)) {
                $missingNames[] = cat_Products::fetch($dRec->productId, 'name')->name;
            } else {
                $values[$dRec->id] = $price * $dRec->quantity;
            }
        }

        if (countR($missingNames)) {
            $missingMsg = (empty($rec->priceListId)) ? 'не е избрана Ценова политика за разпад|*' : ('следните нямат цена по избраната ценова политика|*: <b>' . implode(', ', $missingNames) . '</b>');
            $res->error = "Произведените артикули са в различни мерки, без зададени % за себестойност, и {$missingMsg}";

            return $res;
        }

        $res->mode = 'value';
        $res->rows = static::allocateByRatio($prodRecs, $totalValue, function ($dRec) use ($values) {
            return $values[$dRec->id];
        });
        $res->ok = true;

        return $res;
    }


    /**
     * Живо изчислена себестойност на произведените артикули - за показване в детайла
     *
     * @param int $id
     *
     * @return array [detailId => float]|null
     */
    public static function getProductionCosts($id)
    {
        $allocation = static::calcAllocation($id);

        return ($allocation->ok) ? $allocation->rows : null;
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
        if (!is_object($productRec)) return false;

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

        $row->title = $mvc->getHyperlink($rec, true);
    }
}