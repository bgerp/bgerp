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
    protected $changeableFields = 'from,to,compare,group,dealers,contragent,crmGroup,articleType';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('compare', 'enum(no=Без, previous=Предходен,month=По месеци, year=Миналогодишен)', 'caption=Сравнение,after=title,refreshForm,single=none,silent');
        $fieldset->FLD('from', 'date', 'caption=От,after=compare,single=none,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,mandatory');
        $fieldset->FLD('firstMonth', 'key(mvc=acc_Periods,select=title)', 'caption=Месец 1,after=compare,single=none,input=none');
        $fieldset->FLD('secondMonth', 'key(mvc=acc_Periods,select=title)', 'caption=Месец 2,after=firstMonth,single=none,input=none');
        $fieldset->FLD('dealers', 'users(rolesForAll=ceo|repAllGlobal, rolesForTeams=ceo|manager|repAll|repAllGlobal)', 'caption=Търговци,single=none,after=to,mandatory');
        $fieldset->FLD('contragent', 'keylist(mvc=doc_Folders,select=title,allowEmpty)', 'caption=Контрагенти->Контрагент,single=none,after=dealers');
        $fieldset->FLD('crmGroup', 'keylist(mvc=crm_Groups,select=name)', 'caption=Контрагенти->Група контрагенти,after=contragent,single=none');
        $fieldset->FLD('group', 'keylist(mvc=cat_Groups,select=name)', 'caption=Артикули->Група артикули,after=crmGroup,single=none');
        $fieldset->FLD('articleType', 'enum(yes=Стандартни,no=Нестандартни,all=Всички)', 'caption=Артикули->Тип артикули,maxRadio=3,columns=3,after=group,single=none');
        $fieldset->FLD('grouping', 'enum(yes=Групирано, no=По артикули)', 'caption=Показване,maxRadio=2,after=articleType,single=none');
        
        // $fieldset->FLD('contragent', 'key2(mvc=doc_Folders,select=title,allowEmpty, restrictViewAccess=yes,coverInterface=crm_ContragentAccRegIntf)', 'caption=Контрагент,single=none,after=dealers');
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
        
        if ($rec->compare == 'month') {
            $form->setField('from', 'input=hidden');
            $form->setField('to', 'input=hidden');
            $form->setField('firstMonth', 'input');
            $form->setField('secondMonth', 'input');
        }
        
        $monthSugg = (acc_Periods::fetchByDate(dt::today())->id);
        
        $form->setDefault('firstMonth', $monthSugg);
        
        $form->setDefault('secondMonth', $monthSugg);
        
        $form->setDefault('articleType', 'all');
        
        $form->setDefault('compare', 'no');
        
        $form->setDefault('grouping', 'no');
        
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
        if (($rec->grouping == 'no') && $rec->group) {
            $this->groupByField = 'group';
        }
        
        $recs = array();
        
        $query = sales_PrimeCostByDocument::getQuery();
        
        $query->EXT('groupMat', 'cat_Products', 'externalName=groups,externalKey=productId');
        
        $query->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
        
        $query->where("#state != 'rejected'");
        
        //не е бърза продажба//
        $query->where('#sellCost IS NOT NULL');
        
        if (($rec->compare) == 'no') {
            $query->where("#valior >= '{$rec->from}' AND #valior <= '{$rec->to}'");
        }
        
        // Last период && By months
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
        
        // LastYear период
        if (($rec->compare) == 'year') {
            $fromLastYear = dt::addDays(-365, $rec->from);
            $toLastYear = dt::addDays(-365, $rec->to);
            
            $query->where("(#valior >= '{$rec->from}' AND #valior <= '{$rec->to}') OR (#valior >= '{$fromLastYear}' AND #valior <= '{$toLastYear}')");
        }
        
        $query->where("#state != 'rejected'");
        
        if (isset($rec->dealers)) {
            if ((min(array_keys(keylist::toArray($rec->dealers))) >= 1)) {
                $dealers = keylist::toArray($rec->dealers);
                
                $query->in('dealerId', $dealers);
            }
        }
        
        if ($rec->contragent || $rec->crmGroup) {
            $contragentsArr = array();
            $contragentsId = array();
            
            $query->EXT('coverId', 'doc_Folders', 'externalKey=folderId');
            $query->EXT('groupList', 'crm_Companies', 'externalFieldName=folderId, externalKey=folderId');
            
            if (!$rec->crmGroup && $rec->contragent) {
                $contragentsArr = keylist::toArray($rec->contragent);
                
                foreach ($contragentsArr as $val) {
                    $contragentsId[doc_Folders::fetch($val)->coverId] = doc_Folders::fetch($val)->coverId;
                }
                
                $query->in('coverId', $contragentsId);
            }
            
            if ($rec->crmGroup && !$rec->contragent) {
                $query->likeKeylist('groupList', $rec->crmGroup);
            }
            
            if ($rec->crmGroup && $rec->contragent) {
                $contragentsArr = keylist::toArray($rec->contragent);
                
                foreach ($contragentsArr as $val) {
                    $contragentsId[doc_Folders::fetch($val)->coverId] = doc_Folders::fetch($val)->coverId;
                }
                
                $query->in('coverId', $contragentsId);
                
                $query->likeKeylist('groupList', $rec->crmGroup);
            }
        }
        
        if (isset($rec->group)) {
            $query->likeKeylist('groupMat', $rec->group);
        }
        
        if ($rec->articleType != 'all') {
            $query->where("#isPublic = '{$rec->articleType}'");
        }
        
        // Синхронизира таймлимита с броя записи //
        $rec->count = $query->count();
        
        $timeLimit = $query->count() * 0.05;
        
        if ($timeLimit >= 30) {
            core_App::setTimeLimit($timeLimit);
        }
        
        $num = 1;
        $quantity = 0;
        $flag = false;
        
        while ($recPrime = $query->fetch()) {
            $quantity = $primeCost = $delta = 0;
            $quantityPrevious = $primeCostPrevious = $deltaPrevious = 0;
            $quantityLastYear = $primeCostLastYear = $deltaLastYear = 0;
            
            $DetClass = cls::get($recPrime->detailClassId);
            
            $id = $recPrime->productId;
            
            if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                if ($recPrime->valior >= $fromPreviuos && $recPrime->valior <= $toPreviuos) {
                    if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {
                        $quantityPrevious = (-1) * $recPrime->quantity;
                        $primeCostPrevious = (-1) * $recPrime->sellCost * $recPrime->quantity;
                        $deltaPrevious = (-1) * $recPrime->delta;
                    } else {
                        $quantityPrevious = $recPrime->quantity;
                        $primeCostPrevious = $recPrime->sellCost * $recPrime->quantity;
                        $deltaPrevious = $recPrime->delta;
                    }
                }
            }
            
            if ($rec->compare == 'year') {
                if ($recPrime->valior >= $fromLastYear && $recPrime->valior <= $toLastYear) {
                    if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {
                        $quantityLastYear = (-1) * $recPrime->quantity;
                        $primeCostLastYear = (-1) * $recPrime->sellCost * $recPrime->quantity;
                        $deltaLastYear = (-1) * $recPrime->delta;
                    } else {
                        $quantityLastYear = $recPrime->quantity;
                        $primeCostLastYear = $recPrime->sellCost * $recPrime->quantity;
                        $deltaLastYear = $recPrime->delta;
                    }
                }
            }
            
            if ($recPrime->valior >= $rec->from && $recPrime->valior <= $rec->to) {
                if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {
                    $quantity = (-1) * $recPrime->quantity;
                    
                    $primeCost = (-1) * $recPrime->sellCost * $recPrime->quantity;
                    
                    $delta = (-1) * $recPrime->delta;
                } else {
                    $quantity = $recPrime->quantity;
                    
                    $primeCost = $recPrime->sellCost * $recPrime->quantity;
                    
                    $delta = $recPrime->delta;
                }
            }
            
            // добавяме в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    
                    'code' => $recPrime->code ? $recPrime->code : "Art{$recPrime->productId}",
                    'measure' => cat_Products::getProductInfo($recPrime->productId)->productRec->measureId,
                    'productId' => $recPrime->productId,
                    'quantity' => $quantity,
                    'quantityPrevious' => $quantityPrevious,
                    'primeCostPrevious' => $primeCostPrevious,
                    'deltaPrevious' => $deltaPrevious,
                    'quantityLastYear' => $quantityLastYear,
                    'primeCostLastYear' => $primeCostLastYear,
                    'deltaLastYear' => $deltaLastYear,
                    'primeCost' => $primeCost,
                    'group' => $recPrime->groupMat,
                    'groupList' => $recPrime->groupList,
                    'delta' => $delta
                
                );
            } else {
                $obj = &$recs[$id];
                $obj->quantity += $quantity;
                $obj->quantityPrevious += $quantityPrevious;
                $obj->quantityLastYear += $quantityLastYear;
                $obj->primeCost += $primeCost;
                $obj->delta += $delta;
            }
        }
        
        $groupValues = array();
        $groupDeltas = array();
        $tempArr = array();
        $totalArr = array();
        
        $totalValue = $totalDelta = 0;
        
        
        foreach ($recs as $v) {
            
            if (!$rec->group) {
                if (keylist::isKeylist(($v->group))) {
                    $v->group = keylist::toArray($v->group);
                } else {
                    $v->group = array('Без група' => 'Без група');
                }
                
                unset($gro);
                foreach ($v->group as $k => $gro) {
                    $groupValues[$gro] += $v->primeCost;
                    $groupDeltas[$gro] += $v->delta;
                    $groupPrimeCostPrevious[$gro] += $v->primeCostPrevious;
                    $groupDeltaPrevious[$gro] += $v->deltaPrevious;
                    $groupprimeCostLastYear[$gro] += $v->primeCostLastYear;
                    $groupDeltaLastYear[$gro] += $v->deltaLastYear;
                }
                
                
                //изчислява обща стойност на всички артикули продадени
                //през избрания период когато не е избрана група
                $totalValue += $v->primeCost;
                $totalDelta += $v->delta;
                $totalPrimeCostPrevious += $v->primeCostPrevious;
                $totalDeltaPrevious += $v->deltaPrevious;
                $totalPrimeCostLastYear += $v->primeCostLastYear;
                $totalDeltaLastYear += $v->deltaLastYear;
            } else {
                
                //изчислява обща стойност на артикулите от избраните грули продадени
                //през избрания период, и стойността по групи
                $grArr = array();
                
                unset($key,$val);
                foreach (keylist::toArray($rec->group) as $key => $val) {
                    if (in_array($val, keylist::toArray($v->group))) {
                        $grArr[$val] = $val;
                    }
                }
                
                $tempArr[$v->productId] = $v;
                
                $tempArr[$v->productId]->group = $grArr;
                
                $totalValue += $v->primeCost;
                $totalDelta += $v->delta;
                $totalPrimeCostPrevious += $v->primeCostPrevious;
                $totalDeltaPrevious += $v->deltaPrevious;
                $totalPrimeCostLastYear += $v->primeCostLastYear;
                $totalDeltaLastYear += $v->deltaLastYear;
                
                unset($gro);
                foreach ($tempArr[$v->productId]->group as $gro) {
                    $groupValues[$gro] += $v->primeCost;
                    $groupDeltas[$gro] += $v->delta;
                    $groupPrimeCostPrevious[$gro] += $v->primeCostPrevious;
                    $groupDeltaPrevious[$gro] += $v->deltaPrevious;
                    $groupprimeCostLastYear[$gro] += $v->primeCostLastYear;
                    $groupDeltaLastYear[$gro] += $v->deltaLastYear;
                }
                $recs = $tempArr;
            }
        }
        
        
        //при избрани групи включва артикулите във всички групи в които са регистрирани
        if (!is_null($rec->group)) {
            $tempArr = array();
            
            unset($val,$v);
            foreach ($recs as $v) {
                foreach ($v->group as $val) {
                    $v = clone $v;
                    $v->group = (int) $val;
                    $tempArr[] = $v;
                }
            }
           
            $recs = $tempArr;
            
            unset($v);
            foreach ($recs as $v) {
                $v->groupValues = $groupValues[$v->group];
                $v->groupDeltas = $groupDeltas[$v->group];
                $v->groupPrimeCostPrevious = $groupPrimeCostPrevious[$v->group];
                $v->groupDeltaPrevious = $groupDeltaPrevious[$v->group];
                $v->groupPrimeCostLastYear = $groupPrimeCostLastYear[$v->group];
                $v->groupDeltaLastYear = $groupDeltaLastYear[$v->group];
            }
        } else {
            unset($v,$gro);
            foreach ($recs as $v) {
                foreach ($v->group as $gro) {
                    $v->groupValues = $groupValues[$gro];
                    $v->groupDeltas = $groupDeltas[$gro];
                    $v->groupPrimeCostPrevious = $groupPrimeCostPrevious[$gro];
                    $v->groupDeltaPrevious = $groupDeltaPrevious[$gro];
                    $v->groupPrimeCostLastYear = $groupPrimeCostLastYear[$gro];
                    $v->groupDeltaLastYear = $groupDeltaLastYear[$gro];
                }
            }
        }
        
        if ($rec->grouping == 'yes') {
            
            $recs = array();
            foreach ($groupValues as $k => $v) {
                $recs[$k] = (object) array(
                    
                    
                    'group' => $k,
                    'primeCost' => $v,
                    'delta' => $groupDeltas[$k],
                    'groupPrimeCostPrevious' => $groupPrimeCostPrevious[$k],
                    'groupDeltaPrevious' => $groupDeltaPrevious[$k],
                    'groupPrimeCostLastYear' => $groupPrimeCostLastYear[$k],
                    'groupDeltaLastYear' => $groupDeltaLastYear[$k]
                
                );
            }
        }
     
        if (!is_null($recs)) {
            if ($rec->grouping == 'no' && $rec->group){
                arr::sortObjects($recs, 'code', 'аsc', 'native');
            }
            
            if ($rec->grouping == 'no' && !$rec->group){
                arr::sortObjects($recs, 'code', 'аsc', 'native');
            }
            
            if ($rec->grouping == 'yes'){
                arr::sortObjects($recs, 'code', 'аsc', 'native');
            }
        }
       
        $totalArr['total'] = (object) array(
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
        
        if ($rec->compare == 'month') {
            $name1 = acc_Periods::fetch($rec->firstMonth)->title;
            $name2 = acc_Periods::fetch($rec->secondMonth)->title;
        } else {
            $name1 = 'За периода';
            $name2 = 'За сравнение';
        }
        
        if ($export === false) {
            if ($rec->grouping == 'no') {
                // $fld->FLD('group', 'keylist(mvc=cat_groups,select=name)', 'caption=Група');
                $fld->FLD('code', 'varchar', 'caption=Код');
                $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
                $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
                if ($rec->compare != 'no') {
                    $fld->FLD('quantity', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1}->Продажби");
                    $fld->FLD('primeCost', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1}->Стойност");
                    $fld->FLD('delta', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1}->Делта");
                    $fld->FLD('quantityCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2}->Продажби,tdClass=newCol");
                    $fld->FLD('primeCostCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2}->Стойност,tdClass=newCol");
                    $fld->FLD('deltaCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2}->Делта,tdClass=newCol");
                    $fld->FLD('changeSales', 'double(smartRound,decimals=2)', 'smartCenter,caption=Промяна->Продажби');
                    $fld->FLD('changeDeltas', 'double(smartRound,decimals=2)', 'smartCenter,caption=Промяна->Делти');
                } else {
                    $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Продажби');
                    $fld->FLD('primeCost', 'double(smartRound,decimals=2)', 'smartCenter,caption=Стойност');
                    $fld->FLD('delta', 'double(smartRound,decimals=2)', 'smartCenter,caption=Делта');
                }
            } else {
                $fld->FLD('group', 'varchar', 'caption=Група');
                $fld->FLD('primeCost', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1}->Стойност");
                $fld->FLD('delta', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1}->Делта");
                
                if ($rec->compare != 'no') {
                    $fld->FLD('primeCostCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2}->Стойност,tdClass=newCol");
                    $fld->FLD('deltaCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2}->Делта,tdClass=newCol");
                    $fld->FLD('changeSales', 'double(smartRound,decimals=2)', 'smartCenter,caption=Промяна->Продажби');
                    $fld->FLD('changeDeltas', 'double(smartRound,decimals=2)', 'smartCenter,caption=Промяна->Делти');
                }
            }
        } else {
            $fld->FLD('group', 'varchar', 'caption=Група');
            $fld->FLD('code', 'varchar', 'caption=Код');
            $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
            $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
            $fld->FLD('quantity', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1} Продажби");
            $fld->FLD('primeCost', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1} Стойност");
            $fld->FLD('delta', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1} Делта");
            if ($rec->compare != 'no') {
                $fld->FLD('quantityCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2} Продажби,tdClass=newCol");
                $fld->FLD('primeCostCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2} Стойност,tdClass=newCol");
                $fld->FLD('deltaCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2} Делта,tdClass=newCol");
                $fld->FLD('changeSales', 'double(smartRound,decimals=2)', 'smartCenter,caption=Промяна Продажби');
                $fld->FLD('changeDeltas', 'double(smartRound,decimals=2)', 'smartCenter,caption=Промяна Делти');
            }
        }
        
        return $fld;
    }
    
    
    /**
     * Връща групите
     *
     * @param stdClass $dRec
     * @param bool     $verbal
     *
     * @return mixed $dueDate
     */
    private static function getGroups($dRec, $verbal = true, $rec)
    {
        if ($verbal === true) {
            if (is_numeric($dRec->group)) {
                $groupVal = $dRec->groupValues;
                $groupDeltas = $dRec->groupDeltas;
                
                $group = cat_Groups::getVerbal($dRec->group, 'name') . "<span class= 'fright'><span class= ''>" . 'Общо за групата ( стойност: ' . core_Type::getByName('double(decimals=2)')->toVerbal($groupVal) . ', делта: ' . core_Type::getByName('double(decimals=2)')->toVerbal($groupDeltas) . ' )' . '</span>';
            } else {
                $group = $dRec->group . "<span class= 'fright'>" . 'Общо за групата ( стойност: ' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupValues) . ', делта: ' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupDeltas) . ' )' . '</span>';
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
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $groArr = array();
        
        $row = new stdClass();
        
        if ($dRec->totalValue) {
            $row->productId = '<b>' . 'ОБЩО ЗА ПЕРИОДА:' . '</b>';
            $row->primeCost = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalValue) . '</b>';
            $row->delta = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalDelta) . '</b>';
            
            foreach (array(
                'primeCost',
                'delta'
            ) as $q) {
                if (!isset($dRec->{$q})) {
                    continue;
                }
                
                $row->{$q} = ht::styleNumber($row->{$q}, $dRec->{$q});
            }
            
            if ($rec->compare != 'no' && $rec->grouping == 'no') {
                $changeDeltas = $changeDeltas = 0;
                
                if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                    $row->primeCostCompare = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalPrimeCostPrevious) . '</b>';
                    $row->primeCostCompare = ht::styleNumber($row->primeCostCompare, $dRec->totalPrimeCostPrevious);
                    
                    $row->deltaCompare = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalDeltaPrevious) . '</b>';
                    $row->deltaCompare = ht::styleNumber($row->deltaCompare, $dRec->totalDeltaPrevious);
                    
                    $changeSales = $dRec->totalValue - $dRec->totalPrimeCostPrevious;
                    $row->changeSales = '<b>'. core_Type::getByName('double(decimals=2)')->toVerbal($changeSales) . '</b>';
                    $row->changeSales = ht::styleNumber($row->changeSales, $changeSales);
                    
                    $changeDeltas = $dRec->totalDelta - $dRec->totalDeltaPrevious;
                    $row->changeDeltas = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($changeDeltas) . '</b>';
                    $row->changeDeltas = ht::styleNumber($row->changeDeltas, $changeDeltas);
                }
                if ($rec->compare == 'year') {
                    $row->primeCostCompare = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalPrimeCostLastYear) . '</b>';
                    $row->primeCostCompare = ht::styleNumber($row->primeCostCompare, $dRec->totalPrimeCostLastYear);
                    
                    $row->deltaCompare = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalDeltaLastYear) . '</b>';
                    $row->deltaCompare = ht::styleNumber($row->deltaCompare, $dRec->totalDeltaLastYear);
                    
                    $changeSales = $dRec->totalValue - $dRec->totalPrimeCostLastYear;
                    $row->changeSales = '<b>'. core_Type::getByName('double(decimals=2)')->toVerbal($changeSales) . '</b>';
                    $row->changeSales = ht::styleNumber($row->changeSales, $changeSales);
                    
                    $changeDeltas = $dRec->totalDelta - $dRec->totalDeltaLastYear;
                    $row->changeDeltas = '<b>'. core_Type::getByName('double(decimals=2)')->toVerbal($changeDeltas) . '</b>';
                    $row->changeDeltas = ht::styleNumber($row->changeDeltas, $changeDeltas);
                }
            }
            
            return $row;
        }
        
        if ($rec->grouping == 'yes') {
            if (is_numeric($dRec->group)) {
                $row->group = cat_Groups::getVerbal($dRec->group, 'name');
            } else {
                $row->group = 'Без група';
            }
            $row->primeCost = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->primeCost);
            $row->delta = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->delta);
            
            if ($rec->compare != 'no') {
                $changeDeltas = $changeDeltas = 0;
                
                if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                    $row->primeCostCompare = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupPrimeCostPrevious);
                    $row->primeCostCompare = ht::styleNumber($row->primeCostCompare, $dRec->groupPrimeCostPrevious);
                    
                    $row->deltaCompare = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupDeltaPrevious);
                    $row->deltaCompare = ht::styleNumber($row->deltaCompare, $dRec->groupDeltaPrevious);
                    
                    $changeSales = $dRec->primeCost - $dRec->groupPrimeCostPrevious;
                    $row->changeSales = core_Type::getByName('double(decimals=2)')->toVerbal($changeSales);
                    $row->changeSales = ht::styleNumber($row->changeSales, $changeSales);
                    
                    $changeDeltas = $dRec->delta - $dRec->groupDeltaPrevious;
                    $row->changeDeltas = '<b>'. core_Type::getByName('double(decimals=2)')->toVerbal($changeDeltas) . '</b>';
                    $row->changeDeltas = ht::styleNumber($row->changeDeltas, $changeDeltas);
                }
            }
            
            return $row;
        }
        
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
            'primeCost',
            'delta'
        ) as $fld) {
            if (!isset($dRec->{$fld})) {
                continue;
            }
            
            $row->{$fld} = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->{$fld});
            $row->{$fld} = ht::styleNumber($row->{$fld}, $dRec->{$fld});
        }
        
        $row->group = self::getGroups($dRec, true, $rec);
        
        if ($rec->compare != 'no') {
            $changeDeltas = $changeDeltas = 0;
            
            if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                $row->quantityCompare = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantityPrevious);
                $row->quantityCompare = ht::styleNumber($row->quantityCompare, $dRec->quantityPrevious);
                
                $row->primeCostCompare = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->primeCostPrevious);
                $row->primeCostCompare = ht::styleNumber($row->primeCostCompare, $dRec->primeCostPrevious);
                
                $row->deltaCompare = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->deltaPrevious);
                $row->deltaCompare = ht::styleNumber($row->deltaCompare, $dRec->deltaPrevious);
                
                $changeSales = $dRec->primeCost - $dRec->primeCostPrevious;
                $row->changeSales = core_Type::getByName('double(decimals=2)')->toVerbal($changeSales);
                $row->changeSales = ht::styleNumber($row->changeSales, $changeSales);
                
                $changeDeltas = $dRec->delta - $dRec->deltaPrevious;
                $row->changeDeltas = core_Type::getByName('double(decimals=2)')->toVerbal($changeDeltas);
                $row->changeDeltas = ht::styleNumber($row->changeDeltas, $changeDeltas);
            }
            
            if ($rec->compare == 'year') {
                $row->quantityCompare = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantityLastYear);
                $row->quantityCompare = ht::styleNumber($row->quantityCompare, $dRec->quantityLastYear);
                
                $row->primeCostCompare = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->primeCostLastYear);
                $row->primeCostCompare = ht::styleNumber($row->primeCostCompare, $dRec->primeCostLastYear);
                
                $row->deltaCompare = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->deltaLastYear);
                $row->deltaCompare = ht::styleNumber($row->deltaCompare, $dRec->deltaLastYear);
                
                $changeSales = $dRec->primeCost - $dRec->primeCostLastYear;
                $row->changeSales = core_Type::getByName('double(decimals=2)')->toVerbal($changeSales);
                $row->changeSales = ht::styleNumber($row->changeSales, $changeSales);
                
                $changeDeltas = $dRec->delta - $dRec->deltaLastYear;
                $row->changeDeltas = core_Type::getByName('double(decimals=2)')->toVerbal($changeDeltas);
                $row->changeDeltas = ht::styleNumber($row->changeDeltas, $changeDeltas);
            }
        }
        
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
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                <small><div><!--ET_BEGIN from-->|От|*: [#from#]<!--ET_END from--></div></small>
                                <small><div><!--ET_BEGIN to-->|До|*: [#to#]<!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN firstMonth-->|Месец 1|*: [#firstMonth#]<!--ET_END firstMonth--></div></small>
                                <small><div><!--ET_BEGIN secondMonth-->|Месец 2|*: [#secondMonth#]<!--ET_END secondMonth--></div></small>
                                <small><div><!--ET_BEGIN dealers-->|Търговци|*: [#dealers#]<!--ET_END dealers--></div></small>
                                <small><div><!--ET_BEGIN contragent-->|Контрагент|*: [#contragent#]<!--ET_END contragent--></div></small>
                                <small><div><!--ET_BEGIN crmGroup-->|Група контрагенти|*: [#crmGroup#]<!--ET_END crmGroup--></div></small>
                                <small><div><!--ET_BEGIN group-->|Групи продукти|*: [#group#]<!--ET_END group--></div></small>
                                <small><div><!--ET_BEGIN art-->|Артикули|*: [#art#]<!--ET_END art--></div></small>
                                <small><div><!--ET_BEGIN compare-->|Сравнение|*: [#compare#]<!--ET_END compare--></div></small>
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
                    
                    if ((count((type_Keylist::toArray($data->rec->crmGroup))) - $marker) != 0) {
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
                    
                    if ((count(type_Keylist::toArray($data->rec->contragent))) - $marker != 0) {
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
     * @param stdClass            $res
     * @param stdClass            $rec
     * @param stdClass            $dRec
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        $res->group = self::getGroups($dRec, false, $rec);
        
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
                $res->deltaCompare = $dRec->$totalDeltaLastYear;
                $res->changeSales = ($dRec->primeCost - $dRec->totalPrimeCostLastYear);
                $res->changeDeltas = ($dRec->delta - $dRec->$totalDeltaLastYear);
            }
        }
    }
}
