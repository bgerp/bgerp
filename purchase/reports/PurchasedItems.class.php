<?php


/**
 * Мениджър на отчети за доставени артикули
 *
 *
 * @category  bgerp
 * @package   purchase
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Покупки » Закупени артикули
 */
class purchase_reports_PurchasedItems extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, acc, repAll, repAllGlobal, purchase';


    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField;


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
    protected $changeableFields = 'from,duration,compare,compareStart,seeCrmGroup,seeGroup,group,dealers,contragent,crmGroup,articleType,orderBy,grouping,updateDays,updateTime';


    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields = 'code,productId,changePurchases,amount';


    /**
     * Кои полета са за избор на период
     */
    protected $periodFields = 'from,to';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('dealers', 'users(rolesForAll=ceo|repAllGlobal, rolesForTeams=ceo|manager|repAll|repAllGlobal)', 'caption=Търговци,single=none,after=title,mandatory');

        //Период
        $fieldset->FLD('from', 'date', 'caption=Период->От,after=dealers,single=none,mandatory');
        $fieldset->FLD('duration', 'time(suggestions=1 седмица| 1 месец| 2 месеца| 3 месеца| 6 месеца| 12 месеца)', 'caption=Период->Продължителност,after=from,single=none,mandatory');

        //Сравнение
        $fieldset->FLD('compare', 'enum(no=Без, previous=Предходен период, year=Миналогодишен период,checked=Избран период)', 'caption=Сравнение->Сравнение,after=duration,removeAndRefreshForm,single=none,silent');
        $fieldset->FLD('compareStart', 'date', 'caption=Сравнение->Начало,after=compare,single=none,mandatory');

        //Контрагенти и групи контрагенти
        $fieldset->FLD('contragent', 'keylist(mvc=doc_Folders,select=title,allowEmpty)', 'caption=Контрагенти->Контрагент,single=none,after=compareStart');
        $fieldset->FLD('seeCrmGroup', 'set(yes = )', 'caption=Контрагенти->Група контрагенти,after=contragent,removeAndRefreshForm,silent,single=none');

        if (BGERP_GIT_BRANCH == 'dev') {
            $fieldset->FLD('crmGroup', 'keylist(mvc=crm_Groups,select=name, parentId=parentId)', 'caption=Контрагенти->Група контрагенти,after=seeCrmGroup,single=none');
        } else {
            $fieldset->FLD('crmGroup', 'treelist(mvc=crm_Groups,select=name, parentId=parentId)', 'caption=Контрагенти->Група контрагенти,after=seeCrmGroup,single=none');
        }

        //Склад
        $fieldset->FLD('storeId', 'keylist(mvc=store_Stores,select=name,allowEmpty)', 'caption=Избор на склад->Склад,placeholder=Всички,after=crmGroup,single=none');

        //Групиране на резултата
        $fieldset->FLD('seeGroup', 'set(yes = )', 'caption=Артикули->Група артикули,after=storeId,removeAndRefreshForm,silent,single=none');

        if (BGERP_GIT_BRANCH == 'dev') {
            $fieldset->FLD('group', 'keylist(mvc=cat_Groups,select=name, parentId=parentId)', 'caption=Артикули->Група артикули,after=seeGroup,single=none');
        } else {
            $fieldset->FLD('group', 'treelist(mvc=cat_Groups,select=name, parentId=parentId)', 'caption=Артикули->Група артикули,after=seeGroup,single=none');
        }

        $fieldset->FLD('articleType', 'enum(yes=Стандартни,no=Нестандартни,all=Всички)', 'caption=Артикули->Тип артикули,after=group,single=none');

        //Показване на резултата
        $fieldset->FLD('grouping', 'enum(art=По артикули, grouped=Групирано)', 'caption=Показване->Вид,maxRadio=2,after=articleType');

        //Подредба на резултатите
        $fieldset->FLD('orderBy', 'enum(code=Код, amount=Стойност, changeAmount=Промяна)', 'caption=Подреждане на резултата->Показател,maxRadio=5,columns=3,after=grouping');


        $fieldset->FNC('to', 'date', 'input=none,single=none');          //Крайна дата на избрания период за наблюдение
        $fieldset->FNC('toChecked', 'date', 'input=none,single=none');   //Крайна дата на избрания период за сравнение
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

            //Проверка за правилна подредба
            if (($form->rec->orderBy == 'code') && ($form->rec->grouping == 'grouped')) {
                $form->setError('orderBy', 'При ГРУПИРАНО показване не може да има подредба по КОД.');
            }

            if (($form->rec->orderBy == 'changeAmount') && ($form->rec->compare == 'no')) {
                $form->setError('orderBy', 'Когато няма сравнение не се отчита промяна.');
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
        $suggestions = array();

        if ($rec->compare != 'checked') {
            $form->setField('compareStart', 'input=none');
        }

        if ($rec->seeCrmGroup != 'yes') {
            $form->setField('crmGroup', 'input=none');
        }

        if ($rec->seeGroup != 'yes') {
            $form->setField('group', 'input=none');
        }

        $form->setDefault('articleType', 'all');

        $form->setDefault('duration', '1 месец');

        $form->setDefault('compare', 'no');

        $form->setDefault('grouping', 'art');

        $form->setDefault('orderBy', 'amount');

        //Масив с предложения за избор на контрагент $suggestions[]
        $purchaseQuery = purchase_Purchases::getQuery();

        $purchaseQuery->EXT('folderTitle', 'doc_Folders', 'externalName=title,externalKey=folderId');

        $purchaseQuery->groupBy('folderId');

        $purchaseQuery->show('folderId, contragentId, folderTitle');

        while ($contragent = $purchaseQuery->fetch()) {
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
        //Показването да бъде ли ГРУПИРАНО
        if (($rec->grouping == 'art') && $rec->group) {
            $this->groupByField = 'group';
        }

        $recs = array();
        $purchasesThreads = $purchasesFastThreads = array();

        //ПОКУПКИ
        $purchasesQuery = purchase_Purchases::getQuery();

        $purchasesQuery->where("#state != 'rejected'");

        $purchasesQuery->show('threadId,contoActions');

        while ($purchase = $purchasesQuery->fetch()) {
            if (strpos($purchase->contoActions, 'ship') != false) {

                //Масив с нишките на бързите покупки
                if (!in_array($purchase->threadId, $purchasesFastThreads)) {
                    $purchasesFastThreads[$purchase->threadId] = $purchase->threadId;
                }
            } else {

                //Масив с нишките на НЕбързите покупки

                if (!in_array($purchase->threadId, $purchasesThreads)) {
                    $purchasesThreads[$purchase->threadId] = $purchase->threadId;
                }
            }
        }

        //Складови разписки
        $receiptsDetQuery = store_ReceiptDetails::getQuery();

        $receiptsDetQuery->EXT('threadId', 'store_Receipts', 'externalName=threadId,externalKey=receiptId');

        $receiptsDetQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');

        $receiptsDetQuery->EXT('isReverse', 'store_Receipts', 'externalName=isReverse,externalKey=receiptId');

        $receiptsDetQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');

        $receiptsDetQuery->EXT('state', 'store_Receipts', 'externalName=state,externalKey=receiptId');

        $receiptsDetQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');

        $receiptsDetQuery->EXT('valior', 'store_Receipts', 'externalName=valior,externalKey=receiptId');

        $receiptsDetQuery->EXT('storeId', 'store_Receipts', 'externalName=storeId,externalKey=receiptId');

        //Експедиционни нареждания за връщане на стока
        $shipmentOrdersDetQuery = store_ShipmentOrderDetails::getQuery();

        $shipmentOrdersDetQuery->EXT('threadId', 'store_ShipmentOrders', 'externalName=threadId,externalKey=shipmentId');

        $shipmentOrdersDetQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');

        $shipmentOrdersDetQuery->EXT('isReverse', 'store_ShipmentOrders', 'externalName=isReverse,externalKey=shipmentId');

        $shipmentOrdersDetQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');

        $shipmentOrdersDetQuery->EXT('state', 'store_ShipmentOrders', 'externalName=state,externalKey=shipmentId');

        $shipmentOrdersDetQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');

        $shipmentOrdersDetQuery->EXT('valior', 'store_ShipmentOrders', 'externalName=valior,externalKey=shipmentId');

        $shipmentOrdersDetQuery->EXT('storeId', 'store_ShipmentOrders', 'externalName=storeId,externalKey=shipmentId');

        //Бързи продажби
        $fastPurchasesDetQuery = purchase_PurchasesDetails::getQuery();

        $fastPurchasesDetQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');

        $fastPurchasesDetQuery->EXT('threadId', 'purchase_Purchases', 'externalName=threadId,externalKey=requestId');

        $fastPurchasesDetQuery->in('threadId', $purchasesFastThreads);

        $fastPurchasesDetQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');

        $fastPurchasesDetQuery->EXT('state', 'purchase_Purchases', 'externalName=state,externalKey=requestId');

        $fastPurchasesDetQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');

        $fastPurchasesDetQuery->EXT('valior', 'purchase_Purchases', 'externalName=valior,externalKey=requestId');

        $fastPurchasesDetQuery->EXT('storeId', 'purchase_Purchases', 'externalName=shipmentStoreId,externalKey=requestId');

        //От приемателни протоколи за услуги
        $purchasesServicesDetQuery = purchase_ServicesDetails::getQuery();

        $purchasesServicesDetQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');

        $purchasesServicesDetQuery->EXT('threadId', 'purchase_Services', 'externalName=threadId,externalKey=shipmentId');

        $purchasesServicesDetQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');

        $purchasesServicesDetQuery->EXT('state', 'purchase_Services', 'externalName=state,externalKey=shipmentId');

        $purchasesServicesDetQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');

        $purchasesServicesDetQuery->EXT('valior', 'purchase_Services', 'externalName=valior,externalKey=shipmentId');

        // $purchasesServicesDetQuery->EXT('storeId', 'purchase_Services', 'externalName=storeId,externalKey=shipmentId');

        //Продължителност на периода за показване
        $durationStr = cls::get('type_Time')->toVerbal($rec->duration);

        list($periodCount, $periodType) = explode(' ', $durationStr);

        //Край на избрания период за показване $dateEnd
        core_Lg::push('bg');

        if ($periodType == 'дни' || $periodType == 'ден' || $periodType == 'дена') {
            $dateEnd = dt::addDays($periodCount - 1, $rec->from, false);
        }

        if ($periodType == 'мес.') {
            $dateEnd = dt::addMonths($periodCount, $rec->from, false);
            $dateEnd = dt::addDays(-1, $dateEnd, false);
        }

        if ($periodType == 'год.') {
            $monts = 12 * $periodCount;
            $dateEnd = dt::addMonths($monts, $rec->from, false);
            $dateEnd = dt::addDays(-1, $dateEnd, false);
        }

        $rec->to = $dateEnd;

        //Когато е БЕЗ СРАВНЕНИЕ
        if (($rec->compare) == 'no') {
            $receiptsDetQuery->where("#valior >= '{$rec->from}' AND #valior <= '{$dateEnd}'");

            $fastPurchasesDetQuery->where("#valior >= '{$rec->from}' AND #valior <= '{$dateEnd}'");

            $shipmentOrdersDetQuery->where("#valior >= '{$rec->from}' AND #valior <= '{$dateEnd}'");

            $purchasesServicesDetQuery->where("#valior >= '{$rec->from}' AND #valior <= '{$dateEnd}'");
        }

        // сравнение с ПРЕДХОДЕН ПЕРИОД
        if (($rec->compare == 'previous')) {
            if ($periodType == 'дни') {
                $fromPreviuos = dt::addDays(-$periodCount, $rec->from, false);
                $toPreviuos = dt::addDays(-$periodCount, $dateEnd, false);
            }

            if ($periodType == 'мес.') {
                $fromPreviuos = dt::addMonths(-$periodCount, $rec->from, false);

                $toPreviuos = dt::addMonths($periodCount, $fromPreviuos, false);
                $toPreviuos = dt::addDays(-1, $toPreviuos, false);
            }

            if ($periodType == 'год.') {
                $monts = 12 * $periodCount;
                $fromPreviuos = dt::addMonths(-$monts, $rec->from, false);
                $toPreviuos = dt::addMonths($monts, $fromPreviuos, false);
                $toPreviuos = dt::addDays(-1, $toPreviuos, false);
            }

            $receiptsDetQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$dateEnd}') OR (#valior >= '{$fromPreviuos}' AND #valior <= '{$toPreviuos}')");

            $fastPurchasesDetQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$dateEnd}') OR (#valior >= '{$fromPreviuos}' AND #valior <= '{$toPreviuos}')");

            $shipmentOrdersDetQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$dateEnd}') OR (#valior >= '{$fromPreviuos}' AND #valior <= '{$toPreviuos}')");

            $purchasesServicesDetQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$dateEnd}') OR (#valior >= '{$fromPreviuos}' AND #valior <= '{$toPreviuos}')");
        }

        // сравнение с ПРЕДХОДНА ГОДИНА
        if (($rec->compare) == 'year') {
            $fromLastYear = dt::addMonths(-12, $rec->from);
            $toLastYear = dt::addMonths(-12, $dateEnd);

            $receiptsDetQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$dateEnd}') OR (#valior >= '{$fromLastYear}' AND #valior <= '{$toLastYear}')");

            $fastPurchasesDetQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$dateEnd}') OR (#valior >= '{$fromLastYear}' AND #valior <= '{$toLastYear}')");

            $shipmentOrdersDetQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$dateEnd}') OR (#valior >= '{$fromLastYear}' AND #valior <= '{$toLastYear}')");

            $purchasesServicesDetQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$dateEnd}') OR (#valior >= '{$fromLastYear}' AND #valior <= '{$toLastYear}')");
        }

        // сравнение с ИЗБРАН ПЕРИОД
        if (($rec->compare == 'checked')) {
            if ($periodType == 'дни') {
                $toChecked = dt::addDays($periodCount - 1, $rec->compareStart, false);
            }

            if ($periodType == 'мес.') {
                $toChecked = dt::addMonths($periodCount, $rec->compareStart, false);
                $toChecked = dt::addDays(-1, $toChecked, false);
            }

            if ($periodType == 'год.') {
                $monts = 12 * $periodCount;
                $toChecked = dt::addMonths($monts, $rec->compareStart, false);
                $toChecked = dt::addDays(-1, $toChecked, false);
            }

            $rec->toChecked = $toChecked;

            $receiptsDetQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$dateEnd}') OR (#valior >= '{$rec->compareStart}' AND #valior <= '{$toChecked}')");

            $fastPurchasesDetQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$dateEnd}') OR (#valior >= '{$rec->compareStart}' AND #valior <= '{$toChecked}')");

            $shipmentOrdersDetQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$dateEnd}') OR (#valior >= '{$rec->compareStart}' AND #valior <= '{$toChecked}')");

            $purchasesServicesDetQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$dateEnd}') OR (#valior >= '{$rec->compareStart}' AND #valior <= '{$toChecked}')");
        }

        core_Lg::pop();

        $receiptsDetQuery->where("#state != 'rejected'");

        $fastPurchasesDetQuery->where("#state != 'rejected'");

        $shipmentOrdersDetQuery->where("#state != 'rejected'");

        $purchasesServicesDetQuery->where("#state != 'rejected'");


        //Филтър за КОНТРАГЕНТ и ГРУПИ КОНТРАГЕНТИ
        if ($rec->contragent || $rec->crmGroup) {
            $contragentsArr = $contragentCoversId = $contragentCoverClasses = array();

            $receiptsDetQuery->EXT('contragentId', 'store_Receipts', 'externalName=contragentId,externalKey=receiptId');
            $receiptsDetQuery->EXT('contragentClassId', 'store_Receipts', 'externalName=contragentClassId,externalKey=receiptId');
            $receiptsDetQuery->EXT('folderId', 'store_Receipts', 'externalName=folderId,externalKey=receiptId');

            $fastPurchasesDetQuery->EXT('contragentId', 'purchase_Purchases', 'externalName=contragentId,externalKey=requestId');
            $fastPurchasesDetQuery->EXT('contragentClassId', 'purchase_Purchases', 'externalName=contragentClassId,externalKey=requestId');
            $fastPurchasesDetQuery->EXT('folderId', 'purchase_Purchases', 'externalName=folderId,externalKey=requestId');

            $shipmentOrdersDetQuery->EXT('contragentId', 'purchase_Purchases', 'externalName=contragentId,externalKey=shipmentId');
            $shipmentOrdersDetQuery->EXT('contragentClassId', 'purchase_Purchases', 'externalName=contragentClassId,externalKey=shipmentId');
            $shipmentOrdersDetQuery->EXT('folderId', 'purchase_Purchases', 'externalName=folderId,externalKey=shipmentId');

            $purchasesServicesDetQuery->EXT('contragentId', 'purchase_Services', 'externalName=contragentId,externalKey=shipmentId');
            $purchasesServicesDetQuery->EXT('contragentClassId', 'purchase_Services', 'externalName=contragentClassId,externalKey=shipmentId');
            $purchasesServicesDetQuery->EXT('folderId', 'purchase_Services', 'externalName=folderId,externalKey=shipmentId');

            if (!$rec->crmGroup && $rec->contragent) {
                $contragentsArr = keylist::toArray($rec->contragent);

                foreach ($contragentsArr as $val) {
                    $contragentCoversId[$val] = doc_Folders::fetch($val)->coverId;
                    $contragentCoverClasses[$val] = doc_Folders::fetch($val)->coverClass;
                }

                $receiptsDetQuery->in('contragentId', $contragentCoversId);
                $receiptsDetQuery->in('contragentClassId', $contragentCoverClasses);

                $fastPurchasesDetQuery->in('contragentId', $contragentCoversId);
                $fastPurchasesDetQuery->in('contragentClassId', $contragentCoverClasses);

                $shipmentOrdersDetQuery->in('contragentId', $contragentCoversId);
                $shipmentOrdersDetQuery->in('contragentClassId', $contragentCoverClasses);

                $purchasesServicesDetQuery->in('contragentId', $contragentCoversId);
                $purchasesServicesDetQuery->in('contragentClassId', $contragentCoverClasses);
            }

            if ($rec->crmGroup && !$rec->contragent) {
                $foldersInGroups = self::getFoldersInGroups($rec);

                $receiptsDetQuery->in('folderId', $foldersInGroups);

                $fastPurchasesDetQuery->in('folderId', $foldersInGroups);

                $shipmentOrdersDetQuery->in('folderId', $foldersInGroups);

                $purchasesServicesDetQuery->in('folderId', $foldersInGroups);
            }

            if ($rec->crmGroup && $rec->contragent) {
                $contragentsArr = keylist::toArray($rec->contragent);


                foreach ($contragentsArr as $val) {
                    $contragentCoversId[$val] = doc_Folders::fetch($val)->coverId;
                    $contragentCoverClasses[$val] = doc_Folders::fetch($val)->coverClass;
                }

                $receiptsDetQuery->in('contragentId', $contragentCoversId);
                $receiptsDetQuery->in('contragentClassId', $contragentCoverClasses);

                $fastPurchasesDetQuery->in('contragentId', $contragentCoversId);
                $fastPurchasesDetQuery->in('contragentClassId', $contragentCoverClasses);

                $shipmentOrdersDetQuery->in('contragentId', $contragentCoversId);
                $shipmentOrdersDetQuery->in('contragentClassId', $contragentCoverClasses);

                $purchasesServicesDetQuery->in('contragentId', $contragentCoversId);
                $purchasesServicesDetQuery->in('contragentClassId', $contragentCoverClasses);

                $foldersInGroups = self::getFoldersInGroups($rec);

                $receiptsDetQuery->in('folderId', $foldersInGroups);
                $fastPurchasesDetQuery->in('folderId', $foldersInGroups);
                $shipmentOrdersDetQuery->in('folderId', $foldersInGroups);
                $purchasesServicesDetQuery->in('folderId', $foldersInGroups);
            }
        }

        //Филтър по склад
        if ($rec->storeId) {
            $storesArr = keylist::toArray($rec->storeId);
            $receiptsDetQuery->in('storeId', $storesArr);
            $fastPurchasesDetQuery->in('storeId', $storesArr);
            $shipmentOrdersDetQuery->in('storeId', $storesArr);
            // $purchasesServicesDetQuery->in('storeId', $storesArr);
        }

        //Филтър по групи артикули
        if (isset($rec->group)) {

            plg_ExpandInput::applyExtendedInputSearch('cat_Products', $receiptsDetQuery, $rec->group, 'productId');
            plg_ExpandInput::applyExtendedInputSearch('cat_Products', $fastPurchasesDetQuery, $rec->group, 'productId');
            plg_ExpandInput::applyExtendedInputSearch('cat_Products', $shipmentOrdersDetQuery, $rec->group, 'productId');
            plg_ExpandInput::applyExtendedInputSearch('cat_Products', $purchasesServicesDetQuery, $rec->group, 'productId');

        }

        //Филтър по тип артикул СТАНДАРТНИ / НЕСТАНДАРТНИ
        if ($rec->articleType != 'all') {
            $receiptsDetQuery->where("#isPublic = '{$rec->articleType}'");
            $fastPurchasesDetQuery->where("#isPublic = '{$rec->articleType}'");
            $shipmentOrdersDetQuery->where("#isPublic = '{$rec->articleType}'");
            $purchasesServicesDetQuery->where("#isPublic = '{$rec->articleType}'");
        }

        // Синхронизира таймлимита с броя записи //
        $rec->count = $receiptsDetQuery->count() + $fastPurchasesDetQuery->count() + $shipmentOrdersDetQuery->count();

        $timeLimit = $receiptsDetQuery->count() * 0.05 + $fastPurchasesDetQuery->count() * 0.05;

        if ($timeLimit >= 30) {
            core_App::setTimeLimit($timeLimit);
        }

        //Масив избрани дилъри $dealers
        if ((min(array_keys(keylist::toArray($rec->dealers))) >= 1)) {
            $dealers = keylist::toArray($rec->dealers);
        }

        $recsArr = array($receiptsDetQuery, $fastPurchasesDetQuery, $shipmentOrdersDetQuery, $purchasesServicesDetQuery);

        foreach ($recsArr as $details) {
            while ($detRec = $details->fetch()) {

                $revCoef = 1; //коефициента става -1 при връщане на стока към доставчик със ЕН в Покупка

                $class = cls::get($details->mvc);

                //Когато документа е СР проверяваме дали е връщане от продажба
                //Ако ДА, то не влиза в справката
                if ($class instanceof store_ReceiptDetails) {
                    $firstDocument = doc_Threads::getFirstDocument($detRec->threadId);
                    if ($firstDocument->className == 'sales_Sales' && $detRec->isReverse == 'yes') {
                        continue;
                    }
                }

                //Когато документа е ЕН проверяваме дали е връщане от покупка към доставчик
                //Ако ДА, то не влиза в справката
                if ($class instanceof store_ShipmentOrderDetails) {

                    $revCoef = -1;
                    $firstDocument = doc_Threads::getFirstDocument($detRec->threadId);
                    if ($detRec->isReverse == 'no') {
                        continue;
                    }

                }

                $quantity = $amount = 0;
                $quantityPrevious = $amountPrevious = 0;
                $quantityLastYear = $amountLastYear = 0;
                $quantityCheckedPeriod = $amountCheckedPeriod = 0;


                //Филтър за ДИЛЪР
                if (!is_null($dealers)) {
                    $firstDocument = doc_Threads::getFirstDocument($detRec->threadId);

                    $thisClassName = $firstDocument->className;

                    $thisDealerId = $thisClassName::fetch($firstDocument->that)->dealerId;

                    if (!in_array($thisDealerId, $dealers)) {
                        continue;
                    }
                }

                //Ключ на масива
                $id = $detRec->productId;

                //Код на артикула
                $artCode = $detRec->code ? $detRec->code : "Art{$detRec->productId}";

                //Мярка на артикула
                $measureArt = cat_Products::getProductInfo($detRec->productId)->productRec->measureId;

                //Данни за ПРЕДХОДЕН ПЕРИОД
                if ($rec->compare == 'previous') {
                    if ($detRec->valior >= $fromPreviuos && $detRec->valior <= $toPreviuos) {
                        $quantityPrevious = $detRec->quantity * $revCoef;
                        $amountPrevious = $detRec->amount * $revCoef;
                    }
                }

                //Данни за ПРЕДХОДНА ГОДИНА
                if ($rec->compare == 'year') {
                    if ($detRec->valior >= $fromLastYear && $detRec->valior <= $toLastYear) {
                        $quantityLastYear = $detRec->quantity * $revCoef;
                        $amountLastYear = $detRec->amount * $revCoef;
                    }
                }

                //Данни за ИЗБРАН ПЕРИОД
                if ($rec->compare == 'checked') {
                    if ($detRec->valior >= $rec->compareStart && $detRec->valior <= $toChecked) {
                        $quantityCheckedPeriod = $detRec->quantity * $revCoef;
                        $amountCheckedPeriod = $detRec->amount * $revCoef;
                    }
                }

                //Данни за ТЕКУЩ период
                if ($detRec->valior >= $rec->from && $detRec->valior <= $dateEnd) {
                    $quantity = $detRec->quantity * $revCoef;
                    $amount = $detRec->amount * $revCoef;
                }

                // Запис в масива
                if (!array_key_exists($id, $recs)) {
                    $recs[$id] = (object)array(

                        'code' => $artCode,                                   //Код на артикула
                        'productId' => $detRec->productId,                    //Id на артикула
                        'measure' => $measureArt,                             //Мярка

                        'quantity' => $quantity,                              //Текущ период - количество
                        'amount' => $amount,                                  //Текущ период - стойност на продажбите за артикула

                        'quantityPrevious' => $quantityPrevious,              //Предходен период - количество
                        'amountPrevious' => $amountPrevious,                  //Предходен период - стойност на продажбите за артикула

                        'quantityLastYear' => $quantityLastYear,              //Предходна година - количество
                        'amountLastYear' => $amountLastYear,                  //Предходна година - стойност на продажбите за артикула

                        'quantityCheckedPeriod' => $quantityCheckedPeriod,    //Избран период - количество
                        'amountCheckedPeriod' => $amountCheckedPeriod,        //Избран период - стойност на продажбите за артикула

                        'group' => $detRec->groups,                           // В кои групи е включен артикула
                        'groupList' => $detRec->groupList,                    //В кои групи е включен контрагента

                    );
                } else {
                    $obj = &$recs[$id];

                    $obj->quantity += $quantity;
                    $obj->amount += $amount;

                    $obj->quantityPrevious += $quantityPrevious;
                    $obj->amountPrevious += $amountPrevious;

                    $obj->quantityLastYear += $quantityLastYear;
                    $obj->amountLastYear += $amountLastYear;

                    $obj->quantityCheckedPeriod += $quantityCheckedPeriod;
                    $obj->amountCheckedPeriod += $amountCheckedPeriod;
                }
            }
        }

        //Изчисляване на промяната в стойността на продажбите и делтите за артикул
        foreach ($recs as $v) {

            //Промяна на стийноста за артикула[$v->productId] за текущ период спрямо предходен
            $v->changeAmountPrevious = $v->amount - $v->amountPrevious;

            //Промяна на стийноста за артикула[$v->productId] за текущ период спрямо предходна година
            $v->changeAmountLastYear = $v->amount - $v->amountLastYear;

            //Промяна на стийноста за артикула[$v->productId] за текущ период спрямо избран период
            $v->changeAmountCheckedPeriod = $v->amount - $v->amountCheckedPeriod;
        }


        $groupValues = $groupAmountPrevious = $groupAmountLastYear = $groupAmountCheckedPeriod = array();
        $tempArr = array();
        $totalArr = array();
        $totalValue = 0;

        // Изчисляване на общите покупки и покупките по групи
        foreach ($recs as $v) {

            //Когато НЕ СА ИЗБРАНИ групи артикули
            if (!$rec->group) {
                if (keylist::isKeylist(($v->group))) {
                    $v->group = keylist::toArray($v->group); //Кейлиста с групите го записва като масив
                } else {
                    $v->group = array('Без група' => 'Без група'); //Ако артикула не е включен в групи записва 'Без група'
                }

                //Изчислява стойността на покупките от един артикул
                //за текущ, предходен период и предходна година във ВСЯКА ГРУПА В КОЯТО Е РЕГИСТРИРАН
                foreach ($v->group as $k => $gro) {
                    //За този артикул
                    $groupValues[$gro] += $v->amount;                               //Стойност на покупките за текущ период
                    $groupAmountPrevious[$gro] += $v->amountPrevious;               //Стойност на покупките за предходен период
                    $groupAmountLastYear[$gro] += $v->amountLastYear;               //Стойност на покупките за предходна година
                    $groupAmountCheckedPeriod[$gro] += $v->amountCheckedPeriod;     //Стойност на покупките за избрания период
                }
                unset($gro, $k);

                //изчислява обща стойност на всички артикули закупени
                //през текущ, предходен период и предходна година когато не е избрана група
                $totalValue += $v->amount;
                $totalAmountPrevious += $v->amountPrevious;
                $totalAmountLastYear += $v->amountLastYear;
                $totalAmountCheckedPeriod += $v->amountCheckedPeriod;
            } else {

                //КОГАТО ИМА ИЗБРАНИ ГРУПИ
                //изчислява обща стойност на артикулите от избраните групи купени
                //през текущ, предходен период и предходна година, и стойността по групи(само ИЗБРАНИТЕ)
                $grArr = array();

                //Масив с избраните групи
                $checkedGroups = keylist::toArray($rec->group);

                foreach ($checkedGroups as $key => $val) {
                    if (in_array($val, keylist::toArray($v->group))) {
                        $grArr[$val] = $val;                            //Масив от групите в които е ргистриран артикула АКО СА ЧАСТ ОТ ИЗБРАНИТЕ ГРУПИ
                    }
                }

                unset($key, $val);

                $tempArr[$v->productId] = $v;

                $tempArr[$v->productId]->group = $grArr; //Оставяме в записаСтойност" за артикула само групите които са избрани

                //изчислява ОБЩА стойност на всички артикули закупени
                //през текущ, предходен период и предходна година за ВСИЧКИ избрани групи

                if (!empty(array_intersect($grArr, $checkedGroups))) {
                    $totalValue += $v->amount;
                    $totalAmountPrevious += $v->amountPrevious;
                    $totalAmountLastYear += $v->amountLastYear;
                    $totalAmountCheckedPeriod += $v->amountCheckedPeriod;
                }

                //Изчислява покупките по артикул за всички артикули във всяка избрана група
                //Един артикул може да го има в няколко групи
                foreach ($tempArr[$v->productId]->group as $gro) {
                    $groupValues[$gro] += $v->amount;
                    $groupAmountPrevious[$gro] += $v->amountPrevious;
                    $groupAmountLastYear[$gro] += $v->amountLastYear;
                    $groupAmountCheckedPeriod[$gro] += $v->amountCheckedPeriod;
                }
                unset($gro);

                $recs = $tempArr;
            }

            if ($rec->compare && $rec->compare == 'previous') {
                $changeAmount = 'changeAmountPrevious';
            }

            if ($rec->compare && ($rec->compare == 'year')) {
                $changeAmount = 'changeAmountLastYear';
            }

            if ($rec->compare && ($rec->compare == 'checked')) {
                $changeAmount = 'changeAmountCheckedPeriod';
            }
        }

        //при избрани групи включва артикулите във всички групи в които са регистрирани
        if (!is_null($rec->group)) {
            $tempArr = array();

            foreach ($recs as $v) {
                foreach ($v->group as $val) {
                    $v = clone $v;
                    $v->group = (int)$val;
                    $tempArr[] = $v;
                }
            }
            unset($val, $v);

            $recs = $tempArr;

            foreach ($recs as $v) {
                $v->groupValues = $groupValues[$v->group];
                $v->groupAmountPrevious = $groupAmountPrevious[$v->group];
                $v->groupAmountLastYear = $groupAmountLastYear[$v->group];
                $v->groupAmountCheckedPeriod = $groupAmountCheckedPeriod[$v->group];
            }
            unset($v);
        } else {
            foreach ($recs as $v) {
                foreach ($v->group as $gro) {
                    $v->groupValues = $groupValues[$gro];

                    $v->groupAmountPrevious = $groupAmountPrevious[$gro];

                    $v->groupAmountLastYear = $groupAmountLastYear[$gro];

                    $v->groupAmountCheckedPeriod = $groupAmountCheckedPeriod[$gro];
                }
            }
            unset($v, $gro);
        }

        //Когато имаме избрано ГРУПИРАНО показване правим нов масив
        if ($rec->grouping == 'grouped') {
            $recs = array();

            foreach ($groupValues as $k => $v) {
                $recs[$k] = (object)array(
                    'group' => $k,                                                  //Група артикули
                    'amount' => $v,                                                 //Покупки за текущия период за групата

                    'groupAmountPrevious' => $groupAmountPrevious[$k],               //Покупки за предходен период за групата
                    'changeGroupAmountPrevious' => $v - $groupAmountPrevious[$k],             //Промяна в покупките спрямо предходен период за групата

                    'groupAmountLastYear' => $groupAmountLastYear[$k],                  //Покупки за предходна година за групата
                    'changeGroupAmountLastYear' => $v - $groupAmountLastYear[$k],             //Промяна в покупките спрямо предходна година за групата

                    'groupAmountCheckedPeriod' => $groupAmountCheckedPeriod[$k],        //Покупки за избрания период за групата
                    'changeGroupAmountCheckedPeriod' => $v - $groupAmountCheckedPeriod[$k],   //Промяна в покупките спрямо избрания период за групата
                );
            }

            if ($rec->compare && $rec->compare == 'previous') {
                $changeAmount = 'changeGroupAmountPrevious';
            }

            if ($rec->compare && ($rec->compare == 'year')) {
                $changeAmount = 'changeGroupAmountLastYear';
            }

            if ($rec->compare && ($rec->compare == 'checked')) {
                $changeAmount = 'changeGroupAmountCheckedPeriod';
            }
        }

        //Подредба на резултатите
        if (!empty($recs)) {
            $typeOrder = ($rec->orderBy == 'code') ? 'stri' : 'native';

            $orderBy = $rec->orderBy;

            if ($rec->orderBy == 'changeAmount') {
                $orderBy = $changeAmount;
            }
            $key = key($recs);
            if (property_exists($recs[$key], $orderBy)) {
                arr::sortObjects($recs, $orderBy, 'DESC', $typeOrder);
            }
        }

        //Добавям ред за ОБЩИТЕ суми
        $totalArr['total'] = (object)array(
            'totalValue' => $totalValue,
            'totalAmountPrevious' => $totalAmountPrevious,
            'totalAmountLastYear' => $totalAmountLastYear,
            'totalAmountCheckedPeriod' => $totalAmountCheckedPeriod,
        );

        array_unshift($recs, $totalArr['total']);

        return $recs;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec - записа
     * @param bool $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');

        $name1 = 'За периода';
        $name2 = 'За сравнение';

        if ($export === false) {

            //По артикули(без групиране)
            if ($rec->grouping == 'art') {
                $fld->FLD('code', 'varchar', 'caption=Код');
                $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
                $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');

                //Когато има сравнение
                if ($rec->compare != 'no') {
                    $fld->FLD('quantity', 'varchar', "smartCenter,caption={$name1}->Покупки");
                    $fld->FLD('amount', 'varchar', "smartCenter,caption={$name1}->Стойност");

                    $fld->FLD('quantityCompare', 'varchar', "smartCenter,caption={$name2}->Покупки,tdClass=newCol");
                    $fld->FLD('amountCompare', 'varchar', "smartCenter,caption={$name2}->Стойност,tdClass=newCol");

                    $fld->FLD('changePurchases', 'varchar', 'smartCenter,caption=Промяна-> Стойност');
                } else {

                    //Когато е без сравнение
                    $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Покупки->Количество');
                    $fld->FLD('amount', 'double(smartRound,decimals=2)', 'smartCenter,caption=Покупки->Стойност');
                }
            }

            //Обобщено по групи
            if ($rec->grouping == 'grouped') {

                //Когато има сравнение
                if ($rec->compare != 'no') {
                    $fld->FLD('group', 'varchar', 'caption=Група');
                    $fld->FLD('amount', 'varchar', "smartCenter,caption={$name1}->Стойност");

                    $fld->FLD('amountCompare', 'varchar', "smartCenter,caption={$name2}-> Стойност,tdClass=newCol");

                    $fld->FLD('changePurchases', 'varchar', 'smartCenter,caption=Промяна->Стойност');
                } else {

                    //Когато е без сравнение
                    $fld->FLD('group', 'varchar', 'caption=Група');
                    $fld->FLD('amount', 'varchar', "smartCenter,caption={$name1}->Стойност");
                }
            }
        } else {
            //По артикули(без групиране)
            if ($rec->grouping == 'art') {
                $fld->FLD('code', 'varchar', 'caption=Код');
                $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
                $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');

                //Когато има сравнение
                if ($rec->compare != 'no') {
                    $fld->FLD('quantity', 'double(decimals=2)', "smartCenter,caption={$name1}->Покупки");
                    $fld->FLD('amount', 'double(decimals=2)', "smartCenter,caption={$name1}->Стойност");

                    $fld->FLD('quantityCompare', 'double(decimals=2)', "smartCenter,caption={$name2}->Покупки,tdClass=newCol");
                    $fld->FLD('amountCompare', 'double(decimals=2)', "smartCenter,caption={$name2}->Стойност,tdClass=newCol");

                    $fld->FLD('changePurchases', 'double(decimals=2)', 'smartCenter,caption=Промяна-> Стойност');
                } else {

                    //Когато е без сравнение
                    $fld->FLD('quantity', 'double(decimals=2)', 'smartCenter,caption=Покупки->Количество');
                    $fld->FLD('amount', 'double(decimals=2)', 'smartCenter,caption=Покупки->Стойност');
                }
            }

            //Обобщено по групи
            if ($rec->grouping == 'grouped') {

                //Когато има сравнение
                if ($rec->compare != 'no') {
                    $fld->FLD('group', 'varchar', 'caption=Група');
                    $fld->FLD('amount', 'double(decimals=2)', "smartCenter,caption={$name1}->Стойност");

                    $fld->FLD('amountCompare', 'double(decimals=2)', "smartCenter,caption={$name2}-> Стойност,tdClass=newCol");

                    $fld->FLD('changePurchases', 'double(decimals=2)', 'smartCenter,caption=Промяна->Стойност');
                } else {

                    //Когато е без сравнение
                    $fld->FLD('group', 'varchar', 'caption=Група');
                    $fld->FLD('amount', 'double(decimals=2)', "smartCenter,caption={$name1}->Стойност");
                }
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
        if ($verbal === true) {
            if (is_numeric($dRec->group)) {
                $groupVal = $dRec->groupValues;

                $group = cat_Groups::getVerbal($dRec->group, 'name') . "<span class= 'fright'><span class= ''>" . 'Общо за групата ( стойност: ' . core_Type::getByName('double(decimals=2)')->toVerbal($groupVal) . '</span>';
            } else {
                $group = $dRec->group . "<span class= 'fright'>" . 'Общо за групата ( стойност: ' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupValues) . '</span>';
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
            if (isset($dRec->totalValue)) {
                $row->amount = '<b>' . $Double->toVerbal($dRec->totalValue) . '</b>';
                $row->amount = ht::styleNumber($row->amount, $dRec->totalValue);
            }
            if ($rec->grouping == 'grouped') {
                $row->group = '<b>' . 'ОБЩО ЗА ПЕРИОДА:' . '</b>';
            }

            if ($rec->compare != 'no') {
                $changePurchases = 0;

                if ($rec->compare == 'previous') {
                    $row->amountCompare = '<b>' . $Double->toVerbal($dRec->totalAmountPrevious) . '</b>';
                    $row->amountCompare = ht::styleNumber($row->amountCompare, $dRec->totalAmountPrevious);

                    $changePurchases = $dRec->totalValue - $dRec->totalAmountPrevious;
                    $row->changePurchases = '<b>' . $Double->toVerbal($changePurchases) . '</b>';
                    $row->changePurchases = ht::styleNumber($row->changePurchases, $changePurchases);
                }

                if ($rec->compare == 'year') {
                    $row->amountCompare = '<b>' . $Double->toVerbal($dRec->totalAmountLastYear) . '</b>';
                    $row->amountCompare = ht::styleNumber($row->amountCompare, $dRec->totalAmountLastYear);


                    $changePurchases = $dRec->totalValue - $dRec->totalAmountLastYear;
                    $row->changePurchases = '<b>' . $Double->toVerbal($changePurchases) . '</b>';
                    $row->changePurchases = ht::styleNumber($row->changePurchases, $changePurchases);
                }

                if ($rec->compare == 'checked') {
                    $row->amountCompare = '<b>' . $Double->toVerbal($dRec->totalAmountCheckedPeriod) . '</b>';
                    $row->amountCompare = ht::styleNumber($row->amountCompare, $dRec->totalAmountCheckedPeriod);


                    $changePurchases = $dRec->totalValue - $dRec->totalAmountCheckedPeriod;
                    $row->changePurchases = '<b>' . $Double->toVerbal($changePurchases) . '</b>';
                    $row->changePurchases = ht::styleNumber($row->changePurchases, $changePurchases);
                }
            }

            return $row;
        }

        //Ако имаме избрано показване "ГРУПИРАНО"
        if ($rec->grouping == 'grouped') {
            if (is_numeric($dRec->group)) {
                $row->group = cat_Groups::getVerbal($dRec->group, 'name');
            } else {
                $row->group = 'Без група';
            }
            $row->amount = $Double->toVerbal($dRec->amount);

            if ($rec->compare != 'no') {
                if ($rec->compare == 'previous') {
                    $row->amountCompare = $Double->toVerbal($dRec->groupAmountPrevious);
                    $row->amountCompare = ht::styleNumber($row->amountCompare, $dRec->groupAmountPrevious);


                    $row->changePurchases = $Double->toVerbal($dRec->changeGroupAmountPrevious);
                    $row->changePurchases = ht::styleNumber($row->changePurchases, $dRec->changeGroupAmountPrevious);
                }

                if ($rec->compare == 'year') {
                    $row->amountCompare = '<b>' . $Double->toVerbal($dRec->groupAmountLastYear) . '</b>';
                    $row->amountCompare = ht::styleNumber($row->amountCompare, $dRec->groupAmountLastYear);

                    $row->changePurchases = '<b>' . $Double->toVerbal($dRec->changeGroupAmountLastYear) . '</b>';
                    $row->changePurchases = ht::styleNumber($row->changePurchases, $dRec->changeGroupAmountLastYear);
                }

                if ($rec->compare == 'checked') {
                    $row->amountCompare = '<b>' . $Double->toVerbal($dRec->groupAmountCheckedPeriod) . '</b>';
                    $row->amountCompare = ht::styleNumber($row->amountCompare, $dRec->groupAmountCheckedPeriod);

                    $row->changePurchases = '<b>' . $Double->toVerbal($dRec->changeGroupAmountCheckedPeriod) . '</b>';
                    $row->changePurchases = ht::styleNumber($row->changePurchases, $dRec->changeGroupAmountCheckedPeriod);
                }
            }

            return $row;
        }


        //Ако имаме избрано показване "ПО АРТИКУЛИ" (без групиране)
        if ($rec->grouping == 'art') {
            if (isset($dRec->code)) {
                $row->code = $dRec->code;
            }
            if (isset($dRec->productId)) {
                $row->productId = cat_Products::getLinkToSingle_($dRec->productId, 'name');
            }
            if (isset($dRec->measure)) {
                $row->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
            }

            foreach (array(
                         'quantity',
                         'amount',
                     ) as $fld) {
                if (!isset($dRec->{$fld})) {
                    continue;
                }

                $row->{$fld} = $Double->toVerbal($dRec->{$fld});
                $row->{$fld} = ht::styleNumber($row->{$fld}, $dRec->{$fld});
            }

            $row->group = self::getGroups($dRec, true, $rec);

            if ($rec->compare != 'no') {
                if ($rec->compare == 'previous') {
                    $row->quantityCompare = $Double->toVerbal($dRec->quantityPrevious);
                    $row->quantityCompare = ht::styleNumber($row->quantityCompare, $dRec->quantityPrevious);

                    $row->amountCompare = $Double->toVerbal($dRec->amountPrevious);
                    $row->amountCompare = ht::styleNumber($row->amountCompare, $dRec->amountPrevious);

                    $row->changePurchases = $Double->toVerbal($dRec->changeAmountPrevious);
                    $row->changePurchases = ht::styleNumber($row->changePurchases, $dRec->changeAmountPrevious);
                }

                if ($rec->compare == 'year') {
                    $row->quantityCompare = $Double->toVerbal($dRec->quantityLastYear);
                    $row->quantityCompare = ht::styleNumber($row->quantityCompare, $dRec->quantityLastYear);

                    $row->amountCompare = $Double->toVerbal($dRec->amountLastYear);
                    $row->amountCompare = ht::styleNumber($row->amountCompare, $dRec->amountLastYear);

                    $row->changePurchases = $Double->toVerbal($dRec->changeAmountLastYear);
                    $row->changePurchases = ht::styleNumber($row->changePurchases, $dRec->changeAmountLastYear);
                }

                if ($rec->compare == 'checked') {
                    $row->quantityCompare = $Double->toVerbal($dRec->quantityCheckedPeriod);
                    $row->quantityCompare = ht::styleNumber($row->quantityCompare, $dRec->quantityCheckedPeriod);

                    $row->amountCompare = $Double->toVerbal($dRec->amountCheckedPeriod);
                    $row->amountCompare = ht::styleNumber($row->amountCompare, $dRec->amountCheckedPeriod);

                    $row->changePurchases = $Double->toVerbal($dRec->changeAmountCheckedPeriod);
                    $row->changePurchases = ht::styleNumber($row->changePurchases, $dRec->changeAmountCheckedPeriod);
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
        $Date = cls::get('type_Date');
        $groArr = array();

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


        $arrCompare = array(
            'no' => 'Без сравнение',
            'previous' => 'С предходен период',
            'year' => 'С миналогодишен период',
            'checked' => 'Избран период'
        );

        if ($rec->compare == 'checked') {
            $row->compare = $arrCompare[$rec->compare] . ' ( ' . $Date->toverbal($rec->compareStart) . ' - ' . $Date->toverbal($rec->toChecked) . ' )';
        } else {
            $row->compare = $arrCompare[$rec->compare];
        }
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
                                        <!--ET_BEGIN dealers--><div>|Търговци|*: [#dealers#]</div><!--ET_END dealers-->
                                        <!--ET_BEGIN contragent--><div>|Контрагент|*: [#contragent#]</div><!--ET_END contragent-->
                                        <!--ET_BEGIN crmGroup--><div>|Група контрагенти|*: [#crmGroup#]</div><!--ET_END crmGroup-->
                                        <!--ET_BEGIN group--><div>|Групи продукти|*: [#group#]</div><!--ET_END group-->
                                        <!--ET_BEGIN art--><div>|Артикули|*: [#art#]</div><!--ET_END art-->
                                        <!--ET_BEGIN compare--><div>|Сравнение|*: [#compare#]</div><!--ET_END compare-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));


        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' . $data->row->from . '</b>', 'from');
        }

        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $data->row->to . '</b>', 'to');
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

        if (isset($data->rec->article)) {
            $fieldTpl->append($data->rec->art, 'art');
        }

        if (isset($data->rec->compare)) {
            $fieldTpl->append('<b>' . $data->row->compare . '</b>', 'compare');
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
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;

        //Ако имаме избрано показване "ГРУПИРАНО"
        if ($rec->grouping == 'grouped') {
            if (is_numeric($dRec->group)) {
                $groupName = cat_Groups::getVerbal($dRec->group, 'name');
                $res->group = cat_Groups::getVerbal($dRec->group, 'name');
            } else {
                $res->group = 'Без група';
            }
        }

        $res->amount = $Double->toVerbal($dRec->amount);

        if ($rec->compare != 'no') {
            if ($rec->compare == 'previous') {
                $res->amountCompare = ($dRec->groupAmountPrevious);

                $res->changePurchases = ($dRec->changeGroupAmountPrevious);
            }

            if ($rec->compare == 'year') {
                $res->amountCompare = '<b>' . ($dRec->groupAmountLastYear) . '</b>';


                $res->changePurchases = '<b>' . ($dRec->changeGroupAmountLastYear) . '</b>';
            }

            if ($rec->compare == 'checked') {
                $res->amountCompare = '<b>' . ($dRec->groupAmountCheckedPeriod) . '</b>';


                $res->changePurchases = '<b>' . ($dRec->changeGroupAmountCheckedPeriod) . '</b>';
            }
        }


        if ($rec->grouping == 'art') {
            if ($rec->compare != 'no') {
                if ($rec->compare == 'previous') {
                    $res->quantityCompare = ($dRec->quantityPrevious);
                    $res->amountCompare = ($dRec->amountPrevious);
                    $res->changePurchases = ($dRec->changeAmountPrevious);
                }

                if ($rec->compare == 'year') {
                    $res->quantityCompare = ($dRec->quantityLastYear);
                    $res->amountCompare = ($dRec->amountLastYear);
                    $res->changePurchases = ($dRec->changeAmountLastYear);
                }

                if ($rec->compare == 'checked') {
                    $res->quantityCompare = ($dRec->quantityCheckedPeriod);
                    $res->amountCompare = ($dRec->amountCheckedPeriod);
                    $res->changePurchases = ($dRec->changeAmountCheckedPeriod);
                }
            }
        }
    }

    /*
     * Връща folderId-тата на всички контрагенти,
     * които имат регистрация в поне една от избраните групи
     *
     * @param stdClass            $rec
     *
     * @return array
     */
    public static function getFoldersInGroups($rec)
    {
        $foldersInGroups = array();

        $fQuery = doc_Folders::getQuery();

        $classIds = array(core_Classes::getId('crm_Companies'), core_Classes::getId('crm_Persons'));

        $fQuery->in('coverClass', $classIds);

        while ($contr = $fQuery->fetch()) {
            $className = core_Classes::getName($contr->coverClass);

            $contrGroups = $className::fetchField($contr->coverId, 'groupList'); //Групите в които е регистриран контрагента

            if (keylist::isIn(keylist::toArray($contrGroups), $rec->crmGroup)) {
                $foldersInGroups[$contr->id] = $contr->id;
            }
        }

        return $foldersInGroups;
    }
}
