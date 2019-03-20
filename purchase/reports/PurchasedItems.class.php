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
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var string
     */
    protected $newFieldToCheck;
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;
    
    
    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'from,to,compare,group,dealers,contragent,crmGroup,articleType,seeDelta';
    
    
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
        $fieldset->FLD('duration','time(suggestions=1 седмица| 1 месец| 2 месеца| 3 месеца| 6 месеца| 12 месеца)', 'caption=Период->Продължителност,after=from,placeholder=1 месец,single=none,mandatory');
        
        //Сравнение
        $fieldset->FLD('compare', 'enum(no=Без, previous=Предходен, year=Миналогодишен,checked=Избран)', 'caption=Сравнение->Сравнение,after=duration,refreshForm,single=none,silent');
        $fieldset->FLD('compareStart', 'date', 'caption=Сравнение->Начало,after=compare,single=none,silent');
        
        //Контрагенти и групи контрагенти
        $fieldset->FLD('contragent', 'keylist(mvc=doc_Folders,select=title,allowEmpty)', 'caption=Контрагенти->Контрагент,single=none,after=compareDuration');
        $fieldset->FLD('crmGroup', 'keylist(mvc=crm_Groups,select=name)', 'caption=Контрагенти->Група контрагенти,after=contragent,single=none');
        
        //Групиране на резултата
        $fieldset->FLD('group', 'keylist(mvc=cat_Groups,select=name)', 'caption=Артикули->Група артикули,after=crmGroup,single=none');
        $fieldset->FLD('articleType', 'enum(yes=Стандартни,no=Нестандартни,all=Всички)', 'caption=Артикули->Тип артикули,after=group,single=none');
        
        //Покаване на резултата
        $fieldset->FLD('grouping', 'enum(summary=Обобщено,level1=1-во ниво,level2=2-ро ниво,detail=Подробно, art=По артикули)', 'caption=Показване->Вид,maxRadio=2,after=articleType');
   
        //Подредба на резултатите
        $fieldset->FLD('orderBy', 'enum(code=Код, amount=Стойност, price=Цена)', 'caption=Подреждане на резултата->Показател,maxRadio=5,columns=3,after=grouping');
     
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_Form         $form
     * @param stdClass          $data
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted()) {
            
            //Проверка за правилна подредба
            if (($form->rec->orderBy == 'code') && ($form->rec->grouping == 'yes')) {
                $form->setError('orderBy', 'При ГРУПИРАНО показване не може да има подредба по КОД.');
            }
            
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        $suggestions = array();
        
        if ($rec->compare != 'checked') {
            $form->setField('compareStart', 'input=none');
         //   $form->setField('compareDuration', 'input=none');
           
        }
        
        $form->setDefault('articleType', 'all');
        
        $form->setDefault('compare', 'no');
        
        $form->setDefault('grouping', 'no');
        
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
        if (($rec->grouping == 'no') && $rec->group) {
            $this->groupByField = 'group';
        }
        
        $recs = array();
        $purchasesThreads = $purchasesFastThreads = array();
        
        //ПОКУПКИ
        $purchasesQuery = purchase_Purchases::getQuery();
        
        $purchasesQuery->where("#state != 'rejected'");
        
         $purchasesQuery->show('threadId,contoActions');
         
        while ($purchase = $purchasesQuery->fetch()){
            
             
            if($purchase->contoActions == ''){
                
                //Масив с нишките на НЕбързите покупки
                
                if (!in_array($purchase->threadId, $purchasesThreads)){
                    $purchasesThreads[$purchase->threadId]= $purchase->threadId;
                }
                
            }else {
                
                //Масив с нишките на бързите продажби
                if (!in_array($purchase->threadId, $purchasesFastThreads)){
                    $purchasesFastThreads[$purchase->threadId]= $purchase->threadId;
                }
            }
            
        }
        
        //Складови разписки
        $receiptsDetQuery = store_ReceiptDetails::getQuery();
        
        $receiptsDetQuery->EXT('threadId', 'store_Receipts', 'externalName=threadId,externalKey=receiptId');
        
        $receiptsDetQuery-> in('threadId',$purchasesThreads);
        
        $receiptsDetQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
        
        $receiptsDetQuery->EXT('state', 'store_Receipts', 'externalName=state,externalKey=receiptId');
        
        $receiptsDetQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
        
        $receiptsDetQuery->EXT('valior', 'store_Receipts', 'externalName=valior,externalKey=receiptId');
        
        //Продължителност на периода за показване
        $durationStr = cls::get('type_Time')->toVerbal($rec->duration);
            
        list($periodCount, $periodType)= explode(' ', $durationStr);
        
        //Край на избрания период за показване $dateEnd
        core_Lg::push('bg');
        
        if ($periodType == 'дни'){
            $dateEnd = dt::addDays($periodCount-1, $rec->from, false);
        }
        
        if ($periodType == 'мес.'){
            $dateEnd = dt::addMonths($periodCount, $rec->from, false);
            $dateEnd = dt::addDays(-1, $dateEnd, false);
        }
        
        if ($periodType == 'год.'){
            
            $monts = 12*$periodCount;
            $dateEnd = dt::addMonths($monts, $rec->from, false);
            $dateEnd = dt::addDays(-1, $dateEnd, false);
        }
        
        //Когато е БЕЗ СРАВНЕНИЕ
        if (($rec->compare) == 'no') {
         
            $receiptsDetQuery->where("#valior >= '{$rec->from}' AND #valior <= '{$dateEnd}'");
        }
        
        // сравнение с ПРЕДХОДЕН ПЕРИОД
        if (($rec->compare == 'previous')) {
            
            if ($periodType == 'дни'){
                
                $fromPreviuos = dt::addDays(-$periodCount, $rec->from, false);
                $toPreviuos = dt::addDays(-$periodCount, $dateEnd, false);
            }
            
            if ($periodType == 'мес.'){
                
                $fromPreviuos = dt::addMonths(-$periodCount, $rec->from, false);
                
                $toPreviuos = dt::addMonths($periodCount, $fromPreviuos, false);
                $toPreviuos = dt::addDays(-1, $toPreviuos, false);
            }
            
            if ($periodType == 'год.'){
                
                $monts = 12*$periodCount;
                $fromPreviuos = dt::addMonths(-$monts, $rec->from, false);
                $toPreviuos = dt::addMonths($monts, $fromPreviuos, false);
                $toPreviuos = dt::addDays(-1, $toPreviuos, false);
            }
            
            $receiptsDetQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$dateEnd}') OR (#valior >= '{$fromPreviuos}' AND #valior <= '{$toPreviuos}')");
        }
        
        // сравнение с ПРЕДХОДНА ГОДИНА
        if (($rec->compare) == 'year') {
            
            $fromLastYear = dt::addMonths(-12, $rec->from);
            $toLastYear = dt::addMonths(-12, $dateEnd);
            
            $receiptsDetQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$dateEnd}') OR (#valior >= '{$fromLastYear}' AND #valior <= '{$toLastYear}')");
        }
        
        // сравнение с ИЗБРАН ПЕРИОД
        if (($rec->compare == 'checked')) {
           
            if ($periodType == 'дни'){
                $toChecked = dt::addDays($periodCount-1, $rec->compareStart, false);
                
            }
            
            if ($periodType == 'мес.'){
                $toChecked = dt::addMonths($periodCount, $rec->compareStart, false);
                $toChecked = dt::addDays(-1, $toChecked, false);
            }
            
            if ($periodType == 'год.'){
                
                $monts = 12*$periodCount;
                $toChecked = dt::addMonths($monts, $rec->compareStart, false);
                $toChecked = dt::addDays(-1, $toChecked, false);
            }
                $receiptsDetQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$dateEnd}') OR (#valior >= '{$rec->compareStart}' AND #valior <= '{$toChecked}')");
        }
       
        core_Lg::pop();
        
        $receiptsDetQuery->where("#state != 'rejected'");
        
      
        //Филтър за КОНТРАГЕНТ и ГРУПИ КОНТРАГЕНТИ
        if ($rec->contragent || $rec->crmGroup) {
            
            $contragentsArr = $contragentCoversId = $contragentCoverClasses = array();
            
            $receiptsDetQuery->EXT('contragentId', 'store_Receipts', 'externalName=contragentId,externalKey=receiptId');
            $receiptsDetQuery->EXT('contragentClassId', 'store_Receipts', 'externalName=contragentClassId,externalKey=receiptId');
            $receiptsDetQuery->EXT('folderId', 'store_Receipts', 'externalName=folderId,externalKey=receiptId');
            
            if (!$rec->crmGroup && $rec->contragent) {
                $contragentsArr = keylist::toArray($rec->contragent);
                
                foreach ($contragentsArr as $val) {
                    $contragentCoversId[$val] = doc_Folders::fetch($val)->coverId;
                    $contragentCoverClasses[$val] = doc_Folders::fetch($val)->coverClass;
                }
                
                $receiptsDetQuery->in('contragentId', $contragentCoversId);
                $receiptsDetQuery->in('contragentClassId', $contragentCoverClasses);
                
            }
            
            if ($rec->crmGroup && !$rec->contragent) {
                
                $foldersInGroups = self::getFoldersInGroups($rec);
                
                $receiptsDetQuery->in('folderId', $foldersInGroups);
            }
            
            if ($rec->crmGroup && $rec->contragent) {
                $contragentsArr = keylist::toArray($rec->contragent);
                
                
                foreach ($contragentsArr as $val) {
                    $contragentCoversId[$val] = doc_Folders::fetch($val)->coverId;
                    $contragentCoverClasses[$val] = doc_Folders::fetch($val)->coverClass;
                }
                
                $receiptsDetQuery->in('contragentId', $contragentCoversId);
                $receiptsDetQuery->in('contragentClassId', $contragentCoverClasses);
                
                $foldersInGroups = self::getFoldersInGroups($rec);
                
                $receiptsDetQuery->in('folderId', $foldersInGroups);
            }
        }
    
        //Филтър по групи артикули
        if (isset($rec->group)) {
            $receiptsDetQuery->likeKeylist('groups', $rec->group);
        }
        
        
        //Филтър по тип артикул СТАНДАРТНИ / НЕСТАНДАРТНИ
        if ($rec->articleType != 'all') {
            $receiptsDetQuery->where("#isPublic = '{$rec->articleType}'");
        }
        
        // Синхронизира таймлимита с броя записи //
        $rec->count = $receiptsDetQuery->count();
        
        $timeLimit = $receiptsDetQuery->count() * 0.05;
        
        if ($timeLimit >= 30) {
            core_App::setTimeLimit($timeLimit);
        }
        
        //Масив избрани дилъри $dealers
        if ((min(array_keys(keylist::toArray($rec->dealers))) >= 1)) {
            $dealers = keylist::toArray($rec->dealers);
        }
        
        while ($receiptsDetRec = $receiptsDetQuery->fetch()) {
            
            $quantity = $amount = 0;
            $quantityPrevious = $amountPrevious = 0;
            $quantityLastYear = $amountLastYear = 0;
            $quantityCheckedPeriod = $amountCheckedPeriod = 0;
            
            
            //Филтър за ДИЛЪР
            if (!is_null($dealers)) {
                
            $firstDocument = doc_Threads::getFirstDocument($receiptsDetRec->threadId);
            
            $thisClassName = $firstDocument->className;
            
            $thisDealerId = $thisClassName::fetch($firstDocument->that)->dealerId;
           
                if(!in_array($thisDealerId, $dealers) ){
                    continue;
                }
            }
           
            //Ключ на масива
            $id = $receiptsDetRec->productId;
            
            //Код на артикула
            $artCode = $receiptsDetRec->code ? $receiptsDetRec->code : "Art{$receiptsDetRec->productId}";
            
            //Мярка на артикула
            $measureArt = cat_Products::getProductInfo($receiptsDetRec->productId)->productRec->measureId;
            
            //Данни за ПРЕДХОДЕН ПЕРИОД
            if ($rec->compare == 'previous') {
                
                if ($receiptsDetRec->valior >= $fromPreviuos && $receiptsDetRec->valior <= $toPreviuos) {
                    
                    $quantityPrevious = $receiptsDetRec->quantity;
                    $amountPrevious = $receiptsDetRec->amount;
                   
                }
            }
            
            //Данни за ПРЕДХОДНА ГОДИНА
            if ($rec->compare == 'year') {
                
                if ($receiptsDetRec->valior >= $fromLastYear && $receiptsDetRec->valior <= $toLastYear) {
                    
                        $quantityLastYear = $receiptsDetRec->quantity;
                        $amountLastYear = $receiptsDetRec->amount;
                       
                }
            }
            
            //Данни за ИЗБРАН ПЕРИОД
            if ($rec->compare == 'checked') {
                
                if ($receiptsDetRec->valior >= $rec->compareStart && $receiptsDetRec->valior <= $toChecked) {
                    
                    $quantityCheckedPeriod = $receiptsDetRec->quantity;
                    $amountCheckedPeriod = $receiptsDetRec->amount; 
                    
                }
            }
           
            //Данни за ТЕКУЩ период
            if ($receiptsDetRec->valior >= $rec->from && $receiptsDetRec->valior <= $dateEnd) {
                
                    $quantity = $receiptsDetRec->quantity;
                    $amount = $receiptsDetRec->amount;
            }
            
            // Запис в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    
                    'code' => $artCode,                                   //Код на артикула
                    'productId' => $receiptsDetRec->productId,            //Id на артикула
                    'measure' => $measureArt,                             //Мярка
                    
                    'quantity' => $quantity,                              //Текущ период - количество
                    'amount' => $amount,                                  //Текущ период - стойност на продажбите за артикула
                    
                    'quantityPrevious' => $quantityPrevious,              //Предходен период - количество
                    'amountPrevious' => $amountPrevious,                  //Предходен период - стойност на продажбите за артикула
                    
                    'quantityLastYear' => $quantityLastYear,              //Предходна година - количество
                    'amountLastYear' => $amountLastYear,                  //Предходна година - стойност на продажбите за артикула
                    
                    'quantityCheckedPeriod' => $quantityCheckedPeriod,    //Избран период - количество
                    'amountCheckedPeriod' => $amountCheckedPeriod,        //Избран период - стойност на продажбите за артикула
                    
                    'group' => $receiptsDetRec->groups,                   // В кои групи е включен артикула
                    'groupList' => $receiptsDetRec->groupList,            //В кои групи е включен контрагента
                    
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
        } bp($recs);
       
       
        
        return $recs;
    }
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *                         - записа
     * @param bool     $export
     *                         - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        
      
                $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
                $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
               
        
        return $fld;
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
        
       
            return $row;
       
    }
    
    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param core_ET             $tpl
     * @param stdClass            $data
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
      
        
       
    }
    
    
    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass            $res
     * @param stdClass            $rec
     * @param stdClass            $dRec
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        
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
        
        $classIds = array(core_Classes::getId('crm_Companies'),core_Classes::getId('crm_Persons'));
        
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
