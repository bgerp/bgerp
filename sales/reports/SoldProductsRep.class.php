<?php


/**
 * Мениджър на отчети за продадени артикули продукти по групи и търговци
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Продажби » Продадени артикули
 */
class sales_reports_SoldProductsRep extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, acc, repAll, repAllGlobal, sales';


//     /**
//      * Кои полета от таблицата в справката да се сумират в обобщаващия ред
//      *
//      * @var int
//      */
//     protected $summaryListFields = 'invAmount,primeCost,delta,primeCostCompare,deltaCompare,changeSales,changeDeltas,';
    protected $summaryListFields = 'invAmount';


    /**
     * Как да се казва обобщаващия ред. За да се покаже трябва да е зададено $summaryListFields
     *
     * @var int
     */
    protected $summaryRowCaption = 'ОБЩО ЗА ПЕРИОДА';


    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck;


    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'from,to,compare,firstMonth,secondMonth,group,dealers,contragent,crmGroup,articleType,seeDelta,orderBy,order,grouping,updateDays,updateTime,products';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('compare', 'enum(no=Без, previous=Предходен,month=По месеци, year=Миналогодишен)', 'caption=Сравнение,after=title,refreshForm,single=none,silent');

        $fieldset->FLD('from', 'date', 'caption=От,after=compare,single=none,removeAndRefreshForm,silent');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,removeAndRefreshForm,silent');

        $fieldset->FLD('firstMonth', 'key(mvc=acc_Periods,select=title)', 'caption=Месец 1,after=compare,removeAndRefreshForm,single=none,input=none,silent');
        $fieldset->FLD('secondMonth', 'key(mvc=acc_Periods,select=title)', 'caption=Месец 2,after=firstMonth,removeAndRefreshForm,single=none,input=none,silent');

        $fieldset->FLD('dealers', 'users(rolesForAll=ceo|repAllGlobal, rolesForTeams=ceo|manager|repAll|repAllGlobal)', 'caption=Търговци,single=none,input=none,after=to,silent,mandatory');

        $fieldset->FLD('contragent', 'keylist(mvc=doc_Folders,select=title,allowEmpty)', 'caption=Контрагенти->Контрагент,placeholder=Всички,single=none,after=dealers');
        $fieldset->FLD('crmGroup', 'keylist(mvc=crm_Groups,select=name)', 'caption=Контрагенти->Група контрагенти,placeholder=Всички,after=contragent,single=none');

        $fieldset->FLD('typeOfGroups', 'enum(no=Без групи, category=Категории артикули, art=Групи артикули)', 'caption=Артикули->Групи,removeAndRefreshForm,after=crmGroup');
        $fieldset->FLD('category', 'keylist(mvc=cat_Categories,select=name)', 'caption=Артикули->Категории артикули,after=typeOfGroups,removeAndRefreshForm,placeholder=Всички,silent,single=none');
        $fieldset->FLD('group', 'keylist(mvc=cat_Groups,select=name)', 'caption=Артикули->Групи артикули,after=category,removeAndRefreshForm,placeholder=Всички,silent,single=none');
        $fieldset->FLD('products', 'keylist(mvc=cat_Products,select=name)', 'caption=Артикули->Артикули,placeholder=Всички,after=group,single=none,input=none,class=w100');
        $fieldset->FLD('articleType', 'enum(yes=Стандартни,no=Нестандартни,all=Всички)', 'caption=Артикули->Тип артикули,maxRadio=3,columns=3,after=productId,single=none');
        $fieldset->FLD('quantityType', 'enum(shipped=Експедирани, ordered=Поръчани,invoiced=Фактурирано)', 'caption=Артикули->Количества,removeAndRefreshForm,silent,after=articleType');

        //Покаване на резултата
        $fieldset->FLD('grouping', 'enum(yes=По групи, no=По артикули)', 'caption=Показване->Вид,removeAndRefreshForm,after=quantityType');
        $fieldset->FLD('currency', 'key(mvc=currency_Currencies,select=code,allowEmpty)', 'caption=Показване->Валута,removeAndRefreshForm,single=none,after=grouping,placeholder=Основна');
        $fieldset->FLD('seeByContragent', 'enum(yes=ДА, no=НЕ)', 'caption=Показване->Разбивка по контрагенти,after=currency,removeAndRefreshForm,single=none,silent');
        $fieldset->FLD('seeCategory', 'enum(yes=ДА, no=НЕ)', 'caption=Показване->Покажи категория,after=seeByContragent,single=none,silent');

        $fieldset->FLD('engName', 'enum(yes=ДА, no=НЕ)', 'caption=Показване->Име EN,after=seeByContragent,single=none');
        $fieldset->FLD('seeDelta', 'enum(yes=ДА, no=НЕ)', 'caption=Показване->Покажи делти,after=engName,single=none');
        $fieldset->FLD('seeWeight', 'enum(yes=ДА, no=НЕ)', 'caption=Показване->Покажи тегло,after=seeDelta,single=none');


        //Подредба на резултатите
        $fieldset->FLD('orderBy', 'enum(code=Код, primeCost=Продажби, delta=Делти, changeDelta=Промяна Делти, changeCost=Промяна Стойност)', 'caption=Подреждане на резултата->Показател,maxRadio=5,columns=3,after=seeWeight');
        $fieldset->FLD('order', 'enum(desc=Низходящо, asc=Възходящо)', 'caption=Подреждане на резултата->Ред,maxRadio=2,after=orderBy,single=none');
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_Form $form
     * @param stdClass $data
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted()) {

            if (($form->rec->compare != 'month') && (!($form->rec->from && $form->rec->to))) {
                $form->setError('from,to,selectPeriod', 'Изберете период.');
            }

            // Проверка на периоди
            if (isset($form->rec->from, $form->rec->to) && ($form->rec->from > $form->rec->to)) {
                $form->setError('from,to', 'Началната дата на периода не може да бъде по-голяма от крайната.');
            }


            if (isset($form->rec->compare) && $form->rec->compare == 'year') {
                $toLastYear = dt::addDays(-365, $form->rec->to);
                if ($form->rec->from < $toLastYear) {
                    $form->setError('compare', 'Периода трябва да е по-малък от 365 дни за да сравнявате с "миналогодишен" период.
                                                  За да сравнявате периоди по-големи от 1 година, използвайте сравнение с "предходен" период');
                }
            }

            //Проверка за правилна подредба
            if (($form->rec->orderBy == 'code') && ($form->rec->grouping == 'yes')) {
                $form->setError('orderBy', 'При ГРУПИРАНО показване не може да има подредба по КОД.');
            }

            if (($form->rec->compare == 'no') && (($form->rec->orderBy == 'changeCost') || ($form->rec->orderBy == 'changeDelta'))) {
                $form->setError('orderBy,compare', 'Не е посочен период за сравнение. Няма промяна.');
            }


            if (($form->rec->seeByContragent == 'yes') && (($form->rec->grouping == 'yes'))) {
                $form->setError('grouping', 'Когато е избрана разбивка по контрагент, полето ГРУПИРАНЕ трябва да бъде ПО АРТИКУЛИ');
            }

            if (($form->rec->seeByContragent == 'yes') && (($form->rec->compare != 'no'))) {
                $form->setError('compare', 'Когато е избрана разбивка по контрагент, трябва да бъде без сравнение');
            }

            if (($form->rec->products) && (($form->rec->group) || ($form->rec->category))) {
                $form->setError('products,group,category', 'Не може едновременно да бъдат включени и двата филтъра "Артикул" и "Групи"');
            }

            if ($form->rec->typeOfGroups == 'no' && !$form->rec->products) {
                $form->setError('products,group,category', 'Трябва да има или групи или артикул');
            }
        }
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        $suggestions = $prodSuggestions = $prodSalesArr = $posProdsArr = $prodArr = array();

        if ($rec->compare == 'month') {
            $form->setField('from', 'input=hidden');
            $form->setField('to', 'input=hidden');
            $form->setField('selectPeriod', 'input=hidden');

            $form->setField('firstMonth', 'input');
            $form->setField('secondMonth', 'input');
        }

        if ($rec->compare != 'no') {
            $form->setField('seeWeight', 'input=hidden');
        }
        $form->input('typeOfGroups');
        if ($rec->typeOfGroups == 'category') {
            $form->setField('group', 'input=hidden');
        } elseif ($rec->typeOfGroups == 'art') {
            $form->setField('category', 'input=hidden');
        } elseif ($rec->typeOfGroups == 'no') {
            $form->setField('category', 'input=hidden');
            $form->setField('group', 'input=hidden');
        }


        $today = dt::today();
        $from = dt::addMonths(-1, $today);
        $form->setDefault('from', $from);
        $form->setDefault('to', $today);

        $periodStart = $rec->from;
        $periodEnd = $rec->to;

        $monthSugg = (acc_Periods::fetchByDate(dt::today())->id);

        $form->setDefault('firstMonth', $monthSugg);
        $form->setDefault('secondMonth', $monthSugg);


        if ($rec->compare == 'month') {
            $periodStart = acc_Periods::fetch($rec->firstMonth)->start;
            $periodEnd = acc_Periods::fetch($rec->secondMonth)->end;

            $periodStart1 = acc_Periods::fetch($rec->secondMonth)->start;
            $periodEnd1 = acc_Periods::fetch($rec->secondMonth)->end;
        }

        $form->setDefault('articleType', 'all');

        $form->setDefault('currency', '');

        $form->setDefault('compare', 'no');

        $form->setDefault('grouping', 'no');

        $form->setDefault('seeByContragent', 'no');

        $form->setDefault('seeCategory', 'no');

        $form->setDefault('seeGroups', 'no');

        $form->setDefault('typeOfGroups', 'art');

        $form->setDefault('engName', 'no');

        $form->setDefault('seeDelta', 'no');

        $form->setDefault('seeWeight', 'no');

        $form->setDefault('orderBy', 'primeCost');

        $form->setDefault('order', 'desc');

        $form->setDefault('quantityType', 'shipped');

        if ($rec->quantityType != 'invoiced') {

            $form->setField('dealers', 'input');
        }

        if ($rec->seeByContragent == 'yes') {
            $form->setField('products', 'input');

            //Подготовка на масива за зареждане на полето 'артикули'

            //от експедиционни
            $shipmentdetQuery = store_ShipmentOrderDetails::getQuery();

            $shipmentdetQuery->EXT('state', 'store_ShipmentOrders', 'externalName=state,externalKey=shipmentId');

            $shipmentdetQuery->EXT('valior', 'store_ShipmentOrders', 'externalName=valior,externalKey=shipmentId');

            $shipmentdetQuery->where("#valior >= '{$periodStart}' AND #valior <= '{$periodEnd}'");

            $shipmentdetQuery->where("#state != 'rejected'  AND #state != 'draft'");
            $shipmentdetQuery->show('productId');

            $prodArr = arr::extractValuesFromArray($shipmentdetQuery->fetchAll(), 'productId');

            //от бързи продажби
            $salesDetQuery = sales_SalesDetails::getQuery();

            $salesDetQuery->EXT('state', 'sales_Sales', 'externalName=state,externalKey=saleId');

            $salesDetQuery->EXT('valior', 'sales_Sales', 'externalName=valior,externalKey=saleId');

            $salesDetQuery->EXT('contoActions', 'sales_Sales', 'externalName=contoActions,externalKey=saleId');

            $salesDetQuery->where("#valior >= '{$periodStart}' AND #valior <= '{$periodEnd}'");

            $salesDetQuery->where("#state != 'rejected' AND #state != 'draft'");

            $salesDetQuery->where("#contoActions  Like '%ship%'");

            $salesDetQuery->show('productId');

            $prodSalesArr = arr::extractValuesFromArray($salesDetQuery->fetchAll(), 'productId');

            $prodArr = array_unique(array_merge($prodArr, $prodSalesArr));

            //от POS
            $posDetQuery = pos_ReceiptDetails::getQuery();

            $posDetQuery->EXT('state', 'pos_Receipts', 'externalName=state,externalKey=receiptId');

            $posDetQuery->EXT('valior', 'pos_Receipts', 'externalName=valior,externalKey=receiptId');

            $posDetQuery->where("#valior >= '{$periodStart}' AND #valior <= '{$periodEnd}'");

            $posDetQuery->where('#productId IS NOT NULL');

            $posDetStateArr = array('active', 'closed', 'waiting');

            $posDetQuery->in('state', $posDetStateArr);

            $posDetQuery->show('productId');

            $posProdsArr = arr::extractValuesFromArray($posDetQuery->fetchAll(), 'productId');

            $prodArr = array_unique(array_merge($prodArr, $posProdsArr));

            if (!empty($prodArr)) {
                foreach ($prodArr as $val) {
                    $prodSuggestions[$val] = cat_Products::getTitleById($val);
                }
            }

            asort($prodSuggestions);
        } else {
            $rec->products = null;
            $prodSuggestions = array('' => '');
        }

        $form->setSuggestions('products', $prodSuggestions);

        //Масив с предложения за избор на контрагент $suggestions[]
        $salesQuery = sales_Sales::getQuery();

        $salesQuery->EXT('folderTitle', 'doc_Folders', 'externalName=title,externalKey=folderId');

        $salesQuery->groupBy('folderId');

        $salesQuery->show('folderId, contragentId, folderTitle');

        while ($contragent = $salesQuery->fetch()) {
            if (!is_null($contragent->contragentId)) {
                $suggestions[$contragent->folderId] = $contragent->folderTitle;
            }
        }

        asort($suggestions);

        $form->setSuggestions('contragent', $suggestions);
    }


    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     */
    protected function prepareRecs($rec, &$data = null)
    {
        //Код и Id  на основната валута в края на периода
        $baseCurrency = acc_Periods::getBaseCurrencyCode($rec->to);
        $baseCurrencyId = currency_Currencies::getIdByCode($baseCurrency);

        //При групиране по кои крупи да работи: групи артикули или категории артикули
        if ($rec->typeOfGroups == 'art') {
            $checkForGruping = 'group';
        } elseif (($rec->typeOfGroups == 'category')) {
            $checkForGruping = 'category';
        } elseif (($rec->typeOfGroups == 'no')) {
            $checkForGruping = 'category';
        }

        // Да се показват ли делтите
        if (is_null($rec->seeDelta)) {
            $rec->seeDelta = 'no';
        }

        //Показването да бъде ли ГРУПИРАНО
        if (($rec->grouping == 'no') && ($rec->group || $rec->category)) {
            if ($rec->typeOfGroups == 'art') {
                $groupByField = 'group';
            } elseif (($rec->typeOfGroups == 'category')) {
                $groupByField = 'category';
            }
            $this->groupByField = $groupByField;
        }

        if ($rec->seeByContragent == 'yes') {
            $this->groupByField = 'contragent';
        }

        $recs = $invProd = array();


        //Ако има избрано разбивка "Артикули по контрагент"
        //Подготвяме масив с фактурираните артикули през избрания период
        //разбити по контрагент
        if ($rec->seeByContragent == 'yes') {

            $invDetQuery = self::getInvoicedProducts($rec);

            $invDetQuery->where("#state = 'active'");

            $invDetQuery->where(array("#date >= '[#1#]' AND #date <= '[#2#]'", $rec->from, $rec->to));

            while ($invDetRec = $invDetQuery->fetch()) {
                $invQuantity = $discount = $invAmount = 0;
                $originQuantity = $changeQuatity = 0;

                //Ключ на масива
                $id = $invDetRec->productId . ' | ' . $invDetRec->folderId;

                $invQuantity = $invDetRec->quantity * $invDetRec->quantityInPack;
                $discount = $invDetRec->price * $invQuantity * $invDetRec->discount;
                $invAmount = ($invDetRec->price * $invQuantity) - $discount;

                //Ако фактурата е дебитно или кредитно известие с промяна в артикулите
                if ($invDetRec->type == 'dc_note') {

                    $correctionArray = self::dcNoteCorrection($invDetRec);

                    if (empty($correctionArray)) {
                        continue;
                    }

                    $invQuantity = $correctionArray['quanttity'];
                    $invAmount = $correctionArray['amount'];

//                    $originId = doc_Containers::getDocument($invDetRec->originId)->that;
//                    $originDetRec = sales_InvoiceDetails::fetch("#invoiceId = ${originId} AND #productId = {$invDetRec->productId}");
//                    $originQuantity = $originDetRec->quantity * $originDetRec->quantityInPack;
//                    $changeQuatity = $invQuantity - $originQuantity;
//                    $changePrice = $invDetRec->price - $originDetRec->price;
//
//                    if ($changeQuatity == 0 && $changePrice == 0) {
//                        continue;
//                    }
//                    $invQuantity = $changeQuatity != 0 ? $changeQuatity :0;
//                    $invAmount = $changeQuatity == 0 ? $changePrice * $invDetRec->quantity * $invDetRec->quantityInPack : $invDetRec->price * $invQuantity;
//                    if ($invDetRec->discount) {
//                        $invAmount = $invAmount * (1 - $invDetRec->discount);
//                    }
                }

                // Запис в масива с фактурираните артикули $invProd
                if (!array_key_exists($id, $invProd)) {
                    $invProd[$id] = (object)array(
                        'productId' => $invDetRec->productId,
                        'invQuantity' => $invQuantity,
                        'invAmount' => $invAmount,
                    );
                } else {
                    $obj = &$invProd[$id];
                    $obj->invQuantity += $invQuantity;
                    $obj->invAmount += $invAmount;
                }
            }
        }


        if ($rec->quantityType == 'shipped') {
            $query = sales_PrimeCostByDocument::getQuery();

            //не е бърза продажба//
            $query->where('#sellCost IS NOT NULL');
        } elseif ($rec->quantityType == 'ordered') {

            //За заявени количества
            $query = sales_SalesDetails::getQuery();

            $query->EXT('state', 'sales_Sales', 'externalName=state,externalKey=saleId');

            $query->EXT('valior', 'sales_Sales', 'externalName=valior,externalKey=saleId');

            $query->EXT('dealerId', 'sales_Sales', 'externalName=dealerId,externalKey=saleId');

            $query->EXT('folderId', 'sales_Sales', 'externalName=folderId,externalKey=saleId');

            $query->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');

        } elseif ($rec->quantityType == 'invoiced') {

            $query = self::getInvoicedProducts($rec);

        }

        $query->EXT('groupMat', 'cat_Products', 'externalName=groups,externalKey=productId');

        $query->EXT('prodFolderId', 'cat_Products', 'externalName=folderId,externalKey=productId');

        $query->EXT('category', 'doc_Folders', 'externalName=coverId,externalKey=prodFolderId');

        $query->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');

        $query->in('state', array('rejected', 'stopped', 'draft'), true);

        //Когато е БЕЗ СРАВНЕНИЕ
        if (($rec->compare) == 'no') {
            $query->where("#valior >= '{$rec->from}' AND #valior <= '{$rec->to}'");
        }

        // сравнение с ПРЕДХОДЕН ПЕРИОД  или ПО МЕСЕЦИ
        if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
            if (($rec->compare == 'previous')) {
                $daysInPeriod = dt::daysBetween($rec->to, $rec->from) + 1;

                $fromPreviuos = dt::addDays(-$daysInPeriod, $rec->from, false);

                $toPreviuos = dt::addDays(-$daysInPeriod, $rec->to, false);
            }

            if (($rec->compare == 'month')) {
                $rec->from = (acc_Periods::fetch($rec->firstMonth)->start);

                $rec->to = (acc_Periods::fetch($rec->firstMonth)->end);

                $fromPreviuos = (acc_Periods::fetch($rec->secondMonth)->start);

                $toPreviuos = (acc_Periods::fetch($rec->secondMonth)->end);
            }

            $query->where("(#valior >= '{$rec->from}' AND #valior <= '{$rec->to}') OR (#valior >= '{$fromPreviuos}' AND #valior <= '{$toPreviuos}')");
        }

        // сравнение с ПРЕДХОДНА ГОДИНА
        if (($rec->compare) == 'year') {
            $fromLastYear = dt::addDays(-365, $rec->from);
            $toLastYear = dt::addDays(-365, $rec->to);

            $query->where("(#valior >= '{$rec->from}' AND #valior <= '{$rec->to}') OR (#valior >= '{$fromLastYear}' AND #valior <= '{$toLastYear}')");
        }


        //Филтър за ДИЛЪР
        if ($rec->quantityType != 'invoiced' && isset($rec->dealers)) {
            if ((min(array_keys(keylist::toArray($rec->dealers))) >= 1)) {
                $dealers = keylist::toArray($rec->dealers);

                $query->in('dealerId', $dealers);
            }
        }

        //Филтър за КОНТРАГЕНТ и ГРУПИ КОНТРАГЕНТИ
        if ($rec->contragent || $rec->crmGroup) {
            $contragentsArr = array();
            $contragentsId = array();

            if (!$rec->crmGroup && $rec->contragent) {
                $contragentsArr = keylist::toArray($rec->contragent);

                $query->in('folderId', $contragentsArr);
            }

            if ($rec->crmGroup && !$rec->contragent) {
                $foldersInGroups = self::getFoldersInGroups($rec);

                $query->in('folderId', $foldersInGroups);
            }

            if ($rec->crmGroup && $rec->contragent) {
                $foldersInGroups = self::getFoldersInGroups($rec);

                $contragentsArr = keylist::toArray($rec->contragent);

                $foldersInGroups = array_merge($foldersInGroups, $contragentsArr);

                $foldersInGroups = array_unique($foldersInGroups);

                $query->in('folderId', $foldersInGroups);
            }
        }

        //Филтър за АРТИКУЛ и ГРУПИ АРТИКУЛИ

        if ($rec->typeOfGroups == 'art') {
            $filterGroupsType = 'group';
        } elseif ($rec->typeOfGroups == 'category') {
            $filterGroupsType = 'category';
        } elseif ($rec->typeOfGroups == 'no') {
            $filterGroupsType = 'category';
        }
        $checkFieldName = ($filterGroupsType == 'group') ? 'groupMat' : 'category';

        if ($rec->products || $rec->$filterGroupsType) {
            $prodsArr = array();

            if (!$rec->$filterGroupsType && $rec->products) {
                $prodsArr = keylist::toArray($rec->products);
                $query->in('productId', $prodsArr);
            }

            if ($rec->$filterGroupsType && !$rec->products) {
                if ($filterGroupsType == 'group') {
                    $query->likeKeylist($checkFieldName, $rec->$filterGroupsType);
                } else {
                    $filterGroupsArr = keylist::toArray($rec->$filterGroupsType);
                    $query->in($checkFieldName, $filterGroupsArr);
                }
            }

            if ($rec->$filterGroupsType && $rec->products) {
                $prodsArr = keylist::toArray($rec->products);
                $query->in('productId', $prodsArr);
                $query->orLikeKeylist($checkFieldName, $rec->$filterGroupsType);
            }
        }

        //Филтър за стандартни артикули
        if ($rec->articleType != 'all') {
            $query->where("#isPublic = '{$rec->articleType}'");
        }

        // Синхронизира таймлимита с броя записи
        $rec->count = $query->count();

        $timeLimit = $query->count() * 0.05;

        if ($timeLimit >= 30) {
            core_App::setTimeLimit($timeLimit);
        }

        while ($recPrime = $query->fetch()) {
            $quantity = $primeCost = $delta = 0;
            $quantityPrevious = $primeCostPrevious = $deltaPrevious = 0;
            $quantityLastYear = $primeCostLastYear = $deltaLastYear = 0;

            if ($rec->quantityType == 'shipped') {
                $DetClass = cls::get($recPrime->detailClassId);
                $price = 'sellCost';
            } elseif ($rec->quantityType == 'ordered') {
                $DetClass = cls::get('sales_SalesDetails');
                $price = 'price';
            } elseif ($rec->quantityType == 'invoiced') {
                $DetClass = cls::get('sales_InvoiceDetails');

            }

            $categoryId = doc_Folders::fetch($recPrime->prodFolderId)->coverId;

            //Ключ на масива
            $id = ($rec->seeByContragent == 'yes') ? $recPrime->productId . ' | ' . $recPrime->folderId : $recPrime->productId;

            //Код на артикула
            $artCode = $recPrime->code ? $recPrime->code : "Art{$recPrime->productId}";

            //Мярка на артикула
            $measureArt = cat_Products::fetch($recPrime->productId)->measureId;

            //Данни за ПРЕДХОДЕН ПЕРИОД или МЕСЕЦ
            if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                if ($recPrime->valior >= $fromPreviuos && $recPrime->valior <= $toPreviuos) {

                    if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {

                        $quantityPrevious = (-1) * $recPrime->quantity;
                        $primeCostPrevious = (-1) * $recPrime->{"${price}"} * $recPrime->quantity;
                        $deltaPrevious = (-1) * $recPrime->delta;

                    } elseif ($DetClass instanceof sales_SalesDetails || $DetClass instanceof store_ShipmentOrderDetails || $DetClass instanceof pos_Reports) {
                        $quantityPrevious = $recPrime->quantity;
                        $primeCostPrevious = $recPrime->{"${price}"} * $recPrime->quantity;
                        $deltaPrevious = $recPrime->delta;

                    } elseif ($DetClass instanceof sales_InvoiceDetails) {

                        if ($recPrime->type == 'invoice') {

                            $quantityPrevious = $recPrime->quantity * $recPrime->quantityInPack;
                            $discount = $recPrime->price * $quantityPrevious * $recPrime->discount;
                            $primeCostPrevious = ($recPrime->price * $quantityPrevious) - $discount;

                        } elseif ($recPrime->type == 'dc_note') {

                            $correctionArray = self::dcNoteCorrection($recPrime);

                            if (empty($correctionArray)) {
                                continue;
                            }

                            $quantityPrevious = $correctionArray['quantity'];
                            $primeCostPrevious = $correctionArray['amount'];

                        }
                    }
                }
            }

            //Данни за ПРЕДХОДНА ГОДИНА
            if ($rec->compare == 'year') {
                if ($recPrime->valior >= $fromLastYear && $recPrime->valior <= $toLastYear) {

                    if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {

                        $quantityLastYear = (-1) * $recPrime->quantity;
                        $primeCostLastYear = (-1) * $recPrime->{"${price}"} * $recPrime->quantity;
                        $deltaLastYear = (-1) * $recPrime->delta;

                    } elseif ($DetClass instanceof sales_SalesDetails || $DetClass instanceof store_ShipmentOrderDetails || $DetClass instanceof pos_Reports) {

                        $quantityLastYear = $recPrime->quantity;
                        $primeCostLastYear = $recPrime->{"${price}"} * $recPrime->quantity;
                        $deltaLastYear = $recPrime->delta;

                    } elseif ($DetClass instanceof sales_InvoiceDetails) {

                        if ($recPrime->type == 'invoice') {

                            $quantityLastYear = $recPrime->quantity * $recPrime->quantityInPack;
                            $discount = $recPrime->price * $quantityLastYear * $recPrime->discount;
                            $primeCostLastYear = ($recPrime->price * $quantityLastYear) - $discount;

                        } elseif ($recPrime->type == 'dc_note') {
                            $correctionArray = self::dcNoteCorrection($recPrime);

                            if (empty($correctionArray)) {
                                continue;
                            }
                            $quantityLastYear = $correctionArray['quantity'];
                            $primeCostLastYear = $correctionArray['amount'];

                        }

                    }
                }
            }

            //Данни за ТЕКУЩ период
            if ($recPrime->valior >= $rec->from && $recPrime->valior <= $rec->to) {
                if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {
                    $quantity = (-1) * $recPrime->quantity;

                    $primeCost = (-1) * $recPrime->{"${price}"} * $recPrime->quantity;

                    $delta = (-1) * $recPrime->delta;

                } elseif ($DetClass instanceof sales_SalesDetails || $DetClass instanceof store_ShipmentOrderDetails || $DetClass instanceof pos_Reports) {
                    $quantity = $recPrime->quantity;

                    $primeCost = $recPrime->{"${price}"} * $recPrime->quantity;

                    $delta = $recPrime->delta;
                } elseif ($DetClass instanceof sales_InvoiceDetails) {

                    if ($recPrime->type == 'invoice') {

                        $quantity = $recPrime->quantity * $recPrime->quantityInPack;
                        $discount = $recPrime->price * $quantity * $recPrime->discount;
                        $primeCost = ($recPrime->price * $quantity) - $discount;

                    } elseif ($recPrime->type == 'dc_note') {

                        $correctionArray = self::dcNoteCorrection($recPrime);

                        if (empty($correctionArray)) {
                            continue;
                        }

                        $quantity = $correctionArray['quantity'];
                        $primeCost = $correctionArray['amount'];

                    }
                }
            }

            //Ако има избрана валута и тя е различна от основната преизчислява сумите
            if ($rec->currency && ($rec->currency != $baseCurrencyId)) {
                $checkedCurrencyCode = currency_Currencies::getCodeById($rec->currency);

                $rate = currency_CurrencyRates::getRate($recPrime->valior, null, $checkedCurrencyCode);

                $primeCost *= $rate;
                $delta *= $rate;
                $primeCostPrevious *= $rate;
                $deltaPrevious *= $rate;
                $primeCostLastYear *= $rate;
                $deltaLastYear *= $rate;
                if ($invProd[$id]->invAmount) {
                    $invProd[$id]->invAmount *= $rate;
                }
            }

            // Запис в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object)array(

                    'contragent' => $recPrime->folderId,                  //Папка на контрагента

                    'code' => $artCode,                                   //Код на артикула
                    'productId' => $recPrime->productId,                  //Id на артикула
                    'category' => $categoryId,                          //Id на  категорията на артикула
                    'measure' => $measureArt,                             //Мярка

                    'quantity' => $quantity,                              //Текущ период - количество
                    'primeCost' => $primeCost,                            //Текущ период - стойност на продажбите за артикула
                    'delta' => $delta,                                    //Текущ период - ДЕЛТА на продажбите за артикула

                    'quantityPrevious' => $quantityPrevious,              //Предходен период - количество
                    'primeCostPrevious' => $primeCostPrevious,            //Предходен период - стойност на продажбите за артикула
                    'deltaPrevious' => $deltaPrevious,                    //Предходен период - ДЕЛТА на продажбите за артикула

                    'quantityLastYear' => $quantityLastYear,              //Предходна година - количество
                    'primeCostLastYear' => $primeCostLastYear,            //Предходна година - стойност на продажбите за артикула
                    'deltaLastYear' => $deltaLastYear,                    //Предходна година - ДЕЛТА на продажбите за артикула

                    'group' => $recPrime->groupMat,                       // В кои групи е включен артикула
                    'groupList' => $recPrime->groupList,                  //В кои групи е включен контрагента

                    'invQuantity' => $invProd[$id]->invQuantity,          // Фактурирано количество от този артикул на този контрагент
                    'invAmount' => $invProd[$id]->invAmount,              // Стойност на фактурираното количество от този артикул на този контрагент


                );
            } else {
                $obj = &$recs[$id];

                $obj->quantity += $quantity;
                $obj->primeCost += $primeCost;
                $obj->delta += $delta;

                $obj->quantityPrevious += $quantityPrevious;
                $obj->primeCostPrevious += $primeCostPrevious;
                $obj->deltaPrevious += $deltaPrevious;

                $obj->quantityLastYear += $quantityLastYear;
                $obj->primeCostLastYear += $primeCostLastYear;
                $obj->deltaLastYear += $deltaLastYear;
            }
        }

        //Отчитане на ДИ и КИ без детайли

        if ($rec->quantityType == 'invoiced') {
            //За сега работи само когато намери такова ИЗВЕСТИЕ в рамките на периода
            //и то коригира фактура която е от периода

            //iQuery ДИ и КИ влизащи в периода и коригиращи обща сума(без детайли)
            $iQuery = sales_Invoices::getQuery();
            $iQuery->where("#type = 'dc_note'");
            $iQuery->where("#date >= '{$rec->from}' AND #date <= '{$rec->to}'");
            $iQuery->where("#changeAmount IS NOT NULL");

            $correctionArr = array();

            while ($iRec = $iQuery->fetch()) {

                //$originRec rec-a  на фактурата към която е издадено кредитното
                $originId = doc_Containers::getDocument($iRec->originId)->that;
                $originRec = sales_Invoices::fetch($originId);

                //Ако фактурата към която е издадено известието влиза в периода
                // изваждаме нейните детайли в масив с ключ productId-то
                if ($originRec->date >= $rec->from && $originRec->date <= $rec->to) {

                    $dcAllInvQuery = sales_InvoiceDetails::getQuery();

                    $dcAllInvQuery->where("#invoiceId = $originRec->id");

                    //сумира стойностите на всички детайли във origin фактурата
                    $amountsArr = arr::extractValuesFromArray($dcAllInvQuery->fetchAll(), 'amount');
                    $sumAmounts = array_sum($amountsArr);

                    while ($originDetRec = $dcAllInvQuery->fetch()) {

                        //Каква част от общата стойност е стойността на този ред
                        if ($sumAmounts) {
                            $partOfAmount = $originDetRec->amount / $sumAmounts;
                        } else {
                            $partOfAmount = 1;
                        }


                        //Масив с ключ productId и стойностите с които трябва да се коригира стойността на артикула в recs-a
                        $correctionArr[$originDetRec->productId] = round($iRec->changeAmount * $partOfAmount, 2);

                    }
                }
            }

            //Коригираме стоността на артикула в масива recs
            if (!empty($correctionArr) && !empty($recs)) {
                foreach ($correctionArr as $productId => $correctionAmount) {

                    if (isset($recs[$productId]->primeCost)) {

                        $recs[$productId]->primeCost += $correctionAmount;
                    }


                }

            }
        }

        //Изчисляване на промяната в стойността на продажбите и делтите за артикул
        //добавя в масива пропъртита:
        //changePrimeCostPrevious,changeDeltaPrevious,changePrimeCostLastYear,changeDeltaLastYear
        foreach ($recs as $v) {

            //Промяна на стойноста и делтата за артикула[$v->productId] за текущ период спряно предходен
            $v->changePrimeCostPrevious = $v->primeCost - $v->primeCostPrevious;
            $v->changeDeltaPrevious = $v->delta - $v->deltaPrevious;

            //Промяна на стoйноста и делтата за артикула[$v->productId] за текущ период спряно предходна година
            $v->changePrimeCostLastYear = $v->primeCost - $v->primeCostLastYear;
            $v->changeDeltaLastYear = $v->delta - $v->deltaLastYear;
        }

        $groupValues = $groupQuantity = $groupPrimeCostPrevious = $groupPrimeCostLastYear = array();
        $groupDeltas = $groupDeltaPrevious = $groupDeltaLastYear = array();
        $tempArr = array();
        $totalArr = array();
        $totalValue = $totalDelta = 0;

        if ($rec->typeOfGroups == 'art') {
            $typeGroup = 'group';
        } elseif (($rec->typeOfGroups == 'category')) {
            $typeGroup = 'category';
        } elseif (($rec->typeOfGroups == 'no')) {
            $typeGroup = 'category';
        }


        // Изчисляване на общите продажби и продажбите по групи
        foreach ($recs as $v) {

            //Когато НЕ СА ИЗБРАНИ групи артикули
            if (!($rec->$typeGroup)) {
                if (keylist::isKeylist(($v->$typeGroup))) {
                    $v->$typeGroup = keylist::toArray($v->$typeGroup); //Кейлиста с групите го записва като масив
                } elseif (is_numeric($v->$typeGroup)) {
                    $v->$typeGroup = array($v->$typeGroup => $v->$typeGroup); //Ако е избрана категория
                } else {
                    $v->$typeGroup = array('Без група' => 'Без група'); //Ако артикула не е включен в групи записва 'Без група'
                }

                //Изчислява стойността на продажбите и делтата от един артикул
                //за текущ, предходен период и предходна година във ВСЯКА ГРУПА В КОЯТО Е РЕГИСТРИРАН
                foreach ($v->$typeGroup as $k => $gro) {
                    //За този артикул
                    $groupValues[$gro] += $v->primeCost;                        //Стойност на продажбите за текущ период
                    $groupQuantity[$gro] += $v->quantity;                        //Стойност на продажбите за текущ период
                    $groupDeltas[$gro] += $v->delta;                            //Стойност на делтите за текущ период
                    $groupPrimeCostPrevious[$gro] += $v->primeCostPrevious;     //Стойност на продажбите за предходен период
                    $groupDeltaPrevious[$gro] += $v->deltaPrevious;             //Стойност на делтите за предходен период
                    $groupPrimeCostLastYear[$gro] += $v->primeCostLastYear;     //Стойност на продажбите за предходна година
                    $groupDeltaLastYear[$gro] += $v->deltaLastYear;             //Стойност на делтите за предходна година
                }
                unset($gro, $k);

                //изчислява обща стойност на всички артикули продадени
                //през текущ, предходен период и предходна година когато не е избрана група
                $totalValue += $v->primeCost;
                $totalDelta += $v->delta;
                $totalPrimeCostPrevious += $v->primeCostPrevious;
                $totalDeltaPrevious += $v->deltaPrevious;
                $totalPrimeCostLastYear += $v->primeCostLastYear;
                $totalDeltaLastYear += $v->deltaLastYear;
            } else {

                //КОГАТО ИМА ИЗБРАНИ ГРУПИ
                //изчислява обща стойност на артикулите от избраните групи продадени
                //през текущ, предходен период и предходна година, и стойността по групи(само ИЗБРАНИТЕ)


                $grArr = array();

                //Масив с избраните групи
                $checkedGroups = keylist::toArray($rec->$checkForGruping);

                $goupsArr = (keylist::isKeylist($v->$typeGroup)) ? keylist::toArray($v->$typeGroup) : array($v->$typeGroup => $v->$typeGroup);

                foreach ($checkedGroups as $key => $val) {
                    if (in_array($val, $goupsArr)) {
                        $grArr[$val] = $val;                            //Масив от групите в които е ргистриран артикула АКО СА ЧАСТ ОТ ИЗБРАНИТЕ ГРУПИ
                    }
                }

                unset($key, $val);

                $tempArrKey = ($rec->seeByContragent == 'yes') ? $v->productId . ' | ' . $v->contragent : $v->productId;


                $tempArr[$tempArrKey] = $v;

                $tempArr[$tempArrKey]->$typeGroup = $grArr; //Оставяме в записа за артикула само групите които са избрани

                //изчислява ОБЩА стойност на всички артикули продадени
                //през текущ, предходен период и предходна година за ВСИЧКИ избрани групи
                $totalValue += $v->primeCost;
                $totalDelta += $v->delta;
                $totalPrimeCostPrevious += $v->primeCostPrevious;
                $totalDeltaPrevious += $v->deltaPrevious;
                $totalPrimeCostLastYear += $v->primeCostLastYear;
                $totalDeltaLastYear += $v->deltaLastYear;

                //Изчислява продажбите по артикул за всички артикули във всяка избрана група
                //Един артикул може да го има в няколко групи
                foreach ($tempArr[$tempArrKey]->$typeGroup as $gro) {
                    $groupValues[$gro] += $v->primeCost;
                    $groupQuantity[$gro] += $v->quantity;
                    $groupDeltas[$gro] += $v->delta;
                    $groupPrimeCostPrevious[$gro] += $v->primeCostPrevious;
                    $groupDeltaPrevious[$gro] += $v->deltaPrevious;
                    $groupPrimeCostLastYear[$gro] += $v->primeCostLastYear;
                    $groupDeltaLastYear[$gro] += $v->deltaLastYear;
                }
                unset($gro);

                $recs = $tempArr;
            }

            if ($rec->compare && (($rec->compare == 'previous') || ($rec->compare == 'month'))) {
                $changePrimeCost = 'changePrimeCostPrevious';
                $changeDelta = 'changeDeltaPrevious';
            }

            if ($rec->compare && ($rec->compare == 'year')) {
                $changePrimeCost = 'changePrimeCostLastYear';
                $changeDelta = 'changeDeltaLastYear';
            }
        }


        //при избрани групи включва артикулите във всички групи в които са регистрирани, и се сумира във всички групи
        if (!is_null(($rec->group || $rec->category))) {
            $tempArr = array();

            foreach ($recs as $v) {
                foreach ($v->$typeGroup as $val) {
                    $v = clone $v;
                    $v->$typeGroup = (int)$val;
                    $tempArr[] = $v;

                    if (!$rec->$checkForGruping) {
                        break;
                    }
                }
            }
            unset($val, $v);

            $recs = $tempArr;

            foreach ($recs as $v) {
                $v->groupValues = $groupValues[$v->$typeGroup];
                $v->groupQuantity = $groupQuantity[$v->$typeGroup];
                $v->groupDeltas = $groupDeltas[$v->$typeGroup];
                $v->groupPrimeCostPrevious = $groupPrimeCostPrevious[$v->$typeGroup];
                $v->groupDeltaPrevious = $groupDeltaPrevious[$v->$typeGroup];
                $v->groupPrimeCostLastYear = $groupPrimeCostLastYear[$v->$typeGroup];
                $v->groupDeltaLastYear = $groupDeltaLastYear[$v->$typeGroup];
            }
            unset($v);
        } else {
            foreach ($recs as $v) {
                foreach ($v->$typeGroup as $gro) {
                    $v->groupValues = $groupValues[$gro];
                    $v->groupQuantity = $groupQuantity[$gro];
                    $v->groupDeltas = $groupDeltas[$gro];

                    $v->groupPrimeCostPrevious = $groupPrimeCostPrevious[$gro];
                    $v->groupDeltaPrevious = $groupDeltaPrevious[$gro];

                    $v->groupPrimeCostLastYear = $groupPrimeCostLastYear[$gro];
                    $v->groupDeltaLastYear = $groupDeltaLastYear[$gro];
                }
            }
            unset($v, $gro);
        }


        //запис на промяната в делтите и промяната на стойностите в променливи
        if ($rec->compare && (($rec->compare == 'previous') || ($rec->compare == 'month'))) {
            $changePrimeCost = 'changePrimeCostPrevious';
            $changeDelta = 'changeDeltaPrevious';
        }

        if ($rec->compare && ($rec->compare == 'year')) {
            $changePrimeCost = 'changePrimeCostLastYear';
            $changeDelta = 'changeDeltaLastYear';
        }

        //Когато имаме избрано групирано показване правим нов масив
        if ($rec->grouping == 'yes') {
            $recs = array();

            if ($rec->typeOfGroups == 'category') {
                foreach ($groupValues as $key => $val) {
                    if (cat_Categories::fetch($key) === false) {
                        $groupValues[99999] += $val;
                        unset($groupValues[$key]);
                    }
                }
            }

            foreach ($groupValues as $k => $v) {
                $recs[$k] = (object)array(
                    'group' => $k,                                                                    //Група артикули
                    'primeCost' => $v,                                                                //Продажби за текущия период за групата
                    'delta' => $groupDeltas[$k],                                                      //Делта за текущия период за групата

                    'groupPrimeCostPrevious' => $groupPrimeCostPrevious[$k],                          //Продажби за предходен период за групата
                    'changeGroupPrimeCostPrevious' => $v - $groupPrimeCostPrevious[$k],               //Промяна в продажбите спрямо предходен период за групата
                    'groupDeltaPrevious' => $groupDeltaPrevious[$k],                                  //Делта за предходен период за групата
                    'changeGroupDeltaPrevious' => $groupDeltas[$k] - $groupDeltaPrevious[$k],   //Промяна в делтите спрямо предходен период за групата

                    'groupPrimeCostLastYear' => $groupPrimeCostLastYear[$k],                //Продажби за предходна година за групата
                    'changeGroupPrimeCostLastYear' => $v - $groupPrimeCostLastYear[$k],             //Промяна в продажбите спрямо предходна година за групата
                    'groupDeltaLastYear' => $groupDeltaLastYear[$k],                    //Делта за предходна година за групата
                    'changeGroupDeltaLastYear' => $groupDeltas[$k] - $groupDeltaLastYear[$k],   //Промяна в делтите спрямо предходна година за групата
                );
            }

            if ($rec->compare && (($rec->compare == 'previous') || ($rec->compare == 'month'))) {
                $changePrimeCost = 'changeGroupPrimeCostPrevious';
                $changeDelta = 'changeGroupDeltaPrevious';
            }

            if ($rec->compare && ($rec->compare == 'year')) {
                $changePrimeCost = 'changeGroupPrimeCostLastYear';
                $changeDelta = 'changeGroupDeltaLastYear';
            }
        }

        //Добавяне на колона за теглото
        if ($rec->seeWeight == 'yes' && $rec->grouping == 'no' && $rec->compare == 'no') {

            foreach ($recs as $val) {
                $prodRec = cat_Products::fetch($val->productId);
                $prodWeight = self::getProductWeight($prodRec);
                $val->weight = (is_numeric($prodWeight)) ? $prodWeight * $val->quantity : 'n.a.';

            }


        }

        //Подредба на резултатите
        if (!is_null($recs)) {
            $typeOrder = ($rec->orderBy == 'code') ? 'stri' : 'native';

            $orderBy = $rec->orderBy;

            if ($rec->orderBy == 'changeDelta') {
                $orderBy = $changeDelta;
            }

            if ($rec->orderBy == 'changeCost') {
                $orderBy = $changePrimeCost;
            }

            arr::sortObjects($recs, $orderBy, $rec->order, $typeOrder);
        }

        //Добавям ред за ОБЩИТЕ суми
        $totalArr['total'] = (object)array(
            'totalValue' => $totalValue,
            'totalDelta' => $totalDelta,
            'totalPrimeCostPrevious' => $totalPrimeCostPrevious,
            'totalDeltaPrevious' => $totalDeltaPrevious,
            'totalPrimeCostLastYear' => $totalPrimeCostLastYear,
            'totalDeltaLastYear' => $totalDeltaLastYear
        );

        array_unshift($recs, $totalArr['total']);

        return $recs;
    }

    /**
     * Връща детайлите по фактурите
     *
     * @return array $invDetQuery
     */
    public static function getInvoicedProducts($rec)
    {
        $invDetQuery = array();

        $invDetQuery = sales_InvoiceDetails::getQuery();

        $invDetQuery->EXT('state', 'sales_Invoices', 'externalName=state,externalKey=invoiceId');

        $invDetQuery->EXT('number', 'sales_Invoices', 'externalName=number,externalKey=invoiceId');

        $invDetQuery->EXT('originId', 'sales_Invoices', 'externalName=originId,externalKey=invoiceId');

        $invDetQuery->EXT('changeAmount', 'sales_Invoices', 'externalName=changeAmount,externalKey=invoiceId');

        $invDetQuery->EXT('currencyId', 'sales_Invoices', 'externalName=currencyId,externalKey=invoiceId');

        $invDetQuery->EXT('date', 'sales_Invoices', 'externalName=date,externalKey=invoiceId');

        $invDetQuery->EXT('valior', 'sales_Invoices', 'externalName=date,externalKey=invoiceId');

        $invDetQuery->EXT('type', 'sales_Invoices', 'externalName=type,externalKey=invoiceId');

        $invDetQuery->EXT('folderId', 'sales_Invoices', 'externalName=folderId,externalKey=invoiceId');

        $invDetQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');

        return $invDetQuery;

    }

    /**
     * Преизчислява стойностите и количествата на фактурите, към които има КИ и ДИ
     * когато те коригират реда по количество или стойност
     *
     * @return array $res
     */
    public static function dcNoteCorrection($dcRec)
    {
        $originQuantity = $changeQuatity = $changePrice = $invQuantity = $invAmount = 0;

        $res = array();

        $originId = doc_Containers::getDocument($dcRec->originId)->that;

        $originDetRec = sales_InvoiceDetails::fetch("#invoiceId = $originId AND #productId = '$dcRec->productId' AND
                                                           #packagingId = '$dcRec->packagingId'
                                                           AND (#quantity != '$dcRec->quantity' OR #price != '$dcRec->price')");

        $originQuantity = $originDetRec->quantity * $originDetRec->quantityInPack;
        $changeQuatity = $dcRec->quantity * $dcRec->quantityInPack - $originQuantity;
        $changePrice = $dcRec->price - $originDetRec->price;

        if (($changeQuatity == 0 && $changePrice == 0) || !$originDetRec) {
            return $res;
        }

        $invQuantity = $changeQuatity != 0 ? $changeQuatity : 0;
        $invAmount = $changeQuatity == 0 ? $changePrice * $dcRec->quantity * $dcRec->quantityInPack : $dcRec->price * $invQuantity;

        if ($dcRec->discount) {
            $invAmount = $invAmount * (1 - $dcRec->discount);
        }

        $res['quantity'] = $invQuantity;
        $res['amount'] = $invAmount;

        return $res;

    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *                         - записа
     * @param bool $export
     *                         - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');

        if ($rec->compare == 'month') {
            $name1 = acc_Periods::fetch($rec->firstMonth)->title;
            $name2 = acc_Periods::fetch($rec->secondMonth)->title;
        } else {
            $name1 = 'За периода';
            $name2 = 'За сравнение';
        }

        if ($export === false) {

            //по артикули
            if ($rec->grouping == 'no') {
                $fld->FLD('code', 'varchar', 'caption=Код');
                $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');

                if ($rec->engName == 'yes') {
                    $fld->FLD('engName', 'key(mvc=cat_Products,select=nameEn)', 'caption=Артикул[EN]');
                }

                $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
                if ($rec->seeCategory == 'yes') {
                    $fld->FLD('category', 'key(mvc=doc_Folders,select=title)', 'caption=Категория');
                }

                if ($rec->compare != 'no') {
                    $fld->FLD('quantity', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1}->Продажби");
                    $fld->FLD('primeCost', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1}->Стойност");

                    if ($rec->seeDelta == 'yes') {
                        $fld->FLD('delta', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1}->Делта");
                    }
                    $fld->FLD('quantityCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2}->Продажби,tdClass=newCol");
                    $fld->FLD('primeCostCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2}-> Стойност,tdClass=newCol");

                    if ($rec->seeDelta == 'yes') {
                        $fld->FLD('deltaCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2}->Делта,tdClass=newCol");
                    }

                    $fld->FLD('changeSales', 'double(smartRound,decimals=2)', 'smartCenter,caption=Промяна->Стойност');

                    if ($rec->seeDelta == 'yes') {
                        $fld->FLD('changeDeltas', 'double(smartRound,decimals=2)', 'smartCenter,caption=Промяна->Делти');
                    }
                } else {
                    $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Продажби->Количество');
                    $fld->FLD('primeCost', 'double(smartRound,decimals=2)', 'smartCenter,caption=Продажби->Стойност');


                    if ($rec->seeByContragent == 'yes') {
                        $fld->FLD('contragent', 'keylist(mvc=doc_Folders,select=name)', 'caption=Контрагент');
                        $fld->FLD('invQuantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Фактурирано->количество');
                        $fld->FLD('invAmount', 'double(smartRound,decimals=2)', 'smartCenter,caption=Фактурирано->стойност');
                    }

                    if ($rec->seeDelta == 'yes') {
                        $fld->FLD('delta', 'double(smartRound,decimals=2)', 'smartCenter,caption=Делта');
                    }

                    if ($rec->seeWeight == 'yes') {
                        $fld->FLD('weight', 'double(smartRound,decimals=2)', 'smartCenter,caption=Тегло->[кг]');
                    }
                }
            } else {

                //по групи
                $fld->FLD('group', 'varchar', 'caption=Група');
                $fld->FLD('primeCost', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1}->Стойност");


                if ($rec->seeDelta == 'yes') {
                    $fld->FLD('delta', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1}->Делта");
                }
                if ($rec->compare != 'no') {
                    $fld->FLD('primeCostCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2}->Стойност,tdClass=newCol");

                    if ($rec->seeDelta == 'yes') {
                        $fld->FLD('deltaCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2}->Делта,tdClass=newCol");
                    }
                    $fld->FLD('changeSales', 'double(smartRound,decimals=2)', 'smartCenter,caption=Промяна->Стойност');

                    if ($rec->seeDelta == 'yes') {
                        $fld->FLD('changeDeltas', 'double(smartRound,decimals=2)', 'smartCenter,caption=Промяна->Делти');
                    }
                }
            }
        } else {
            //експорт
            if ($rec->seeByContragent == 'yes') {
                $fld->FLD('contragent', 'varchar', 'caption=Контрагент');
            }
            if ($rec->group) {
                $fld->FLD('group', 'varchar', 'caption=Група');
            }

            $fld->FLD('code', 'varchar', 'caption=Код');
            $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');

            if ($rec->engName == 'yes') {
                $fld->FLD('engName', 'varchar', 'caption=Артикул[EN]');
            }

            $fld->FLD('measure', 'key( да дам мнmvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
            if ($rec->seeCategory == 'yes') {
                $fld->FLD('category', 'varchar', 'caption=Категория');
            }
            $fld->FLD('quantity', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1} Продажби");
            $fld->FLD('primeCost', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1} Стойност");

            if ($rec->seeByContragent == 'yes') {
                $fld->FLD('invQuantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Фактурирано->количество');
                $fld->FLD('invAmount', 'double(smartRound,decimals=2)', 'smartCenter,caption=Фактурирано->стойност');
            }

            if ($rec->seeDelta == 'yes') {
                $fld->FLD('delta', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1} Делта");
            }


            if ($rec->compare != 'no') {
                $fld->FLD('quantityCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2} Продажби,tdClass=newCol");
                $fld->FLD('primeCostCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2} Стойност,tdClass=newCol");
                $fld->FLD('deltaCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2} Делта,tdClass=newCol");
                $fld->FLD('changeSales', 'double(smartRound,decimals=2)', 'smartCenter,caption=Промяна Стойност');
                $fld->FLD('changeDeltas', 'double(smartRound,decimals=2)', 'smartCenter,caption=Промяна Делти');
            }
        }

        return $fld;
    }


    /**
     * Връща групите
     *
     * @param stdClass $dRec
     * @param bool $verbal
     *
     * @return mixed $dueDate
     */
    private static function getGroups($dRec, $verbal = true, $rec)
    {
        if ($rec->typeOfGroups == 'art') {
            $typeGroup = 'group';
        } elseif (($rec->typeOfGroups == 'category')) {
            $typeGroup = 'category';
        }

        if ($verbal === true) {
            if (is_numeric($dRec->$typeGroup)) {
                $groupVal = $dRec->groupValues;
                $groupDeltas = $dRec->groupDeltas;
                $grouping = ($rec->seeDelta == 'yes') ? ', делта: ' . core_Type::getByName('double(decimals=2)')->toVerbal($groupDeltas) : '';

                if ($rec->typeOfGroups == 'art') {
                    $groupClass = 'cat_Groups';
                } elseif (($rec->typeOfGroups == 'category')) {
                    $groupClass = 'cat_Categories';
                }

                $groupName = $groupClass::getVerbal($dRec->$typeGroup, 'name');
                if ($dRec->groupQuantity != 0) {
                    $price = $dRec->groupValues / $dRec->groupQuantity;
                } else {
                    $price = 0;
                }


                $group = $groupName . "<span class= 'fright'><span class= ''>" . 'Общо за групата (количество:' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupQuantity) . ' ; ' . 'стойност: ' . core_Type::getByName('double(decimals=2)')->toVerbal($groupVal) . ' ; ' . 'ср. цена: ' . core_Type::getByName('double(decimals=2)')->toVerbal($price) . $grouping . ' )' . '</span>';
            } else {
                $price = $dRec->groupValues / $dRec->groupQuantity;
                $group = $dRec->group . "<span class= 'fright'>" . 'Общо за групата (количество:' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupQuantity) . ' ; ' . 'стойност: ' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupValues) . ' ; ' . 'ср. цена: ' . core_Type::getByName('double(decimals=2)')->toVerbal($price) . ', делта: ' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupDeltas) . ' )' . '</span>';
            }
        } else {
            if (!is_numeric($dRec->group)) {
                $group = 'Без група';
            } else {
                $group = cat_Groups::getVerbal($dRec->group, 'name');
            }
        }

        return $group;
    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *                       - записа
     * @param stdClass $dRec
     *                       - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;

        $row = new stdClass();

        //Извеждане на реда с ОБЩО
        if (isset($dRec->totalValue)) {
            $row->productId = '<b>' . 'ОБЩО ЗА ПЕРИОДА:' . '</b>';
            $row->primeCost = '<b>' . $Double->toVerbal($dRec->totalValue) . '</b>';
            $row->delta = '<b>' . $Double->toVerbal($dRec->totalDelta) . '</b>';

            foreach (array('primeCost', 'delta') as $q) {
                if (!isset($dRec->{$q})) {
                    continue;
                }

                $row->{$q} = ht::styleNumber($row->{$q}, $dRec->{$q});
            }

            if ($rec->grouping == 'yes') {
                $row->group = '<b>' . 'ОБЩО ЗА ПЕРИОДА:' . '</b>';
            }

            if ($rec->compare != 'no') {
                $changeDeltas = $changeSales = 0;

                if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                    $row->primeCostCompare = '<b>' . $Double->toVerbal($dRec->totalPrimeCostPrevious) . '</b>';
                    $row->primeCostCompare = ht::styleNumber($row->primeCostCompare, $dRec->totalPrimeCostPrevious);

                    $row->deltaCompare = '<b>' . $Double->toVerbal($dRec->totalDeltaPrevious) . '</b>';
                    $row->deltaCompare = ht::styleNumber($row->deltaCompare, $dRec->totalDeltaPrevious);

                    $changeSales = $dRec->totalValue - $dRec->totalPrimeCostPrevious;
                    $row->changeSales = '<b>' . $Double->toVerbal($changeSales) . '</b>';
                    $row->changeSales = ht::styleNumber($row->changeSales, $changeSales);

                    $changeDeltas = $dRec->totalDelta - $dRec->totalDeltaPrevious;
                    $row->changeDeltas = '<b>' . $Double->toVerbal($changeDeltas) . '</b>';
                    $row->changeDeltas = ht::styleNumber($row->changeDeltas, $changeDeltas);
                }
                if ($rec->compare == 'year') {
                    $row->primeCostCompare = '<b>' . $Double->toVerbal($dRec->totalPrimeCostLastYear) . '</b>';
                    $row->primeCostCompare = ht::styleNumber($row->primeCostCompare, $dRec->totalPrimeCostLastYear);

                    $row->deltaCompare = '<b>' . $Double->toVerbal($dRec->totalDeltaLastYear) . '</b>';
                    $row->deltaCompare = ht::styleNumber($row->deltaCompare, $dRec->totalDeltaLastYear);

                    $changeSales = $dRec->totalValue - $dRec->totalPrimeCostLastYear;
                    $row->changeSales = '<b>' . $Double->toVerbal($changeSales) . '</b>';
                    $row->changeSales = ht::styleNumber($row->changeSales, $changeSales);

                    $changeDeltas = $dRec->totalDelta - $dRec->totalDeltaLastYear;
                    $row->changeDeltas = '<b>' . $Double->toVerbal($changeDeltas) . '</b>';
                    $row->changeDeltas = ht::styleNumber($row->changeDeltas, $changeDeltas);
                }
            }

            return $row;
        }

        //Ако имаме избрано показване "ГРУПИРАНО"
        if ($rec->grouping == 'yes') {
            if ($rec->typeOfGroups == 'art') {
                $groupClass = 'cat_Groups';
            } elseif (($rec->typeOfGroups == 'category')) {
                $groupClass = 'cat_Categories';
            }

            if (is_numeric($dRec->group)) {
                $groupName = ($dRec->group != '99999' ? $groupClass::getVerbal($dRec->group, 'name') : 'Частен артикул');
                $row->group = $groupName;
            } else {
                $row->group = 'Без група';
            }
            $row->primeCost = $Double->toVerbal($dRec->primeCost);
            $row->delta = $Double->toVerbal($dRec->delta);

            if ($rec->compare != 'no') {
                if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                    $row->primeCostCompare = $Double->toVerbal($dRec->groupPrimeCostPrevious);
                    $row->primeCostCompare = ht::styleNumber($row->primeCostCompare, $dRec->groupPrimeCostPrevious);

                    $row->deltaCompare = $Double->toVerbal($dRec->groupDeltaPrevious);
                    $row->deltaCompare = ht::styleNumber($row->deltaCompare, $dRec->groupDeltaPrevious);

                    $row->changeSales = $Double->toVerbal($dRec->changeGroupPrimeCostPrevious);
                    $row->changeSales = ht::styleNumber($row->changeSales, $dRec->changeGroupPrimeCostPrevious);

                    $row->changeDeltas = '<b>' . $Double->toVerbal($dRec->changeGroupDeltaPrevious) . '</b>';
                    $row->changeDeltas = ht::styleNumber($row->changeDeltas, $dRec->changeGroupDeltaPrevious);
                }

                if ($rec->compare == 'year') {
                    $row->primeCostCompare = '<b>' . $Double->toVerbal($dRec->groupPrimeCostLastYear) . '</b>';
                    $row->primeCostCompare = ht::styleNumber($row->primeCostCompare, $dRec->groupPrimeCostLastYear);

                    $row->deltaCompare = '<b>' . $Double->toVerbal($dRec->groupDeltaLastYear) . '</b>';
                    $row->deltaCompare = ht::styleNumber($row->deltaCompare, $dRec->groupDeltaLastYear);

                    $row->changeSales = '<b>' . $Double->toVerbal($dRec->changeGroupPrimeCostLastYear) . '</b>';
                    $row->changeSales = ht::styleNumber($row->changeSales, $dRec->changeGroupPrimeCostLastYear);

                    $row->changeDeltas = '<b>' . $Double->toVerbal($dRec->changeGroupDeltaLastYear) . '</b>';
                    $row->changeDeltas = ht::styleNumber($row->changeDeltas, $dRec->changeGroupDeltaLastYear);
                }
            }

            return $row;
        }

        //Ако имаме избрано показване "ПО АРТИКУЛИ"
        if ($rec->grouping == 'no') {
            $row->contragent = doc_Folders::getTitleById($dRec->contragent);

            if (isset($dRec->code)) {
                $row->code = $dRec->code;
            }
            if (isset($dRec->productId)) {
                $row->productId = cat_Products::getLinkToSingle_($dRec->productId, 'name');
            }
            if ($rec->engName == 'yes') {
                $engName = cat_Products::fetch($dRec->productId)->nameEn ? cat_Products::fetch($dRec->productId)->nameEn : 'none';
                $row->engName = $engName;
            }


            if (isset($dRec->measure)) {
                $row->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
            }

            if ($rec->seeCategory == 'yes') {
                $prodFolderId = cat_Products::fetch($dRec->productId)->folderId;
                $prodCategory = doc_Folders::fetch($prodFolderId)->title;
                $row->category = $prodCategory;
            }
            foreach (array(
                         'quantity',
                         'primeCost',
                         'delta',
                         'invQuantity',
                         'invAmount',
                         'weight'
                     ) as $fld) {
                if (!isset($dRec->{$fld})) {
                    continue;
                }

                $row->{$fld} = $Double->toVerbal($dRec->{$fld});
                $row->{$fld} = ht::styleNumber($row->{$fld}, $dRec->{$fld});
            }

            if ($rec->typeOfGroups == 'art') {
                $fieldForGroup = 'group';
            } elseif (($rec->typeOfGroups == 'category')) {
                $fieldForGroup = 'category';
            } elseif (($rec->typeOfGroups == 'no')) {
                $fieldForGroup = 'category';
            }
            if ($rec->$fieldForGroup) {
                $row->$fieldForGroup = self::getGroups($dRec, true, $rec);
            }


            if ($rec->compare != 'no') {
                if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                    $row->quantityCompare = $Double->toVerbal($dRec->quantityPrevious);
                    $row->quantityCompare = ht::styleNumber($row->quantityCompare, $dRec->quantityPrevious);

                    $row->primeCostCompare = $Double->toVerbal($dRec->primeCostPrevious);
                    $row->primeCostCompare = ht::styleNumber($row->primeCostCompare, $dRec->primeCostPrevious);

                    $row->deltaCompare = $Double->toVerbal($dRec->deltaPrevious);
                    $row->deltaCompare = ht::styleNumber($row->deltaCompare, $dRec->deltaPrevious);

                    $row->changeSales = $Double->toVerbal($dRec->changePrimeCostPrevious);
                    $row->changeSales = ht::styleNumber($row->changeSales, $dRec->changePrimeCostPrevious);

                    $row->changeDeltas = $Double->toVerbal($dRec->changeDeltaPrevious);
                    $row->changeDeltas = ht::styleNumber($row->changeDeltas, $dRec->changeDeltaPrevious);
                }

                if ($rec->compare == 'year') {
                    $row->quantityCompare = $Double->toVerbal($dRec->quantityLastYear);
                    $row->quantityCompare = ht::styleNumber($row->quantityCompare, $dRec->quantityLastYear);

                    $row->primeCostCompare = $Double->toVerbal($dRec->primeCostLastYear);
                    $row->primeCostCompare = ht::styleNumber($row->primeCostCompare, $dRec->primeCostLastYear);

                    $row->deltaCompare = $Double->toVerbal($dRec->deltaLastYear);
                    $row->deltaCompare = ht::styleNumber($row->deltaCompare, $dRec->deltaLastYear);

                    $row->changeSales = $Double->toVerbal($dRec->changePrimeCostLastYear);
                    $row->changeSales = ht::styleNumber($row->changeSales, $dRec->changePrimeCostLastYear);

                    $row->changeDeltas = $Double->toVerbal($dRec->changeDeltaLastYear);
                    $row->changeDeltas = ht::styleNumber($row->changeDeltas, $dRec->changeDeltaLastYear);
                }
            }

            return $row;
        }
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
        $groArr = array();
        $artArr = array();

        $Date = cls::get('type_Date');

        $row->from = $Date->toVerbal($rec->from);

        $row->to = $Date->toVerbal($rec->to);

        if (isset($rec->group)) {
            // избраната позиция
            $groups = keylist::toArray($rec->group);
            foreach ($groups as &$g) {
                $gro = cat_Groups::getVerbal($g, 'name');
                array_push($groArr, $gro);
            }

            $row->group = implode(', ', $groArr);
        }

        if (isset($rec->article)) {
            $arts = keylist::toArray($rec->article);
            foreach ($arts as &$ar) {
                $art = cat_Products::fetchField("#id = '{$ar}'", 'name');
                array_push($artArr, $art);
            }

            $row->art = implode(', ', $artArr);
        }

        $arrCompare = array(
            'no' => 'Без сравнение',
            'previous' => 'С предходен период',
            'year' => 'С миналогодишен период',
            'month' => 'По месеци'
        );
        $row->compare = $arrCompare[$rec->compare];
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN from--><div>|От|*: [#from#]</div><!--ET_END from-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                        <!--ET_BEGIN firstMonth--><div>|Месец 1|*: [#firstMonth#]</div><!--ET_END firstMonth-->
                                        <!--ET_BEGIN secondMonth--><div>|Месец 2|*: [#secondMonth#]</div><!--ET_END secondMonth-->
                                        <!--ET_BEGIN dealers--><div>|Търговци|*: [#dealers#]</div><!--ET_END dealers-->
                                        <!--ET_BEGIN contragent--><div>|Контрагент|*: [#contragent#]</div><!--ET_END contragent-->
                                        <!--ET_BEGIN crmGroup--><div>|Група контрагенти|*: [#crmGroup#]</div><!--ET_END crmGroup-->
                                        <!--ET_BEGIN group--><div>|Групи продукти|*: [#group#]</div><!--ET_END group-->
                                        <!--ET_BEGIN category--><div>|Категории продукти|*: [#category#]</div><!--ET_END category-->
                                        <!--ET_BEGIN art--><div>|Артикули|*: [#art#]</div><!--ET_END art-->
                                        <!--ET_BEGIN compare--><div>|Сравнение|*: [#compare#]</div><!--ET_END compare-->
                                        <!--ET_BEGIN currency--><div>|Валута|*: [#currency#]</div><!--ET_END currency-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));

        if ($data->rec->compare == 'month') {
            unset($data->rec->from);
            unset($data->rec->to);
        } else {
            unset($data->rec->firstMonth);
            unset($data->rec->secondMonth);
        }
        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' . $data->row->from . '</b>', 'from');
        }

        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $data->row->to . '</b>', 'to');
        }

        if (isset($data->rec->firstMonth)) {
            $fieldTpl->append('<b>' . acc_Periods::fetch($data->rec->firstMonth)->title . '</b>', 'firstMonth');
        }

        if (isset($data->rec->secondMonth)) {
            $fieldTpl->append('<b>' . acc_Periods::fetch($data->rec->secondMonth)->title . '</b>', 'secondMonth');
        }

        if ((isset($data->rec->dealers)) && ((min(array_keys(keylist::toArray($data->rec->dealers))) >= 1))) {
            foreach (type_Keylist::toArray($data->rec->dealers) as $dealer) {
                $dealersVerb .= (core_Users::getTitleById($dealer) . ', ');
            }

            $fieldTpl->append('<b>' . trim($dealersVerb, ',  ') . '</b>', 'dealers');
        } else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'dealers');
        }

        if (isset($data->rec->contragent) || isset($data->rec->crmGroup)) {
            $marker = 0;
            if (isset($data->rec->crmGroup)) {
                foreach (type_Keylist::toArray($data->rec->crmGroup) as $group) {
                    $marker++;

                    $groupVerb .= (crm_Groups::getTitleById($group));

                    if ((countR((type_Keylist::toArray($data->rec->crmGroup))) - $marker) != 0) {
                        $groupVerb .= ', ';
                    }
                }

                $fieldTpl->append('<b>' . $groupVerb . '</b>', 'crmGroup');
            }

            $marker = 0;

            if (isset($data->rec->contragent)) {
                foreach (type_Keylist::toArray($data->rec->contragent) as $contragent) {
                    $marker++;

                    $contragentVerb .= (doc_Folders::getTitleById($contragent));

                    if ((countR(type_Keylist::toArray($data->rec->contragent))) - $marker != 0) {
                        $contragentVerb .= ', ';
                    }
                }

                $fieldTpl->append('<b>' . $contragentVerb . '</b>', 'contragent');
            }
        } else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'contragent');
        }

        if (isset($data->rec->group)) {
            $fieldTpl->append('<b>' . $data->row->group . '</b>', 'group');
        }

        $marker = 0;
        if (isset($data->rec->category)) {
            foreach (type_Keylist::toArray($data->rec->category) as $category) {
                $marker++;

                $categoryVerb .= (cat_Categories::fetch($category)->name);

                if ((countR(type_Keylist::toArray($data->rec->category))) - $marker != 0) {
                    $categoryVerb .= ', ';
                }
            }

            $fieldTpl->append('<b>' . $categoryVerb . '</b>', 'category');
        }

        if (isset($data->rec->article)) {
            $fieldTpl->append($data->rec->art, 'art');
        }

        if (isset($data->rec->compare)) {
            $fieldTpl->append('<b>' . $data->row->compare . '</b>', 'compare');
        }

        $baseCurrency = acc_Periods::getBaseCurrencyCode($data->rec->to);
        if (isset($data->rec->currency)) {
            $currency = currency_Currencies::getCodeById($data->rec->currency);
            if ($currency == $baseCurrency) {
                $currency = $baseCurrency . ' (основна)';
            }
            $fieldTpl->append('<b>' . $currency . '</b>', 'currency');
        } else {

            $fieldTpl->append('<b>' . $baseCurrency . ' (основна)' . '</b>', 'currency');
        }

        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }


    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass $res
     * @param stdClass $rec
     * @param stdClass $dRec
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        $res->group = self::getGroups($dRec, false, $rec);
        if (isset($dRec->measure)) {
            $res->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
        }

        if ($rec->compare != 'no') {
            if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                $res->quantityCompare = $dRec->quantityPrevious;
                $res->primeCostCompare = $dRec->primeCostPrevious;
                $res->deltaCompare = $dRec->deltaPrevious;
                $res->changeSales = $dRec->primeCost - $dRec->primeCostPrevious;
                $res->changeDeltas = ($dRec->delta - $dRec->deltaPrevious);
            }

            if ($rec->compare == 'year') {
                $res->quantityCompare = $dRec->quantityLastYear;
                $res->primeCostCompare = $dRec->primeCostLastYear;
                $res->deltaCompare = $dRec->deltaLastYear;
                $res->changeSales = ($dRec->primeCost - $dRec->primeCostLastYear);
                $res->changeDeltas = ($dRec->delta - $dRec->deltaLastYear);
            }
        } else {
            if ($rec->seeByContragent == 'yes') {
                $res->contragent = doc_Folders::getTitleById($dRec->contragent);
            }
        }

        if ($res->totalValue) {
            $res->group = 'ОБЩО ЗА ПЕРИОДА:';
            $res->primeCost = $dRec->totalValue;
            $res->delta = $dRec->totalDelta;

            if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                $res->primeCostCompare = $dRec->totalPrimeCostPrevious;
                $res->deltaCompare = $dRec->totalDeltaPrevious;
                $res->changeSales = ($dRec->primeCost - $dRec->totalPrimeCostPrevious);
                $res->changeDeltas = ($dRec->delta - $dRec->totalDeltaPrevious);
            }

            if ($rec->compare == 'year') {
                $res->primeCostCompare = $dRec->totalPrimeCostLastYear;
                $res->deltaCompare = $dRec->totalDeltaLastYear;
                $res->changeSales = ($dRec->primeCost - $dRec->totalPrimeCostLastYear);
                $res->changeDeltas = ($dRec->delta - $dRec->totalDeltaLastYear);
            }
        } else {
            if ($rec->engName == 'yes') {
                $engName = cat_Products::fetch($dRec->productId)->nameEn ? cat_Products::fetch($dRec->productId)->nameEn : 'none';
                $res->engName = $engName;
            }
            if ($rec->seeCategory == 'yes') {
                $prodFolderId = cat_Products::fetch($dRec->productId)->folderId;
                $prodCategory = doc_Folders::fetch($prodFolderId)->title;
                $res->category = $prodCategory;
            }
        }
    }


    /**
     * Връща папките на контрагентите от избраните групи
     *
     * @param stdClass $rec
     *
     * @return array
     */
    public static function getFoldersInGroups($rec)
    {
        $foldersInGroups = array();
        foreach (array('crm_Companies', 'crm_Persons') as $clsName) {
            $q = $clsName::getQuery();

            $q->LikeKeylist('groupList', $rec->crmGroup);

            $q->where('#folderId IS NOT NULL');

            $q->show('folderId');

            $foldersInGroups = array_merge($foldersInGroups, arr::extractValuesFromArray($q->fetchAll(), 'folderId'));
        }

        return $foldersInGroups;
    }

    /**
     * Връща единично тегло на артикула
     *
     * @param stdClass $rec
     *
     * @return double
     */
    public static function getProductWeight($rec)
    {
        //id на мярката 'килограм'
        $kgMeasureId = cat_UoM::getQuery()->fetch("#name = 'килограм'")->id;

        //Взема единичното тегло на целия продукт
        $singleProductWeight = null;
        $singleProductWeight = cat_Products::getParams($rec->id, 'weight');

        if ($singleProductWeight) {
            $singleProductWeight = $singleProductWeight / 1000;
        } else {
            $singleProductWeight = cat_Products::getParams($rec->id, 'weightKg');
        }

        if ($rec->measureId == $kgMeasureId) {
            $singleProductWeight = 1;
        }

        $singleProductWeight = $singleProductWeight ? $singleProductWeight : 'n.a.';

        return $singleProductWeight;
    }
}
