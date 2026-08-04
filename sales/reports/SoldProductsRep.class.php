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
     * Допълва липсващите параметри в нови и стари записи на справката
     */
    protected static function applyRecDefaults($rec)
    {
        $defaults = array(
            'compare' => 'no',
            'typeOfGroups' => 'art',
            'articleType' => 'all',
            'grouping' => 'no',
            'seeByContragent' => 'no',
            'seeCategory' => 'no',
            'engName' => 'no',
            'seeDelta' => 'no',
            'seeWeight' => 'no',
            'quantityType' => 'shipped',
            'primeCostType' => 'standartPrimeCost',
            'orderBy' => 'primeCost',
            'order' => 'desc',
            'group' => null,
            'category' => null,
            'products' => null,
            'contragent' => null,
            'crmGroup' => null,
            'dealers' => null,
            'dealersTeam' => null,
            'currency' => null,
            'grFilter' => null,
        );

        foreach ($defaults as $name => $value) {
            if (!isset($rec->{$name})) {
                $rec->{$name} = $value;
            }
        }
    }


    /**
     * Уеднаквява редовете от заявките за експедирани, поръчани и фактурирани артикули
     */
    protected static function applyDataRecDefaults($rec)
    {
        $defaults = array(
            'category' => null,
            'code' => null,
            'containerId' => null,
            'contragentClassId' => null,
            'contragentId' => null,
            'delta' => 0,
            'detailClassId' => null,
            'discount' => 0,
            'folderId' => null,
            'groupList' => null,
            'groupMat' => null,
            'price' => 0,
            'productId' => null,
            'productIsPublic' => null,
            'productMeasureId' => null,
            'quantity' => 0,
            'quantityInPack' => 1,
            'sellCost' => 0,
            'type' => null,
            'valior' => null,
        );

        foreach ($defaults as $name => $value) {
            if (!isset($rec->{$name})) {
                $rec->{$name} = $value;
            }
        }
    }

    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, planning, sales, debug';


    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields = 'quantity';

    /**
     * Кои полета от таблицата в справката да се сумират в обобщаващия ред
     *
     * @var int
     */
    protected $summaryListFields= 'primeCost,changeSales,invAmount,delta';


    /**
     * Как да се казва обобщаващия ред. За да се покаже трябва да е зададено $summaryListFields
     *
     * @var int
     */
    protected $summaryRowCaption = 'ОБЩО [EUR]';


    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck = 'quantity,primeCost';

    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'from,to,compare,firstMonth,secondMonth,group,dealers,dealersTeam,contragent,crmGroup,articleType,seeDelta,orderBy,order,grouping,updateDays,updateTime,products';


    /**
     * Кои полета са за избор на период
     */
    protected $periodFields = 'from,to';


    /**
     * Връща обхвата на достъп до търговци и екипи за потребителя
     *
     * Използва saleAllGlobal за всички търговци и saleAll за екипите на потребителя.
     * Резултатът се използва едновременно за опциите във формата и за сървърния филтър в prepareRecs().
     *
     * @param int|null $userId
     *
     * @return array
     */
    public static function getDealerAccessScope($userId = null)
    {
        $userId = isset($userId) ? $userId : core_Users::getCurrent();

        $res = array(
            'canSeeAll' => false,
            'canSeeTeams' => false,
            'allowedDealers' => array(),
            'allowedTeams' => array(),
        );

        if (haveRole('ceo, saleAllGlobal', $userId)) {
            $res['canSeeAll'] = true;
            $res['allowedDealers'] = self::getAllDealers();   // ползва се само за dropdown-а с търговци
            $res['allowedTeams'] = keylist::toArray(core_Roles::getRolesByType('team'));

            return $res;
        }

        if (haveRole('saleAll', $userId)) {
            $res['canSeeTeams'] = true;
            $res['allowedTeams'] = keylist::toArray(core_Users::getUserRolesByType($userId, 'team'));

            if (!empty($res['allowedTeams'])) {
                $res['allowedDealers'] = self::getUsersByTeams($res['allowedTeams']);
            }

            return $res;
        }

        $res['allowedDealers'] = array($userId => $userId);

        return $res;
    }


    /**
     * Връща всички потребители, които реално се срещат като dealerId в данните на справката
     *
     * Списъкът не се базира на роли, а на реални продажбени записи.
     *
     * @return array
     */
    protected static function getAllDealers()
    {
        $res = array();

        $primeCostQuery = sales_PrimeCostByDocument::getQuery();
        $primeCostQuery->where('#dealerId IS NOT NULL');
        $primeCostQuery->groupBy('dealerId');
        $primeCostQuery->show('dealerId');
        while ($primeCostRec = $primeCostQuery->fetch()) {
            if ($primeCostRec->dealerId > 0) {
                $res[$primeCostRec->dealerId] = $primeCostRec->dealerId;
            }
        }

        $salesQuery = sales_Sales::getQuery();
        $salesQuery->where('#dealerId IS NOT NULL');
        $salesQuery->groupBy('dealerId');
        $salesQuery->show('dealerId');
        while ($salesRec = $salesQuery->fetch()) {
            if ($salesRec->dealerId > 0) {
                $res[$salesRec->dealerId] = $salesRec->dealerId;
            }
        }

        return $res;
    }


    /**
     * Връща потребителите от подадените екипи
     *
     * Използва се при потребители с достъп само до собствените им екипи.
     *
     * @param array|null $teams
     *
     * @return array
     */
    protected static function getUsersByTeams($teams)
    {
        $res = array();
        if (empty($teams)) {

            return $res;
        }

        $teamsKeylist = keylist::fromArray($teams);

        $usersQuery = core_Users::getQuery();
        $usersQuery->where("#state != 'rejected' AND #state != 'draft'");
        $usersQuery->likeKeylist('roles', $teamsKeylist);

        while ($userRec = $usersQuery->fetch()) {
            $res[$userRec->id] = $userRec->id;
        }

        return $res;
    }


    /**
     * Връща възможните групи/категории от текущите резултати на справката
     *
     * @param stdClass $rec
     *
     * @return array
     */
    protected static function getGroupFilterSuggestions($rec)
    {
        self::applyRecDefaults($rec);

        $suggestions = array();

        if (empty($rec->data->recs) || !is_array($rec->data->recs)) {

            return $suggestions;
        }

        if ($rec->typeOfGroups == 'category' || $rec->typeOfGroups == 'no') {
            foreach ($rec->data->recs as $dRec) {
                $categoryId = isset($dRec->category) ? $dRec->category : $dRec->group;

                if (is_numeric($categoryId) && $categoryId != 99999) {
                    $categoryRec = cat_Categories::fetch($categoryId);
                    if ($categoryRec) {
                        $suggestions[$categoryRec->id] = $categoryRec->name;
                    }
                }
            }

            return $suggestions;
        }

        foreach ($rec->data->recs as $dRec) {
            if (!isset($dRec->group)) {
                continue;
            }

            if (keylist::isKeylist($dRec->group)) {
                $groupIds = keylist::toArray($dRec->group);
            } elseif (is_array($dRec->group ?? null)) {
                $groupIds = $dRec->group;
            } elseif (is_numeric($dRec->group)) {
                $groupIds = array($dRec->group => $dRec->group);
            } else {
                $groupIds = array();
            }

            foreach ($groupIds as $groupId) {
                if (!is_numeric($groupId)) {
                    continue;
                }

                $groupRec = cat_Groups::fetch($groupId);
                if (!$groupRec) {
                    continue;
                }

                $groupsWithParents = cls::get('cat_Groups')->getParentsArray($groupId);
                foreach ($groupsWithParents as $suggestionId) {
                    $suggestionRec = cat_Groups::fetch($suggestionId);
                    if ($suggestionRec) {
                        $suggestions[$suggestionRec->id] = $suggestionRec->name;
                    }
                }
            }
        }

        return $suggestions;
    }


    /**
     * Добавя филтър за артикули, които не са в избраните групи
     *
     * @param core_Query $query
     * @param string $field
     * @param string $groups
     */
    protected static function applyNotInGroupsFilter($query, $field, $groups)
    {
        $groupsArr = keylist::toArray($groups);
        if (empty($groupsArr)) {

            return;
        }

        $notInGroupsCond = '';
        foreach ($groupsArr as $groupId) {
            $groupId = (int) $groupId;
            $notInGroupsCond .= ($notInGroupsCond ? ' AND ' : '') . "LOCATE('|{$groupId}|', #{$field}) = 0";
        }

        $query->where("(#{$field} IS NULL OR #{$field} = '' OR ({$notInGroupsCond}))");
    }


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

        $fieldset->FLD('firstMonth', 'key(mvc=acc_Periods,select=title)', 'caption=Месец 1,after=to,removeAndRefreshForm,single=none,input=none,silent');
        $fieldset->FLD('secondMonth', 'key(mvc=acc_Periods,select=title)', 'caption=Месец 2,after=firstMonth,removeAndRefreshForm,single=none,input=none,silent');

    //   $fieldset->FLD('dealers', 'userlist(rolesForAll=ceo|repAllGlobal, rolesForTeams=ceo|manager|repAll|repAllGlobal)', 'caption=Търговци,single=none,after=to,silent,mandatory');
        $fieldset->FLD('dealers', 'keylist(mvc=core_Users,select=names)', 'caption=Търговци->Търговец,placeholderType=all,after=secondMonth,single=none');
        $fieldset->FLD('dealersTeam', 'keylist(mvc=core_Roles,select=role,allowEmpty)', 'caption=Търговци->Екип,placeholderType=all,after=dealers,single=none');

        $fieldset->FLD('contragent', 'keylist(mvc=doc_Folders,select=title,allowEmpty)', 'caption=Контрагенти->Контрагент,placeholderType=all,single=none,after=dealersTeam');
        $fieldset->FLD('crmGroup', 'keylist(mvc=crm_Groups,select=name)', 'caption=Контрагенти->Група контрагенти,placeholderType=all,after=contragent,single=none');

        $fieldset->FLD('typeOfGroups', 'enum(no=Всички групи/категории, category=Категории артикули, art=Групи артикули, nogrp=Изключи избраните групи артикули)', 'caption=Артикули->Филтър по,removeAndRefreshForm,after=crmGroup');
        $fieldset->FLD('category', 'keylist(mvc=cat_Categories,select=name)', 'caption=Артикули->Категории артикули,after=typeOfGroups,removeAndRefreshForm,placeholderType=all,silent,single=none');
        $fieldset->FLD('group', 'keylist(mvc=cat_Groups,select=name)', 'caption=Артикули->Групи артикули,after=category,removeAndRefreshForm,placeholderType=all,silent,single=none');
        $fieldset->FLD('products', 'keylist(mvc=cat_Products,select=name)', 'caption=Артикули->Артикули,placeholderType=all,after=group,single=none,input=none,class=w100');
        $fieldset->FLD('articleType', 'enum(yes=Стандартни,no=Нестандартни,all=Всички)', 'caption=Артикули->Тип артикули,maxRadio=3,columns=3,after=productId,single=none');
        $fieldset->FLD('quantityType', 'enum(shipped=Експедирани, ordered=Поръчани,invoiced=Фактурирано)', 'caption=Артикули->Количества,removeAndRefreshForm,silent,after=articleType');

        //Покаване на резултата
        $fieldset->FLD('grouping', 'enum(yes=По групи, no=По артикули)', 'caption=Показване->Вид,removeAndRefreshForm,after=quantityType');
        $fieldset->FLD('currency', 'key(mvc=currency_Currencies,select=code,allowEmpty)', 'caption=Показване->Валута,removeAndRefreshForm,single=none,after=grouping,placeholder=Основна');
        $fieldset->FLD('seeByContragent', 'enum(yes=ДА, no=НЕ)', 'caption=Показване->По контрагенти,after=currency,removeAndRefreshForm,single=none,silent');
        $fieldset->FLD('seeCategory', 'enum(yes=ДА, no=НЕ)', 'caption=Показване->Покажи категория,after=seeByContragent,single=none,silent');

        $fieldset->FLD('engName', 'enum(yes=ДА, no=НЕ)', 'caption=Показване->Име EN,after=seeByContragent,single=none');

        //if(haveRole('sdf')){
            $fieldset->FLD('seeDelta', 'enum(yes=ДА, no=НЕ)', 'caption=Показване->Покажи делти,after=engName,single=none');
       // }

        $fieldset->FLD('seeWeight', 'enum(yes=ДА, no=НЕ)', 'caption=Показване->Покажи тегло,after=seeDelta,single=none');
        $fieldset->FLD('primeCostType', 'enum(standartPrimeCost=Стандартна, dealerPrimeCost=Дилърска)', 'caption=Показване->Себестойност,after=seeWeight,single=none');


        //Подредба на резултатите
        $fieldset->FLD('orderBy', 'enum(code=Код, primeCost=Продажби ст., quantity=Продажби кол., group=Групи, delta=Делти, changeDelta=Промяна Делти, changeCost=Промяна Стойност)', 'caption=Подреждане на резултата->Показател,maxRadio=7,columns=3,after=primeCostType');
        $fieldset->FLD('order', 'enum(desc=Низходящо, asc=Възходящо)', 'caption=Подреждане на резултата->Ред,maxRadio=2,after=orderBy,single=none');

        $fieldset->FNC('button', 'varchar', 'caption=Бутон,input=none,single=none');
        $fieldset->FNC('exportFilter', 'varchar', 'caption=Експорт филтър,input=none,single=none');
        $fieldset->FNC('grFilter', 'varchar', 'caption=Филтър по група,input=none,single=none');
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

            if (($form->rec->compare ?? 'no') != 'month' && (empty($form->rec->from) || empty($form->rec->to))) {
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

            if (($form->rec->orderBy == 'quantity') && ($form->rec->grouping == 'yes')) {
                $form->setError('orderBy', 'При ГРУПИРАНО показване не може да има подредба по КОЛИЧЕСТВО.');
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
        self::applyRecDefaults($rec);
        if (date('d') < 10) {
            $form->setDefault('selectPeriod', 'last_month');
        } else {
            $form->setDefault('selectPeriod', 'cur_month');
        }


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
        } elseif (($rec->typeOfGroups == 'art') || ($rec->typeOfGroups == 'nogrp')) {
            $form->setField('category', 'input=hidden');
        } elseif ($rec->typeOfGroups == 'no') {
            $form->setField('category', 'input=hidden');
            $form->setField('group', 'input=hidden');
        }

        $form->input('selectPeriod,from,to',true);
        $currentPeriod = acc_Periods::fetchByDate(dt::today());
        $periodStart = $rec->from ?? $currentPeriod->start;
        $periodEnd = $rec->to ?? $currentPeriod->end;

        $monthSugg = $currentPeriod->id;

        $form->setDefault('firstMonth', $monthSugg);
        $form->setDefault('secondMonth', $monthSugg);


        if ($rec->compare == 'month') {
            $firstMonth = acc_Periods::fetch($rec->firstMonth ?? $monthSugg);
            $secondMonth = acc_Periods::fetch($rec->secondMonth ?? $monthSugg);
            $periodStart = $firstMonth->start;
            $periodEnd = $secondMonth->end;

            $periodStart1 = $secondMonth->start;
            $periodEnd1 = $secondMonth->end;
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

        $form->setDefault('primeCostType', 'standartPrimeCost');

        if ($rec->quantityType == 'invoiced') {

            $form->setField('dealers', 'input=none');
            $form->setField('dealersTeam', 'input=none');
        }

        // POS продажби
        //Подготвям масиви с артикули, и контрагенти
        $posProdsArr = $posContragents = array();

        $posDetQuery = pos_ReceiptDetails::getQuery();

        $posDetQuery->EXT('state', 'pos_Receipts', 'externalName=state,externalKey=receiptId');

        $posDetQuery->EXT('valior', 'pos_Receipts', 'externalName=valior,externalKey=receiptId');

        $posDetQuery->EXT('contragentClass', 'pos_Receipts', 'externalName=contragentClass,externalKey=receiptId');

        $posDetQuery->EXT('contragentObjectId', 'pos_Receipts', 'externalName=contragentObjectId,externalKey=receiptId');

        $posDetQuery->EXT('contragentName', 'pos_Receipts', 'externalName=contragentName,externalKey=receiptId');

        $posDetQuery->where("#valior >= '{$periodStart}' AND #valior <= '{$periodEnd}'");

        $posDetQuery->where('#productId IS NOT NULL');

        $posDetStateArr = array('active', 'closed', 'waiting');

        $posDetQuery->in('state', $posDetStateArr);

        $posDetQuery->show('productId,receiptId,contragentObjectId,contragentClass,contragentName');

        foreach ($posDetQuery->fetchAll() as $det) {

            $posProdsArr[$det->productId] = $det->productId;

            $posContragentClassName = core_Classes::fetch($det->contragentClass)->name;

            $posContragentFolder = $posContragentClassName::fetch($det->contragentObjectId)->folderId;

            $posContragents[$posContragentFolder] = $det->contragentName;

        }

        //Ако имаме разбивка по контрагенти
        if ($rec->seeByContragent == 'yes') {
            $form->setField('products', 'input');

            //Подготовка на масива за зареждане на полето 'АРТИКУЛИ'
            //Полето 'АРТИКУЛИ' е активно само в комбинация с полето 'ПО КОНТРАГЕНТИ'

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

            //Добавяме артикулите от бързите продажби
            if(!empty($prodSalesArr)) {
                $prodArr = array_unique(array_merge($prodArr, $prodSalesArr));
            }
            //Добавяме артикулите от POS продажбите
            if(!empty($posProdsArr)){
                $prodArr = array_unique(array_merge($prodArr, $posProdsArr));
            }

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

        //Масив с предложения за избор на КОНТРАГЕНТ $suggestionContragents[]
        // Да се заредят контрагентите от продажбите
        $salesQuery = sales_Sales::getQuery();

        $salesQuery->EXT('folderTitle', 'doc_Folders', 'externalName=title,externalKey=folderId');

     //   $salesQuery->where("#valior >= '{$periodStart}' AND #valior <= '{$periodEnd}'");

        $salesQuery->groupBy('folderId');

        $salesQuery->show('folderId, contragentId, folderTitle');
        $suggestionContragents = array();
        while ($contragent = $salesQuery->fetch()) {
            if (!is_null($contragent->contragentId)) {
                $suggestionContragents[$contragent->folderId] = $contragent->folderTitle;
            }
        }


        if (empty($posContragents)) {

            // контрагенти от POS
            foreach ($posDetQuery->fetchAll() as $det) {

                $posContragentClassName = core_Classes::fetch($det->contragentClass)->name;

                $posContragentFolder = $posContragentClassName::fetch($det->contragentObjectId)->folderId;

                $posContragents[$posContragentFolder] = $det->contragentName;

            }

        }
        // Да се заредят контрагентите от POS  бележките
        //$suggestionContragents = $posContragents;

        $suggestionContragents = $suggestionContragents+$posContragents;

        asort($suggestionContragents);

        $form->setSuggestions('contragent', $suggestionContragents);

        // Ограничаваме опциите във формата според правата на текущия потребител
        $dealerAccessScope = self::getDealerAccessScope(core_Users::getCurrent());

        $suggestionDealers = array();
        foreach ($dealerAccessScope['allowedDealers'] as $dealerId) {
            $suggestionDealers[$dealerId] = core_Users::fetchField($dealerId, 'nick');
        }
        asort($suggestionDealers);

        $form->setSuggestions('dealers', $suggestionDealers);

        $suggestionTeams = array();
        foreach ($dealerAccessScope['allowedTeams'] as $teamId) {
            $teRec = core_Roles::fetch($teamId);
            if ($teRec) {
                $suggestionTeams[$teRec->id] = $teRec->role;
            }
        }
        asort($suggestionTeams);

        $form->setSuggestions('dealersTeam', $suggestionTeams);
        if (empty($suggestionTeams)) {
            $form->setField('dealersTeam', 'input=none');
        }

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
        self::applyRecDefaults($rec);

        //Код и Id  на основната валута в края на периода
        $baseCurrency = acc_Periods::getBaseCurrencyCode($rec->to);
        $baseCurrencyId = currency_Currencies::getIdByCode($baseCurrency);

        //При групиране по кои групи да работи: групи артикули или категории артикули
        if ($rec->typeOfGroups == 'art' || $rec->typeOfGroups == 'nogrp') {
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
        if (($rec->grouping == 'no') && ($rec->typeOfGroups != 'nogrp') && ($rec->group || $rec->category)) {
            if ($rec->typeOfGroups == 'art' || $rec->typeOfGroups == 'nogrp') {
                $groupByField = 'group';
            } elseif (($rec->typeOfGroups == 'category')) {
                $groupByField = 'category';
            }
            $this->groupByField = $groupByField;
        }

        if ($rec->seeByContragent == 'yes') {
            $this->groupByField = 'contragentName';
        }

        $recs = $invProd = array();

        // Обхват по права на СЪЗДАТЕЛЯ на справката — ограничава данните по търговец (не по текущия потребител).
        $scope = self::getDealerAccessScope($rec->createdBy ?? core_Users::getCurrent());


        //Ако има избрано разбивка "Артикули по контрагент"
        //Подготвяме масив с фактурираните артикули през избрания период
        //разбити по контрагент
        if ($rec->seeByContragent == 'yes') {

            $invDetQuery = self::getInvoicedProducts($rec);

            $invDetQuery->where("#state = 'active'");

            $invDetQuery->where(array("#date >= '[#1#]' AND #date <= '[#2#]'", $rec->from, $rec->to));

            self::applyInvoiceDealerScope($invDetQuery, $scope);

            while ($invDetRec = $invDetQuery->fetch()) {
                self::applyDataRecDefaults($invDetRec);

                $invQuantity = $discount = $invAmount = 0;
                $originQuantity = $changeQuatity = 0;

                //Превалутиране на сумите
                $invDetRec->price = deals_Helper::getSmartBaseCurrency($invDetRec->price, $invDetRec->date, $rec->to);
                $invDetRec->discount = deals_Helper::getSmartBaseCurrency($invDetRec->discount, $invDetRec->date, $rec->to);

                //Ключ на масива
                $id = $invDetRec->productId . ' | ' . $invDetRec->folderId . ' | ' . $invDetRec->folderId;

                $invQuantity = $invDetRec->quantity * $invDetRec->quantityInPack;
                $discount = $invDetRec->price * $invQuantity * $invDetRec->discount;
                $invAmount = ($invDetRec->price * $invQuantity) - $discount;

                //Ако фактурата е дебитно или кредитно известие с промяна в артикулите
                if ($invDetRec->type == 'dc_note') {

                    $correctionArray = self::dcNoteCorrection($invDetRec, $rec);

                    if (empty($correctionArray)) {
                        continue;
                    }

                    $invQuantity = $correctionArray['quantity'];
                    $invAmount = $correctionArray['amount'];   //превалутирано в метода

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

            $query->EXT('containerId', 'sales_Sales', 'externalName=containerId,externalKey=saleId');

            $query->EXT('valior', 'sales_Sales', 'externalName=valior,externalKey=saleId');

            $query->EXT('dealerId', 'sales_Sales', 'externalName=dealerId,externalKey=saleId');

            $query->EXT('contragentClassId', 'sales_Sales', 'externalName=contragentClassId,externalKey=saleId');

            $query->EXT('contragentId', 'sales_Sales', 'externalName=contragentId,externalKey=saleId');

            $query->EXT('folderId', 'sales_Sales', 'externalName=folderId,externalKey=saleId');

            $query->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');

        } elseif ($rec->quantityType == 'invoiced') {

            $query = self::getInvoicedProducts($rec);

        }

        $query->EXT('groupMat', 'cat_Products', 'externalName=groups,externalKey=productId');

        $query->EXT('prodFolderId', 'cat_Products', 'externalName=folderId,externalKey=productId');

        $query->EXT('category', 'doc_Folders', 'externalName=coverId,externalKey=prodFolderId');

        $query->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');

        $query->EXT('productMeasureId', 'cat_Products', 'externalName=measureId,externalKey=productId');

        $query->EXT('productIsPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');

        $query->in('state', array('rejected', 'stopped', 'draft'), true);

        if ($rec->grFilter) {
            if ($rec->typeOfGroups == 'art' || $rec->typeOfGroups == 'nogrp') {
                $filterGroups = cat_Groups::getDescendantArray($rec->grFilter);
                $filterGroups = !empty($filterGroups) ? $filterGroups : array($rec->grFilter => $rec->grFilter);
                if ($rec->typeOfGroups == 'nogrp') {
                    self::applyNotInGroupsFilter($query, 'groupMat', keylist::fromArray($filterGroups));
                } else {
                    plg_ExpandInput::applyExtendedInputSearch('cat_Products', $query, keylist::fromArray($filterGroups), 'productId');
                }
            } elseif ($rec->typeOfGroups == 'category') {
                $query->where("#category = {$rec->grFilter}");
            }
        }

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


        // Сървърна защита на филтъра за дилър (обхватът $scope е изчислен по-горе).
        if ($rec->quantityType != 'invoiced') {
            // shipped / ordered → заявката има dealerId
            $dealersArr = [];

            // Ръчно избрани търговци (поле dealers)
            if (!empty($rec->dealers)) {
                $dealersArr = keylist::toArray($rec->dealers);
            }

            // Ръчно избрани екипи (поле dealersTeam)
            if (!empty($rec->dealersTeam)) {
                foreach (self::getUsersByTeams(keylist::toArray($rec->dealersTeam)) as $userId) {
                    $dealersArr[$userId] = $userId;
                }
            }

            if ($scope['canSeeAll']) {
                // Вижда всичко → ограничаваме само ако сам е избрал търговци
                if (!empty($dealersArr)) {
                    $query->in('dealerId', $dealersArr);
                }
            } else {
                // Ограничени права → само разрешените търговци
                $dealersArr = !empty($dealersArr)
                    ? array_intersect_key($dealersArr, $scope['allowedDealers'])
                    : $scope['allowedDealers'];

                // Празно = "нищо". core_Query::in([]) не добавя условие и би показало всичко.
                if (empty($dealersArr)) {
                    $query->where('1=2');
                } else {
                    $query->in('dealerId', $dealersArr);
                }
            }
        } else {
            // invoiced → фактурите нямат dealerId; атрибуцията към търговец е по нишката на сделката.
            self::applyInvoiceDealerScope($query, $scope);
        }

        //Филтър за КОНТРАГЕНТ и ГРУПИ КОНТРАГЕНТИ
        if ($rec->contragent || $rec->crmGroup) {

            $contragentsArr = array();

            foreach (keylist::toArray($rec->contragent) as $contragent) {

                $Cover = doc_Folders::getCover($contragent);
                $contragentsArr[] = [$Cover->getClassId(), $Cover->that];

            }

            if (!$rec->crmGroup && $rec->contragent) {

                // Генерираме частта от заявката, която съдържа IN условието
                $in_clause = implode(", ", array_map(function ($pair) {
                    return "('" . $pair[0] . "', '" . $pair[1] . "')";
                }, $contragentsArr));

                // Създаваме SQL заявка
                $query->where("(#contragentClassId, #contragentId) IN ($in_clause)");

            }

            if ($rec->crmGroup && !$rec->contragent) {
                $contragentsInGroupFoldersArr = self::getContragentsInGroups($rec);
                $contragentsInGroup = array();

                foreach ($contragentsInGroupFoldersArr as $contragent) {

                    $Cover = doc_Folders::getCover($contragent);
                    $contragentsArr[] = [$Cover->getClassId(), $Cover->that];

                }

                // Генерираме частта от заявката, която съдържа IN условието
                $in_clause = implode(", ", array_map(function ($pair) {
                    return "('" . $pair[0] . "', '" . $pair[1] . "')";
                }, $contragentsArr));

                // Създаваме SQL заявка
                $query->where("(#contragentClassId, #contragentId) IN ($in_clause)");

            }

            if ($rec->crmGroup && $rec->contragent) {
                $contragentsInGroupFoldersArr = self::getContragentsInGroups($rec);

                foreach ($contragentsInGroupFoldersArr as $contragent) {

                    $Cover = doc_Folders::getCover($contragent);
                    $contragentsInGroup[] = [$Cover->getClassId(), $Cover->that];

                    // $contragentsIdArr[$Cover->getClassId()][$Cover->that] = $Cover->that;
                }
                if (is_array($contragentsInGroup) && is_array($contragentsArr)) {
                    $contragentsArr = array_merge($contragentsArr, $contragentsInGroup);
                }

                // Премахване на дублиращите се двойки
                $unique = [];
                foreach ($contragentsArr as $pair) {
                    $key = $pair[0] . '-' . $pair[1]; // Ключ от двете стойности
                    $unique[$key] = $pair; // Добавяне на уникална двойка
                }

                // Преобразуване обратно в нормален масив
                $contragentsArr = array_values($unique);

                // Генерираме частта от заявката, която съдържа IN условието
                $in_clause = implode(", ", array_map(function ($pair) {
                    return "('" . $pair[0] . "', '" . $pair[1] . "')";
                }, $contragentsArr));

                // Създаваме SQL заявка
                $query->where("(#contragentClassId, #contragentId) IN ($in_clause)");

            }
        }

        //Филтър за АРТИКУЛ и ГРУПИ АРТИКУЛИ

        if ($rec->typeOfGroups == 'art' || $rec->typeOfGroups == 'nogrp') {
            $filterGroupsType = 'group';
        } elseif ($rec->typeOfGroups == 'category') {
            $filterGroupsType = 'category';
        } elseif ($rec->typeOfGroups == 'no') {
            $filterGroupsType = 'category';
        }
        $checkFieldName = ($filterGroupsType == 'group') ? 'groupMat' : 'category';

        if ($rec->typeOfGroups == 'nogrp' && !$rec->$filterGroupsType) {
            $query->where("(#{$checkFieldName} IS NULL OR #{$checkFieldName} = '')");
        }

        if ($rec->products || $rec->$filterGroupsType) {
            $prodsArr = array();

            if (!$rec->$filterGroupsType && $rec->products) {
                $prodsArr = keylist::toArray($rec->products);
                $query->in('productId', $prodsArr);
            }

            if ($rec->$filterGroupsType && !$rec->products) {
                if ($rec->typeOfGroups == 'nogrp') {
                    self::applyNotInGroupsFilter($query, $checkFieldName, $rec->$filterGroupsType);
                } elseif ($filterGroupsType == 'group') {
                    $query->likeKeylist($checkFieldName, $rec->$filterGroupsType);
                } else {
                    $filterGroupsArr = keylist::toArray($rec->$filterGroupsType);
                    $query->in($checkFieldName, $filterGroupsArr);
                }
            }

            if ($rec->$filterGroupsType && $rec->products) {
                $prodsArr = keylist::toArray($rec->products);
                $query->in('productId', $prodsArr);
                if ($rec->typeOfGroups == 'nogrp') {
                    self::applyNotInGroupsFilter($query, $checkFieldName, $rec->$filterGroupsType);
                } else {
                    $query->orLikeKeylist($checkFieldName, $rec->$filterGroupsType);
                }
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

        $productsCache = array();
        $foldersTitleCache = array();
        $classesCache = array();
        $documentsCache = array();
        $posContragentCache = array();

        while ($recPrime = $query->fetch()) {
            self::applyDataRecDefaults($recPrime);

            if (empty($recPrime->productId) || empty($recPrime->containerId)) {
                continue;
            }
            if ($rec->quantityType == 'shipped' && empty($recPrime->detailClassId)) {
                continue;
            }

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

            $categoryId = $recPrime->category;

            // Данните за артикула вече са взети с EXT, за да няма fetch за всеки ред
            if (!isset($productsCache[$recPrime->productId])) {
                $productsCache[$recPrime->productId] = (object)array(
                    'measureId' => $recPrime->productMeasureId,
                    'isPublic' => $recPrime->productIsPublic,
                );
            }
            $prodRec = $productsCache[$recPrime->productId];

            //Ключ на масива
            $id = ($rec->seeByContragent == 'yes') ? $recPrime->productId . ' | ' . $recPrime->folderId . ' | ' . $recPrime->folderId : $recPrime->productId;
            if (!isset($documentsCache[$recPrime->containerId])) {
                $documentsCache[$recPrime->containerId] = doc_Containers::getDocument($recPrime->containerId);
            }
            $Doc = $documentsCache[$recPrime->containerId];
            $poscontragentClassId = $poscontragentId = null;
            if ($Doc->isInstanceOf('pos_Reports')) {

                $poscontragentClassId = $recPrime->contragentClassId;
                $poscontragentId = $recPrime->contragentId;

                if (!isset($classesCache[$recPrime->contragentClassId])) {
                    $classesCache[$recPrime->contragentClassId] = core_Classes::fetch($recPrime->contragentClassId)->name;
                }
                $posContragentClassName = $classesCache[$recPrime->contragentClassId];

                $posContragentKey = $recPrime->contragentClassId . '|' . $recPrime->contragentId;
                if (!isset($posContragentCache[$posContragentKey])) {
                    $posContragentCache[$posContragentKey] = $posContragentClassName::fetch($recPrime->contragentId)->folderId;
                }
                $posContragentFolder = $posContragentCache[$posContragentKey];

                if (!isset($foldersTitleCache[$posContragentFolder])) {
                    $foldersTitleCache[$posContragentFolder] = doc_Folders::getTitleById($posContragentFolder);
                }
                $contragentName = $foldersTitleCache[$posContragentFolder];
                $posKey = $recPrime->contragentClassId . '|' . $recPrime->contragentId;

                $id = ($rec->seeByContragent == 'yes') ? $recPrime->productId . ' | ' . $recPrime->folderId . ' | ' . $posKey : $recPrime->productId;

            } else {

                if (!isset($foldersTitleCache[$recPrime->folderId])) {
                    $foldersTitleCache[$recPrime->folderId] = doc_Folders::getTitleById($recPrime->folderId);
                }
                $contragentName = $foldersTitleCache[$recPrime->folderId];
            }


            //Код на артикула
            $artCode = $recPrime->code ? $recPrime->code : "Art{$recPrime->productId}";

            //Мярка на артикула
            $measureArt = $prodRec->measureId;

            $conf = core_Packs::getConfig('sales');
            $deltaMinCoef = (!is_numeric($conf->SALES_DELTA_MIN_PERCENT)) ? 0 : $conf->SALES_DELTA_MIN_PERCENT;

            //Данни за ПРЕДХОДЕН ПЕРИОД или МЕСЕЦ
            if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                if ($recPrime->valior >= $fromPreviuos && $recPrime->valior <= $toPreviuos) {

                    if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {

                        //Превалутиране
                        $pricePr = deals_Helper::getSmartBaseCurrency($recPrime->{"{$price}"}, $recPrime->valior, $rec->to);
                        $recPrime->delta = deals_Helper::getSmartBaseCurrency($recPrime->delta, $recPrime->valior, $rec->to);

                        $quantityPrevious = (-1) * $recPrime->quantity;
                        $primeCostPrevious = (-1) * $pricePr * $recPrime->quantity;

                        $deltaPrevious = (-1) * $recPrime->delta;

                    } elseif ($DetClass instanceof sales_SalesDetails || $DetClass instanceof store_ShipmentOrderDetails || $DetClass instanceof pos_Reports) {
                        $quantityPrevious = $recPrime->quantity;

                        //Превалутиране
                        $pricePr = deals_Helper::getSmartBaseCurrency($recPrime->{"{$price}"}, $recPrime->valior, $rec->to);
                        $primeCostPrevious = $pricePr * $recPrime->quantity;


                        //Ако е избрана Дилърска себестойност, и делтата е отрицателна,
                        // приемаме че, себестойността е 95% от продажната цена
                        if ($rec->primeCostType == 'dealerPrimeCost' && $recPrime->delta <= 0 && $prodRec->isPublic == 'no') {

                            //Превалутиране
                            $recPrime->sellCost = deals_Helper::getSmartBaseCurrency($recPrime->sellCost, $recPrime->valior, $rec->to);

                            $deltaPrevious = $recPrime->sellCost * $deltaMinCoef * $recPrime->quantity * 1.95583;
                        } else {

                            //Превалутиране
                            $recPrime->delta = deals_Helper::getSmartBaseCurrency($recPrime->delta, $recPrime->valior, $rec->to);

                            $deltaPrevious = $recPrime->delta ;
                        }


                    } elseif ($DetClass instanceof sales_InvoiceDetails) {

                        if ($recPrime->type == 'invoice') {

                            $quantityPrevious = $recPrime->quantity * $recPrime->quantityInPack;

                            //Превалутиране
                            $recPrime->price = deals_Helper::getSmartBaseCurrency($recPrime->price, $recPrime->valior, $rec->to);
                            $recPrime->discount = deals_Helper::getSmartBaseCurrency($recPrime->discount, $recPrime->valior, $rec->to);

                            $discount = $recPrime->price * $quantityPrevious * $recPrime->discount;

                            $primeCostPrevious = ($recPrime->price * $quantityPrevious) - $discount;

                        } elseif ($recPrime->type == 'dc_note') {

                            $correctionArray = self::dcNoteCorrection($recPrime, $rec);

                            if (empty($correctionArray)) {
                                continue;
                            }

                            $quantityPrevious = $correctionArray['quantity'];
                            $primeCostPrevious = $correctionArray['amount']; //превалутирано в метода

                        }
                    }
                }
            }

            //Данни за ПРЕДХОДНА ГОДИНА
            if ($rec->compare == 'year') {
                if ($recPrime->valior >= $fromLastYear && $recPrime->valior <= $toLastYear) {

                    if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails ) {

                        //Превалутиране
                        $pricePr = deals_Helper::getSmartBaseCurrency($recPrime->{"{$price}"}, $recPrime->valior, $rec->to);
                        $recPrime->delta = deals_Helper::getSmartBaseCurrency($recPrime->delta, $recPrime->valior, $rec->to);

                        $quantityLastYear = (-1) * $recPrime->quantity;
                        $primeCostLastYear = (-1) * $pricePr * $recPrime->quantity;
                        $deltaLastYear = (-1) * $recPrime->delta;

                    } elseif ($DetClass instanceof sales_SalesDetails || $DetClass instanceof store_ShipmentOrderDetails || $DetClass instanceof pos_Reports) {

                        //Превалутиране
                        $pricePr = deals_Helper::getSmartBaseCurrency($recPrime->{"{$price}"}, $recPrime->valior, $rec->to);

                        $quantityLastYear = $recPrime->quantity;
                        $primeCostLastYear = $pricePr * $recPrime->quantity ;

                        //Ако е избрана Дилърска себестойност, и делтата е отрицателна,
                        // приемаме че, себестойността е 95% от продажната цена
                        if ($rec->primeCostType == 'dealerPrimeCost' && $recPrime->delta <= 0 && $prodRec->isPublic == 'no') {

                            //Превалутиране
                            $recPrime->sellCost = deals_Helper::getSmartBaseCurrency($recPrime->sellCost, $recPrime->valior, $rec->to);

                            $deltaLastYear = $recPrime->sellCost * $deltaMinCoef * $recPrime->quantity * 1.95583;
                        } else {

                            //Превалутиране
                            $recPrime->delta = deals_Helper::getSmartBaseCurrency($recPrime->delta, $recPrime->valior, $rec->to);
                           $deltaLastYear = $recPrime->delta * 1.95583;
                        }

                    } elseif ($DetClass instanceof sales_InvoiceDetails) {

                        if ($recPrime->type == 'invoice') {

                            $quantityLastYear = $recPrime->quantity * $recPrime->quantityInPack;

                            //Превалутиране
                            $recPrime->price = deals_Helper::getSmartBaseCurrency($recPrime->price, $recPrime->valior, $rec->to);
                            $recPrime->discount = deals_Helper::getSmartBaseCurrency($recPrime->discount, $recPrime->valior, $rec->to);

                            $discount = $recPrime->price * $quantityLastYear * $recPrime->discount;
                            $primeCostLastYear = ($recPrime->price * $quantityLastYear) - $discount;

                        } elseif ($recPrime->type == 'dc_note') {
                            $correctionArray = self::dcNoteCorrection($recPrime, $rec);

                            if (empty($correctionArray)) {
                                continue;
                            }
                            $quantityLastYear = $correctionArray['quantity'];
                            $primeCostLastYear = $correctionArray['amount'];  //Превалутирано в метода

                        }

                    }
                }
            }

            //Данни за ТЕКУЩ период
            if ($recPrime->valior >= $rec->from && $recPrime->valior <= $rec->to) {
                if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {
                    $quantity = (-1) * $recPrime->quantity;

                    //Превалутиране
                    if($rec->quantityType != 'shipped'){
                        $pricePr = deals_Helper::getSmartBaseCurrency($recPrime->{"{$price}"}, $recPrime->valior, $rec->to);
                    }else{
                        $pricePr = $recPrime->{"{$price}"};
                    }
                    if($rec->quantityType != 'shipped'){

                        $recPrime->delta = deals_Helper::getSmartBaseCurrency($recPrime->delta, $recPrime->valior, $rec->to);
                    }

                    $primeCost = (-1) * $pricePr * $recPrime->quantity;

                    $delta = (-1) * $recPrime->delta;

                } elseif ($DetClass instanceof sales_SalesDetails || $DetClass instanceof store_ShipmentOrderDetails || $DetClass instanceof pos_Reports || $DetClass instanceof sales_ServicesDetails) {
                    $quantity = $recPrime->quantity;

                    //Превалутиране
                    if($rec->quantityType != 'shipped'){

                        $pricePr = deals_Helper::getSmartBaseCurrency($recPrime->{"{$price}"}, $recPrime->valior, $rec->to);
                    }else{
                        $pricePr = $recPrime->{"{$price}"};
                    }

                    $primeCost = $pricePr * $recPrime->quantity;

                    //Ако е избрана Дилърска себестойност, и делтата е отрицателна,
                    // приемаме че, себестойността е 95% от продажната цена
                    if ($rec->primeCostType == 'dealerPrimeCost' && $recPrime->delta <= 0 && $prodRec->isPublic == 'no') {

                        //Превалутиране
                        if($rec->quantityType != 'shipped'){
                            $recPrime->sellCost = deals_Helper::getSmartBaseCurrency($recPrime->sellCost, $recPrime->valior, $rec->to);
                        }

                        $delta = $recPrime->sellCost * $deltaMinCoef * $recPrime->quantity ;

                    } else {

                        //Превалутиране

                        if($rec->quantityType != 'shipped'){

                            $recPrime->delta = deals_Helper::getSmartBaseCurrency($recPrime->delta, $recPrime->valior, $rec->to);
                        }

                        $delta = $recPrime->delta ;
                    }

                } elseif ($DetClass instanceof sales_InvoiceDetails) {

                    if ($recPrime->type == 'invoice') {

                        $quantity = $recPrime->quantity * $recPrime->quantityInPack;

                        //Превалутиране
                        if($rec->quantityType != 'shipped'){

                             $recPrime->price = deals_Helper::getSmartBaseCurrency($recPrime->price, $recPrime->valior, $rec->to);
                             $recPrime->discount = deals_Helper::getSmartBaseCurrency($recPrime->discount, $recPrime->valior, $rec->to);

                        }

                        $discount = $recPrime->price * $quantity * $recPrime->discount;
                        $primeCost = ($recPrime->price * $quantity) - $discount;

                    } elseif ($recPrime->type == 'dc_note') {

                        $correctionArray = self::dcNoteCorrection($recPrime, $rec);

                        if (empty($correctionArray)) {
                            continue;
                        }

                        $quantity = $correctionArray['quantity'];
                        $primeCost = $correctionArray['amount'];  // Превалутирано в метода

                    }
                }
            }

            $invProdRec = $invProd[$id] ?? (object) array('invQuantity' => 0, 'invAmount' => 0);

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
                if ($invProdRec->invAmount) {
                    $invProdRec->invAmount *= $rate;
                }
            }

            // Запис в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object)array(

                    'contragent' => $recPrime->folderId,                  //Папка на контрагента, когато продажбата не е от POS
                    'poscontragentClassId' => $poscontragentClassId,
                    'poscontragentId' => $poscontragentId,
                    'contragentName' => $contragentName,
                    'posContragentFolder' => $posContragentFolder,

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

                    'invQuantity' => $invProdRec->invQuantity,            // Фактурирано количество от този артикул на този контрагент
                    'invAmount' => $invProdRec->invAmount,                // Стойност на фактурираното количество от този артикул на този контрагент


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

            self::applyInvoiceDealerScope($iQuery, $scope);

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

                    //Превалутиране на сумата
                    $sumAmounts = deals_Helper::getSmartBaseCurrency($sumAmounts, $originRec->date, $rec->to);

                    while ($originDetRec = $dcAllInvQuery->fetch()) {

                        //Каква част от общата стойност е стойността на този ред
                        if ($sumAmounts) {

                            //Превалутиране
                            $originDetRec->amount = deals_Helper::getSmartBaseCurrency($originDetRec->amount, $originRec->date, $rec->to);

                            $partOfAmount = $originDetRec->amount / $sumAmounts;
                        } else {
                            $partOfAmount = 1;
                        }

                        //Превалутиране
                        $iRec->changeAmount = deals_Helper::getSmartBaseCurrency($iRec->changeAmount, $iRec->date, $rec->to);

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
        $totalValue = $totalDelta = $totalPrimeCostPrevious = $totalDeltaPrevious = $totalPrimeCostLastYear = $totalDeltaLastYear = 0;

        if ($rec->typeOfGroups == 'art' || $rec->typeOfGroups == 'nogrp') {
            $typeGroup = 'group';
        } elseif (($rec->typeOfGroups == 'category')) {
            $typeGroup = 'category';
        } elseif (($rec->typeOfGroups == 'no')) {
            $typeGroup = 'category';
        }
        $hasPositiveGroupFilter = ($rec->typeOfGroups != 'nogrp' && $rec->$typeGroup);

        // Изчисляване на общите продажби и продажбите по групи
        foreach ($recs as $v) {

            //Когато НЕ СА ИЗБРАНИ групи артикули
            if (!$hasPositiveGroupFilter) {
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
                    $groupValues[$gro] = ($groupValues[$gro] ?? 0) + $v->primeCost;                        //Стойност на продажбите за текущ период
                    $groupQuantity[$gro] = ($groupQuantity[$gro] ?? 0) + $v->quantity;                        //Стойност на продажбите за текущ период
                    $groupDeltas[$gro] = ($groupDeltas[$gro] ?? 0) + $v->delta;                            //Стойност на делтите за текущ период
                    $groupPrimeCostPrevious[$gro] = ($groupPrimeCostPrevious[$gro] ?? 0) + $v->primeCostPrevious;     //Стойност на продажбите за предходен период
                    $groupDeltaPrevious[$gro] = ($groupDeltaPrevious[$gro] ?? 0) + $v->deltaPrevious;             //Стойност на делтите за предходен период
                    $groupPrimeCostLastYear[$gro] = ($groupPrimeCostLastYear[$gro] ?? 0) + $v->primeCostLastYear;     //Стойност на продажбите за предходна година
                    $groupDeltaLastYear[$gro] = ($groupDeltaLastYear[$gro] ?? 0) + $v->deltaLastYear;             //Стойност на делтите за предходна година
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

                $tempArrKey = ($rec->seeByContragent == 'yes') ? $v->productId . ' | ' . $v->posContragentFolder : $v->productId;


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

                    $groupValues[$gro] = ($groupValues[$gro] ?? 0) + $v->primeCost;
                    $groupQuantity[$gro] = ($groupQuantity[$gro] ?? 0) + $v->quantity;
                    $groupDeltas[$gro] = ($groupDeltas[$gro] ?? 0) + $v->delta;
                    $groupPrimeCostPrevious[$gro] = ($groupPrimeCostPrevious[$gro] ?? 0) + $v->primeCostPrevious;
                    $groupDeltaPrevious[$gro] = ($groupDeltaPrevious[$gro] ?? 0) + $v->deltaPrevious;
                    $groupPrimeCostLastYear[$gro] = ($groupPrimeCostLastYear[$gro] ?? 0) + $v->primeCostLastYear;
                    $groupDeltaLastYear[$gro] = ($groupDeltaLastYear[$gro] ?? 0) + $v->deltaLastYear;
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
        if ($hasPositiveGroupFilter) {
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
                $groupKey = $v->$typeGroup;
                $v->groupValues = $groupValues[$groupKey] ?? 0;
                $v->groupQuantity = $groupQuantity[$groupKey] ?? 0;
                $v->groupDeltas = $groupDeltas[$groupKey] ?? 0;
                $v->groupPrimeCostPrevious = $groupPrimeCostPrevious[$groupKey] ?? 0;
                $v->groupDeltaPrevious = $groupDeltaPrevious[$groupKey] ?? 0;
                $v->groupPrimeCostLastYear = $groupPrimeCostLastYear[$groupKey] ?? 0;
                $v->groupDeltaLastYear = $groupDeltaLastYear[$groupKey] ?? 0;
            }
            unset($v);
        } else {
            foreach ($recs as $v) {
                foreach ($v->$typeGroup as $gro) {
                    $v->groupValues = $groupValues[$gro] ?? 0;
                    $v->groupQuantity = $groupQuantity[$gro] ?? 0;
                    $v->groupDeltas = $groupDeltas[$gro] ?? 0;

                    $v->groupPrimeCostPrevious = $groupPrimeCostPrevious[$gro] ?? 0;
                    $v->groupDeltaPrevious = $groupDeltaPrevious[$gro] ?? 0;

                    $v->groupPrimeCostLastYear = $groupPrimeCostLastYear[$gro] ?? 0;
                    $v->groupDeltaLastYear = $groupDeltaLastYear[$gro] ?? 0;
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

            if ($rec->typeOfGroups == 'category' || $rec->typeOfGroups == 'no') {
                foreach ($groupValues as $key => $val) {
                    if (cat_Categories::fetch($key) === false) {
                        $groupValues[99999] = ($groupValues[99999] ?? 0) + $val;
                        unset($groupValues[$key]);
                    }
                }
            }

            foreach ($groupValues as $k => $v) {
                $recs[$k] = (object)array(
                    'group' => $k,                                                                    //Група артикули
                    'primeCost' => $v,                                                                //Продажби за текущия период за групата
                    'delta' => $groupDeltas[$k] ?? 0,                                                 //Делта за текущия период за групата

                    'groupPrimeCostPrevious' => $groupPrimeCostPrevious[$k] ?? 0,                     //Продажби за предходен период за групата
                    'changeGroupPrimeCostPrevious' => $v - ($groupPrimeCostPrevious[$k] ?? 0),        //Промяна в продажбите спрямо предходен период за групата
                    'groupDeltaPrevious' => $groupDeltaPrevious[$k] ?? 0,                             //Делта за предходен период за групата
                    'changeGroupDeltaPrevious' => ($groupDeltas[$k] ?? 0) - ($groupDeltaPrevious[$k] ?? 0),

                    'groupPrimeCostLastYear' => $groupPrimeCostLastYear[$k] ?? 0,
                    'changeGroupPrimeCostLastYear' => $v - ($groupPrimeCostLastYear[$k] ?? 0),
                    'groupDeltaLastYear' => $groupDeltaLastYear[$k] ?? 0,
                    'changeGroupDeltaLastYear' => ($groupDeltas[$k] ?? 0) - ($groupDeltaLastYear[$k] ?? 0),
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

            if ($rec->orderBy == 'quantity' && $rec->grouping == 'yes') {
               // $orderBy = 'group';
            }

            arr::sortObjects($recs, $orderBy, $rec->order, $typeOrder);
        }

//        //Добавям ред за ОБЩИТЕ суми
//        $totalArr['total'] = (object)array(
//            'totalValue' => $totalValue,
//            'totalDelta' => $totalDelta,
//            'totalPrimeCostPrevious' => $totalPrimeCostPrevious,
//            'totalDeltaPrevious' => $totalDeltaPrevious,
//            'totalPrimeCostLastYear' => $totalPrimeCostLastYear,
//            'totalDeltaLastYear' => $totalDeltaLastYear
//        );

       // array_unshift($recs, $totalArr['total']);

        return $recs;


    }

    /**
     * Връща детайлите по фактурите
     *
     * @return array $invDetQuery
     */
    /**
     * Ограничава заявка за фактури до сделките (нишките) с разрешен търговец.
     *
     * Фактурите и известията (КИ/ДИ) нямат собствено поле dealerId — атрибуцията към
     * търговец става през нишката на сделката: продажбата, фактурите и известията към нея
     * са в една нишка. Затова се филтрира по threadId на продажбите с разрешен dealer.
     * За потребител с пълни права (canSeeAll) не се прилага ограничение.
     *
     * @param core_Query $query        заявка с достъпно поле threadId (native или EXT от sales_Invoices)
     * @param array      $scope        резултат от getDealerAccessScope()
     * @param string     $threadField  име на полето с нишката (по подразбиране 'threadId')
     */
    protected static function applyInvoiceDealerScope($query, $scope, $threadField = 'threadId')
    {
        if ($scope['canSeeAll']) {

            return;
        }

        // Нишките на разрешените продажби се смятат веднъж за заявка (кеш по набор търговци)
        static $threadsCache = array();
        $cacheKey = implode(',', array_keys($scope['allowedDealers']));

        if (!isset($threadsCache[$cacheKey])) {
            $allowedThreads = array();

            if (!empty($scope['allowedDealers'])) {
                $saleQuery = sales_Sales::getQuery();
                $saleQuery->in('dealerId', $scope['allowedDealers']);
                $saleQuery->where("#state != 'rejected' AND #state != 'draft'");
                $saleQuery->show('threadId');
                while ($saleRec = $saleQuery->fetch()) {
                    $allowedThreads[$saleRec->threadId] = $saleRec->threadId;
                }
            }

            $threadsCache[$cacheKey] = $allowedThreads;
        }

        $allowedThreads = $threadsCache[$cacheKey];

        // Празно = "нищо". core_Query::in([]) не добавя условие и би показало всичко.
        if (empty($allowedThreads)) {
            $query->where('1=2');
        } else {
            $query->in($threadField, $allowedThreads);
        }
    }


    public static function getInvoicedProducts($rec)
    {
        $invDetQuery = array();

        $invDetQuery = sales_InvoiceDetails::getQuery();

        $invDetQuery->EXT('state', 'sales_Invoices', 'externalName=state,externalKey=invoiceId');

        $invDetQuery->EXT('number', 'sales_Invoices', 'externalName=number,externalKey=invoiceId');

        $invDetQuery->EXT('containerId', 'sales_Invoices', 'externalName=containerId,externalKey=invoiceId');

        $invDetQuery->EXT('threadId', 'sales_Invoices', 'externalName=threadId,externalKey=invoiceId');

        $invDetQuery->EXT('originId', 'sales_Invoices', 'externalName=originId,externalKey=invoiceId');

        $invDetQuery->EXT('changeAmount', 'sales_Invoices', 'externalName=changeAmount,externalKey=invoiceId');

        $invDetQuery->EXT('currencyId', 'sales_Invoices', 'externalName=currencyId,externalKey=invoiceId');

        $invDetQuery->EXT('date', 'sales_Invoices', 'externalName=date,externalKey=invoiceId');

        $invDetQuery->EXT('valior', 'sales_Invoices', 'externalName=date,externalKey=invoiceId');

        $invDetQuery->EXT('type', 'sales_Invoices', 'externalName=type,externalKey=invoiceId');

        $invDetQuery->EXT('folderId', 'sales_Invoices', 'externalName=folderId,externalKey=invoiceId');

        $invDetQuery->EXT('contragentClassId', 'sales_Invoices', 'externalName=contragentClassId,externalKey=invoiceId');

        $invDetQuery->EXT('contragentId', 'sales_Invoices', 'externalName=contragentId,externalKey=invoiceId');

        $invDetQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');

        return $invDetQuery;

    }

    /**
     * Преизчислява стойностите и количествата на фактурите, към които има КИ и ДИ
     * когато те коригират реда по количество или стойност
     *
     * @return array $res
     */
    public static function dcNoteCorrection($dcRec, $rec)
    {

        $originQuantity = $changeQuatity = $changePrice = $invQuantity = $invAmount = 0;

        $res = array();

        $originId = doc_Containers::getDocument($dcRec->originId)->that;


        $originDetRec = sales_InvoiceDetails::fetch("#invoiceId = $originId AND #productId = '$dcRec->productId' AND
                                                           #packagingId = '$dcRec->packagingId' AND
                                                           #id = '$dcRec->clonedFromDetailId' 
                                                           AND (#quantity != '$dcRec->quantity' OR #price != '$dcRec->price')");

        if (!$originDetRec) {
            return $res;
        }

        $originQuantity = $originDetRec->quantity * $originDetRec->quantityInPack;

        $changeQuatity = $dcRec->quantity * $dcRec->quantityInPack - $originQuantity;
        $changePrice = $dcRec->price - $originDetRec->price;

        if ($changeQuatity == 0 && $changePrice == 0) {
            return $res;
        }

        $invQuantity = $changeQuatity != 0 ? $changeQuatity : 0;
        $invAmount = $changeQuatity == 0 ? $changePrice * $dcRec->quantity * $dcRec->quantityInPack : $dcRec->price * $invQuantity;

        if ($dcRec->discount) {
            $invAmount = $invAmount * (1 - $dcRec->discount);
        }

        //Превалутиране
        $invAmount = deals_Helper::getSmartBaseCurrency($invAmount, $dcRec->date, $rec->to);

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
        self::applyRecDefaults($rec);

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
                        $fld->FLD('contragentName', 'varchar', 'caption=Контрагент');
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
                $fld->FLD('contragentName', 'varchar', 'caption=Контрагент');
            }
            if ($rec->group) {
                $fld->FLD('group', 'varchar', 'caption=Група');
            }

            $fld->FLD('code', 'varchar', 'caption=Код');
            $fld->FLD('productId', 'varchar', 'caption=Артикул');

            if ($rec->engName == 'yes') {
                $fld->FLD('engName', 'varchar', 'caption=Артикул[EN]');
            }

            $fld->FLD('measure', 'key( mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
            if ($rec->seeCategory == 'yes') {
                $fld->FLD('category', 'varchar', 'caption=Категория');
            }
            $fld->FLD('quantity', 'double(sdecimals=2)', "smartCenter,caption={$name1} Продажби");
            $fld->FLD('primeCost', 'double(decimals=2)', "smartCenter,caption={$name1} Стойност");

            if ($rec->seeByContragent == 'yes') {
                $fld->FLD('invQuantity', 'double(decimals=2)', 'smartCenter,caption=Фактурирано->количество');
                $fld->FLD('invAmount', 'double(decimals=2)', 'smartCenter,caption=Фактурирано->стойност');
            }

            if ($rec->seeDelta == 'yes') {
                $fld->FLD('delta', 'double(decimals=2)', "smartCenter,caption={$name1} Делта");
            }

            if ($rec->seeWeight == 'yes') {
                $fld->FLD('weight', 'double(smartRound,decimals=2)', 'smartCenter,caption=Тегло->[кг]');
            }


            if ($rec->compare != 'no') {
                $fld->FLD('quantityCompare', 'double(decimals=2)', "smartCenter,caption={$name2} Продажби,tdClass=newCol");
                $fld->FLD('primeCostCompare', 'double(decimals=2)', "smartCenter,caption={$name2} Стойност,tdClass=newCol");
                $fld->FLD('deltaCompare', 'double(decimals=2)', "smartCenter,caption={$name2} Делта,tdClass=newCol");
                $fld->FLD('changeSales', 'double(decimals=2)', 'smartCenter,caption=Промяна Стойност');
                $fld->FLD('changeDeltas', 'double(decimals=2)', 'smartCenter,caption=Промяна Делти');
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
    private static function getGroups($dRec, $verbal = true, $rec = null)
    {
        if ($rec->typeOfGroups == 'art') {
            $typeGroup = 'group';
        } elseif (($rec->typeOfGroups == 'category') || ($rec->typeOfGroups == 'no')) {
            $typeGroup = 'category';
        }

        if ($verbal === true) {
            if (is_numeric($dRec->$typeGroup)) {
                $groupVal = $dRec->groupValues;
                $groupDeltas = $dRec->groupDeltas;
                $grouping = ($rec->seeDelta == 'yes') ? ', делта: ' . core_Type::getByName('double(decimals=2)')->toVerbal($groupDeltas) : '';

                if ($rec->typeOfGroups == 'art') {
                    $groupClass = 'cat_Groups';
                } elseif (($rec->typeOfGroups == 'category') || ($rec->typeOfGroups == 'no')) {
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
        };
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
        self::applyRecDefaults($rec);

        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;

        $row = new stdClass();

        $euroZoneDate = acc_Setup::getEurozoneDate();
        $baseCurrency = acc_Periods::getBaseCurrencyCode($rec->checkDate ?? $rec->to);

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
            } elseif (($rec->typeOfGroups == 'category') || ($rec->typeOfGroups == 'no')) {
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

            $row->contragentName = $dRec->contragentName ?? null;


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
            $dRec->delta = $dRec->delta ?? 0;
            foreach (array(
                         'quantity',
                         'invQuantity',
                         'invAmount',
                         'weight'
                     ) as $fld) {
                if (!isset($dRec->{$fld})) {
                    continue;
                }

                ////////////////////////////////////////////////////////////////////////
                // Ако справката се издава за период преди еврозоната с основна валута BGN
                if ($rec->to < $euroZoneDate) {
                    if ($rec->quantityType != 'shipped') {
                        $row->primeCost = $Double->toVerbal($dRec->primeCost);
                        $row->delta = $Double->toVerbal($dRec->delta);
                    } else {
                        $row->primeCost = $Double->toVerbal($dRec->primeCost * 1.95583);
                        $row->delta = $Double->toVerbal($dRec->delta * 1.95583);
                    }
                }

                ///////////////////////////////////////////////////////////////////////
                // Ако справката се издава за период ОТ ЕВРОЗОНАТА с основна валута EUR
                if ($rec->to >= $euroZoneDate) {


                    if ($rec->quantityType != 'shipped') {
                        $row->primeCost = $Double->toVerbal($dRec->primeCost);
                        $row->delta = $Double->toVerbal($dRec->delta);
                    } else {
                        $row->primeCost = $Double->toVerbal($dRec->primeCost );
                        $row->delta = $Double->toVerbal($dRec->delta );
                    }




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
        self::applyRecDefaults($rec);

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
        $row->compare = $arrCompare[$rec->compare] ?? $rec->compare;
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
        self::applyRecDefaults($data->rec);
        $dealersVerb = $groupVerb = $contragentVerb = $categoryVerb = '';

        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN from--><div>|От|*: [#from#]</div><!--ET_END from-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                        <!--ET_BEGIN firstMonth--><div>|Месец 1|*: [#firstMonth#]</div><!--ET_END firstMonth-->
                                        <!--ET_BEGIN secondMonth--><div>|Месец 2|*: [#secondMonth#]</div><!--ET_END secondMonth-->
                                        <!--ET_BEGIN dealers--><div>|Търговци|*: [#dealers#]</div><!--ET_END dealers-->
                                         <!--ET_BEGIN dealersTeam--><div>|Екипи|*: [#dealersTeam#]</div><!--ET_END dealersTeam-->
                                        <!--ET_BEGIN contragent--><div>|Контрагент|*: [#contragent#]</div><!--ET_END contragent-->
                                        <!--ET_BEGIN crmGroup--><div>|Група контрагенти|*: [#crmGroup#]</div><!--ET_END crmGroup-->
                                        <!--ET_BEGIN group--><div>|Групи продукти|*: [#group#]</div><!--ET_END group-->
                                        <!--ET_BEGIN category--><div>|Категории продукти|*: [#category#]</div><!--ET_END category-->
                                        <!--ET_BEGIN art--><div>|Артикули|*: [#art#]</div><!--ET_END art-->
                                        <!--ET_BEGIN compare--><div>|Сравнение|*: [#compare#]</div><!--ET_END compare-->
                                        <!--ET_BEGIN currency--><div>|Валута|*: [#currency#]</div><!--ET_END currency-->
                                        <!--ET_BEGIN minDelta--><div>|Мин. делта[%]|*: [#minDelta#]</div><!--ET_END minDelta-->
                                        <!--ET_BEGIN grFilter--><div>|Филтър по група |*: [#grFilter#]</div><!--ET_END grFilter-->
                                        <!--ET_BEGIN button--><div>|Филтри |*: [#button#]</div><!--ET_END button-->
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

        //Показваме избраните търговци
        if ((isset($data->rec->dealers)) && ($data->rec->quantityType != 'invoiced') && ((min(array_keys(keylist::toArray($data->rec->dealers))) >= 1))) {

            foreach (type_Keylist::toArray($data->rec->dealers) as $dealer) {
                $dealersVerb .= (core_Users::getTitleById($dealer) . ', ');
            }

                $fieldTpl->append('<b>' . trim($dealersVerb, ',  ') . '</b>', 'dealers');
        } else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'dealers');
        }
        // Показваме избраните екипи търговци
        if (!empty($data->rec->dealersTeam) && $data->rec->quantityType != 'invoiced') {
            // Преобразуваме keylist в масив от id-та
            $teamIds = keylist::toArray($data->rec->dealersTeam);
            $teamNames = [];
            $marker1 = 0;$role = '';
            foreach ($teamIds as $roleId) {

                $marker1++;
                // Вземаме името на всяка роля чрез core_Roles
                $role .= core_Roles::fetch($roleId)->role . ', ';

            }
                $fieldTpl->append('<b>' . $role . '</b>', 'dealersTeam');

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
        $marker = 0;
        if (isset($data->rec->group)) {

            foreach (type_Keylist::toArray($data->rec->group) as $group) {
                $marker++;

                $groupVerb .= (cat_Groups::fetch($group)->name);

                if ((countR(type_Keylist::toArray($data->rec->$group))) - $marker != 0) {
                    $groupVerb .= ', ';
                }
            }
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

        if ($data->rec->primeCostType == 'dealerPrimeCost') {
            $coefDelta = core_Packs::getConfig('sales')->SALES_DELTA_MIN_PERCENT;
            $coefSee = ($coefDelta != '') ? $coefDelta * 100 : 'Не е посочен ';
            $fieldTpl->append('<b>' . $coefSee . '</b>', 'minDelta');
        }

        //Филтър по група
        $grFilter = $data->rec->grFilter;

        if ($grFilter) {
            if ($data->rec->typeOfGroups == 'category') {
                $grFilterRec = cat_Categories::fetch($grFilter);
            } else {
                $grFilterRec = cat_Groups::fetch($grFilter);
            }
            $grFilterName = $grFilterRec->name ?? 'Не е избрана';
        } else {
            $grFilterName = 'Не е избрана';
        }
        $fieldTpl->append('<b>' . "$grFilterName" . '</b>', 'grFilter');

        $grUrl = array('sales_reports_SoldProductsRep', 'groupfilter', 'recId' => $data->rec->id, 'ret_url' => true);
        $artUrl = array('sales_reports_SoldProductsRep', 'artfilter', 'recId' => $data->rec->id, 'ret_url' => true);
        //$exportUrl = array('store_reports_ProductAvailableQuantity1', 'exportfilter', 'recId' => $data->rec->id, 'ret_url' => true);

        $toolbar = cls::get('core_Toolbar');

        $toolbar->addBtn('Избери група', toUrl($grUrl));
        $toolbar->addBtn('Избери артикул', toUrl($artUrl));
        //$toolbar->addBtn('Филтър за експорт', toUrl($exportUrl));

        $fieldTpl->append('<b>' . $toolbar->renderHtml() . '</b>', 'button');


        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }


    /**
     * Филтриране по група/категория в резултатите на справката
     */
    public static function act_GroupFilter()
    {
        expect($recId = Request::get('recId', 'int'));

        $rec = frame2_Reports::fetch($recId);

        frame2_Reports::refresh($rec);
        $rec = frame2_Reports::fetch($recId);
        self::applyRecDefaults($rec);

        if (!in_array($rec->typeOfGroups, array('art', 'category'))) {
            core_Statuses::newStatus('Филтърът по група е достъпен само при "Групи артикули" или "Категории артикули"', 'error');

            return new Redirect(array('doc_Containers', 'list', 'threadId' => $rec->threadId, 'docId' => $recId, 'ret_url' => true));
        }

        $form = cls::get('core_Form');
        $form->title = 'Филтър за група';

        $isCategoryFilter = ($rec->typeOfGroups == 'category');
        $groupFilterType = $isCategoryFilter ? 'key(mvc=cat_Categories,allowEmpty,select=name)' : 'key(mvc=cat_Groups,allowEmpty,select=name)';

        $form->FLD('groupFilter', $groupFilterType, 'caption=Покажи група,placeholderType=all,silent');

        $suggestions = self::getGroupFilterSuggestions($rec);
        if ($rec->grFilter && !isset($suggestions[$rec->grFilter])) {
            if ($isCategoryFilter) {
                $filterRec = cat_Categories::fetch($rec->grFilter);
            } else {
                $filterRec = cat_Groups::fetch($rec->grFilter);
            }

            if ($filterRec) {
                $suggestions[$filterRec->id] = $filterRec->name;
            }
        }

        asort($suggestions);
        $form->setOptions('groupFilter', $suggestions);
        $form->setDefault('groupFilter', $rec->grFilter);

        $form->input();

        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');

        if ($form->isSubmitted()) {
            $rec->grFilter = $form->rec->groupFilter ?? null;

            frame2_Reports::save($rec);
            frame2_Reports::refresh($rec);

            return new Redirect(array('doc_Containers', 'list', 'threadId' => $rec->threadId, 'docId' => $recId, 'grFilter' => $form->rec->groupFilter, 'ret_url' => true));
        }

        return $form->renderHtml();
    }


    /**
     * Филтриране по артикул в текущите резултати на справката
     */
    public static function act_ArtFilter()
    {
        expect($recId = Request::get('recId', 'int'));

        $rec = frame2_Reports::fetch($recId);
        self::applyRecDefaults($rec);

        if (Request::get('clearArtFilter', 'int')) {
            frame2_Reports::refresh($rec);

            return new Redirect(array('doc_Containers', 'list', 'threadId' => $rec->threadId, 'docId' => $recId, 'ret_url' => true));
        }

        $filterRec = clone $rec;
        if ($Driver = frame2_Reports::getDriver($filterRec)) {
            $filterRec->data = $Driver->prepareData($filterRec);
        }

        $form = cls::get('core_Form');
        $form->title = 'Филтър по артикул';

        $artSuggestionsArr = array();
        if (!empty($filterRec->data->recs) && is_array($filterRec->data->recs)) {
            $prArr = arr::extractValuesFromArray($filterRec->data->recs, 'productId');
            foreach (array_keys($prArr) as $val) {
                $pRec = cat_Products::fetch($val);
                $code = $pRec->code ?: 'Art' . $pRec->id;
                $artSuggestionsArr[$val] = $code . '|' . $pRec->name;
            }
        }

        $form->FLD('artFilter', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,silent');
        $form->setOptions('artFilter', $artSuggestionsArr);

        $form->input();

        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Изчисти филтъра', array('sales_reports_SoldProductsRep', 'artfilter', 'recId' => $recId, 'clearArtFilter' => 1, 'ret_url' => true), 'ef_icon = img/16/delete.png');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');

        if ($form->isSubmitted()) {
            $artFilter = $form->rec->artFilter ?? null;
            $rec->data = $filterRec->data;
            foreach ($rec->data->recs as $key => $pRec) {
                if (!empty($pRec->productId) && $artFilter != $pRec->productId) {
                    unset($rec->data->recs[$key]);
                }
            }

            frame2_Reports::save($rec);

            return new Redirect(array('doc_Containers', 'list', 'threadId' => $rec->threadId, 'docId' => $recId, 'artFilter' => $artFilter, 'ret_url' => true));
        }

        return $form->renderHtml();
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
        self::applyRecDefaults($rec);

        if (!empty($dRec->productId)) {
            $prodRec = cat_Products::fetch($dRec->productId);
        }

        $res->group = self::getGroups($dRec, false, $rec);
        if (isset($dRec->measure)) {
            $res->measure = $dRec->measure;
        }
        if (isset($dRec->productId)) {
            $res->productId = $prodRec->name;
        }

        if ($rec->compare != 'no') {
            if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                $res->quantityCompare = $dRec->quantityPrevious;
                $res->primeCostCompare = $dRec->primeCostPrevious;
                $res->deltaCompare = $dRec->deltaPrevious;
                $res->changeSales = $dRec->primeCost - $dRec->primeCostPrevious;
                $res->changeDeltas = $dRec->delta - $dRec->deltaPrevious;
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
                if (isset($res->contragentName)) {
                    $res->contragentName = $dRec->contragentName;
                }

            }
        }

        if (!empty($res->totalValue)) {
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
                $engName = $prodRec->nameEn ? $prodRec->nameEn : 'none';
                $res->engName = $engName;
            }
            if ($rec->seeCategory == 'yes') {
                $prodFolderId = $prodRec->folderId;
                $prodCategory = doc_Folders::fetch($prodFolderId)->title;
                $res->category = $prodCategory;
            }
            $res->productId = $dRec->productId;
        }
    }


    /**
     * Връща папките на контрагентите от избраните групи
     *
     * @param stdClass $rec
     *
     * @return array
     */
    public static function getContragentsInGroups($rec)
    {
        $foldersInGroups = array();
        foreach (array('crm_Companies', 'crm_Persons') as $clsName) {
            $q = $clsName::getQuery();

            $q->LikeKeylist('groupList', $rec->crmGroup);

            $q->where('#folderId IS NOT NULL');

            $q->show('folderId');

            $foldersInGroups = array_merge($foldersInGroups, arr::extractValuesFromArray($q->fetchAll(), 'folderId'));
        }
//        foreach ($foldersInGroups as $contragent) {
//            $Cover = doc_Folders::getCover($contragent);
//            $contragentsIdArr[$Cover->getClassId()][$Cover->that] = $Cover->that;
//        }

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

        $kgMeasureRec = cat_UoM::getQuery()->fetch("#name = 'килограм'");
        if (!$kgMeasureRec) {
            return 'n.a.';
        }
        $kgMeasureId = $kgMeasureRec->id;

        //Взема единичното тегло на целия продукт
        $singleProductWeight = null;

        $singleProductWeight = cat_Products::getParams($rec->id, 'weight');

        if ($singleProductWeight) {
            $singleProductWeight = $singleProductWeight / 1000;
        } else {
            $singleProductWeight = cat_Products::getParams($rec->id, 'weightKg');
        }

        $kgPackRec = cat_products_Packagings::getPack($rec->id, $kgMeasureId);

        if ($rec->measureId == $kgMeasureId) {
            $singleProductWeight = 1;
        }

        if (!$singleProductWeight){
         if($kgPackRec && $kgPackRec->isSecondMeasure == 'yes' && !empty($kgPackRec->quantity)){

             $singleProductWeight = 1/$kgPackRec->quantity;
         }
        }

        $singleProductWeight = $singleProductWeight ? $singleProductWeight : 'n.a.';

        return $singleProductWeight;
    }

    /**
     * Да се изпраща ли нова нотификация на споделените потребители, при опресняване на отчета
     *
     * @param stdClass $rec
     *
     * @return bool
     */
    public function canSendNotificationOnRefresh($rec)
    {
        //   return true;
    }
}
