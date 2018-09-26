<?php


/**
 * Мениджър на отчети за продажби по контрагенти
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
 * @title     Продажби » Продажби по контрагенти
 */
class sales_reports_SalesByContragents extends frame2_driver_TableData
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
    protected $groupByField = 'groupList';
    
    
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
        $fieldset->FLD('dealers', 'users(rolesForAll=ceo|repAllGlobal, rolesForTeams=ceo|manager|repAll|repAllGlobal)', 'caption=Търговци,single=none,mandatory,after=to');
        $fieldset->FLD('orderBy', 'enum(saleValue=Продажби, delta=Делта,change=Промяна)', 'caption=Подреди по,maxRadio=3,columns=3,after=dealers');
        $fieldset->FLD('contragent', 'keylist(mvc=doc_Folders,select=title,allowEmpty)', 'caption=Контрагенти->Контрагент,single=none,after=orderBy');
        $fieldset->FLD('crmGroup', 'keylist(mvc=crm_Groups,select=name)', 'caption=Контрагенти->Група контрагенти,after=contragent,single=none');
        $fieldset->FLD('group', 'keylist(mvc=cat_Groups,select=name)', 'caption=Артикули->Група артикули,after=crmGroup,single=none');
        $fieldset->FLD('articleType', 'enum(yes=Стандартни,no=Нестандартни,all=Всички)', 'caption=Артикули->Тип артикули,maxRadio=3,columns=3,after=group,single=none');
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
                $toLastYear = dt::addDays(- 365, $form->rec->to);
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
        $suggestions = array();
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
        
        $form->setDefault('orderBy', 'saleValue');
        
        $form->setDefault('compare', 'no');
        
        $salesQuery = sales_Sales::getQuery();
        
        $salesQuery->EXT('folderTitle', 'doc_Folders', 'externalName=title,externalKey=folderId');
        
        $salesQuery->groupBy('folderId');
        
        $salesQuery->show('folderId, contragentId, folderTitle');
        
        while ($contragent = $salesQuery->fetch()) {
            if (! is_null($contragent->contragentId)) {
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
        $recs = array();
        $salesWithShipArr = array();
        
        $contragentsId = array();
        
        $query = sales_PrimeCostByDocument::getQuery();
        
        $query->EXT('groupMat', 'cat_Products', 'externalName=groups,externalKey=productId');
        
        $query->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
        
        $query->where("#state != 'rejected'");
        
        if (($rec->compare) == 'no') {
            $query->where("#valior >= '{$rec->from}' AND #valior <= '{$rec->to}'");
        }
        
        // Last период && By months
        if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
            if (($rec->compare == 'previous')) {
                $daysInPeriod = dt::daysBetween($rec->to, $rec->from) + 1;
                
                $fromPreviuos = dt::addDays(- $daysInPeriod, $rec->from, false);
                
                $toPreviuos = dt::addDays(- $daysInPeriod, $rec->to, false);
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
            $fromLastYear = dt::addDays(- 365, $rec->from);
            $toLastYear = dt::addDays(- 365, $rec->to);
            
            $query->where("(#valior >= '{$rec->from}' AND #valior <= '{$rec->to}') OR (#valior >= '{$fromLastYear}' AND #valior <= '{$toLastYear}')");
        }
        
        if (isset($rec->dealers)) {
            if ((min(array_keys(keylist::toArray($rec->dealers))) >= 1)) {
                $dealers = keylist::toArray($rec->dealers);
                
                $query->in('dealerId', $dealers);
            }
        }
        
        if (isset($rec->group)) {
            $query->likeKeylist('groupMat', $rec->group);
        }
        
        if ($rec->articleType != 'all') {
            $query->where("#isPublic = '{$rec->articleType}'");
        }
        
        // Масив бързи продажби //
        $sQuery = sales_Sales::getQuery();
        
        if (($rec->compare) == 'no') {
            $sQuery->where("#valior >= '{$rec->from}' AND #valior <= '{$rec->to}'");
        }
        
        // Last период
        if (($rec->compare) == 'previous') {
            $sQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$rec->to}') OR (#valior >= '{$fromPreviuos}' AND #valior <= '{$toPreviuos}')");
        }
        
        // LastYear период
        if (($rec->compare) == 'year') {
            $sQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$rec->to}') OR (#valior >= '{$fromLastYear}' AND #valior <= '{$toLastYear}')");
        }
        
        $sQuery->like('contoActions', 'ship', false);
        
        $sQuery->EXT('detailId', 'sales_SalesDetails', 'externalName=id,remoteKey=saleId');
        
        while ($sale = $sQuery->fetch()) {
            $salesWithShipArr[$sale->detailId] = $sale->detailId;
        }
        
        // избрани контрагенти
        $checkContragentsArr = keylist::toArray($rec->contragent);
        
        foreach ($checkContragentsArr as $val) {
            $contragentsId[doc_Folders::fetch($val)->coverId] = doc_Folders::fetch($val)->coverId;
        }
        
        // Синхронизира таймлимита с броя записи //
        $rec->count = $query->count();
        
        $timeLimit = $query->count() * 0.05;
        
        if ($timeLimit >= 30) {
            core_App::setTimeLimit($timeLimit);
        }
        
        while ($recPrime = $query->fetch()) {
            $sellValuePrevious = $sellValueLastYear = $sellValue = $delta = $deltaPrevious = $deltaLastYear = 0;
            $contragentId = $contragentClassId = $contragentClassName = 0;
            $detClassName = $masterClassName = $masterKey = $contragentGroups = 0;
            
            $DetClass = cls::get($recPrime->detailClassId);
            
            // контрагента по сделката
            $detClassName = $DetClass->className;
            $masterClassName = $DetClass->Master->className;
            $masterKey = $detClassName::fetchField($recPrime->detailRecId, "{$DetClass->masterKey}");
            
            if (is_null($masterKey)) {
                log_System::add(sales_reports_SalesByContragents, 'masterKey is NULL във' . $recPrime . $detClassName, null, 'notice');
            } else {
                $contragentId = $masterClassName::fetchField($masterKey, 'contragentId');
                $contragentClassId = $masterClassName::fetchField($masterKey, 'contragentClassId');
                $contragentClassName = core_Classes::fetchField($contragentClassId, 'name');
                
                if (is_null($contragentId) || is_null($contragentClassId)) {
                    log_System::add(sales_reports_SalesByContragents, 'ContragentId или ContragentClassId is NULL във' . $recPrime . $masterClassName . $masterKey, null, 'notice');
                }
            }
            
            // групите на контрагента по сделката
            if ($contragentId) {
                $contragentGroupsList = $contragentClassName::fetchField($contragentId, 'groupList');
                
                $contragentGroups = keylist::toArray($contragentGroupsList);
            }
            
            if ($rec->contragent || $rec->crmGroup) {
                $checkContragentsArr = array();
                
                $checkContragent = $checkGroup = null;
                
                $checkContragent = in_array($contragentId, $contragentsId);
                
                $checkGroup = keylist::isIn($contragentGroups, $rec->crmGroup);
                
                // филтър по контрагент без група
                if (! $rec->crmGroup && $rec->contragent) {
                    if (! $checkContragent) {
                        continue;
                    }
                }
                
                // филтър по група без контрагент
                if ($rec->crmGroup && ! $rec->contragent) {
                    if (! $checkGroup) {
                        continue;
                    }
                }
                
                // филтър по група и контрагент
                if ($rec->crmGroup && $rec->contragent) {
                    if (! $checkContragent || ! $checkGroup) {
                        continue;
                    }
                }
            }
            
            if ($DetClass instanceof sales_SalesDetails) {
                if (is_array($salesWithShipArr)) {
                    if (in_array($recPrime->detailRecId, $salesWithShipArr)) {
                        continue;
                    }
                }
            }
            $id = $contragentId;
            
            if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                if ($recPrime->valior >= $fromPreviuos && $recPrime->valior <= $toPreviuos) {
                    if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {
                        $sellValuePrevious = (- 1) * $recPrime->sellCost * $recPrime->quantity;
                        $deltaPrevious = (- 1) * $recPrime->delta;
                    } else {
                        $sellValuePrevious = $recPrime->sellCost * $recPrime->quantity;
                        $deltaPrevious = $recPrime->delta;
                    }
                }
            }
            
            if ($rec->compare == 'year') {
                if ($recPrime->valior >= $fromLastYear && $recPrime->valior <= $toLastYear) {
                    if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {
                        $sellValueLastYear = (- 1) * $recPrime->sellCost * $recPrime->quantity;
                        $deltaLastYear = (- 1) * $recPrime->delta;
                    } else {
                        $sellValueLastYear = $recPrime->sellCost * $recPrime->quantity;
                        $deltaLastYear = $recPrime->delta;
                    }
                }
            }
            
            if ($recPrime->valior >= $rec->from && $recPrime->valior <= $rec->to) {
                if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {
                    $sellValue = (- 1) * $recPrime->sellCost * $recPrime->quantity;
                    
                    $delta = (- 1) * $recPrime->delta;
                } else {
                    $sellValue = $recPrime->sellCost * $recPrime->quantity;
                    
                    $delta = $recPrime->delta;
                }
            }
            
            // добавяме в масива
            if (! array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    
                    'contragentId' => $id,
                    'contragentClassName' => $contragentClassName,
                    'sellValuePrevious' => $sellValuePrevious,
                    'deltaPrevious' => $deltaPrevious,
                    'sellValueLastYear' => $sellValueLastYear,
                    'deltaLastYear' => $deltaLastYear,
                    'saleValue' => $sellValue,
                    'group' => cat_Products::fetchField($recPrime->productId, 'groups'),
                    'groupList' => $contragentGroupsList,
                    'delta' => $delta,
                    'change' => '',
                    'groupValues' => '',
                    'groupDeltas' => ''
                );
            } else {
                $obj = &$recs[$id];
                $obj->sellValuePrevious += $sellValuePrevious;
                $obj->deltaPrevious += $deltaPrevious;
                $obj->sellValueLastYear += $sellValueLastYear;
                $obj->deltaLastYear += $deltaLastYear;
                $obj->saleValue += $sellValue;
                $obj->delta += $delta;
            }
            
            $totalSalleValue += $sellValue;
            
            $totalDelta += $delta;
            
            $totalDeltaPrevious += $deltaPrevious;
            
            $totalDeltaLastYear += $deltaLastYear;
            
            $totalValuePrevious += $sellValuePrevious;
            
            $totalValueLastYear += $sellValueLastYear;
        }
        
        $tempArr = array();
        
        foreach ($recs as $v) {
            if (! $rec->crmGroup) {
                list($firstGroup) = explode('|', trim($v->groupList, '|'));
                
                $tempArr[$v->contragentId] = $v;
                $tempArr[$v->contragentId]->groupList = $firstGroup;
                
                if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                    $tempArr[$v->contragentId]->change = $v->saleValue - $v->sellValuePrevious;
                }
                
                if ($rec->compare == 'year') {
                    $tempArr[$v->contragentId]->change = $v->saleValue - $v->sellValueLastYear;
                }
                $groupValues[$firstGroup] += $v->saleValue;
                $groupDeltas[$firstGroup] += $v->delta;
                
                if (!$v->groupList) {
                    $v->groupList = 'Без група';
                }
            } else {
                foreach (explode('|', trim($rec->crmGroup, '|')) as $gr) {
                    $tempArr[$v->contragentId] = $v;
                    
                    if (keylist::isIn($gr, $v->groupList)) {
                        $tempArr[$v->contragentId]->groupList = $gr;
                        
                        if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                            $tempArr[$v->contragentId]->change = $v->saleValue - $v->sellValuePrevious;
                        }
                        
                        if ($rec->compare == 'year') {
                            $tempArr[$v->contragentId]->change = $v->saleValue - $v->sellValueLastYear;
                        }
                        
                        $groupValues[$gr] += $v->saleValue;
                        $groupDeltas[$gr] += $v->delta;
                        
                        break;
                    }
                }
            }
        }
        
        $recs = $tempArr;
        
        foreach ($recs as $v) {
            $v->groupValues = $groupValues[$v->groupList];
            $v->groupDeltas = $groupDeltas[$v->groupList];
        }
        
        
        $totalArr = array();
        
        if (! is_null($recs)) {
            arr::sortObjects($recs, $rec->orderBy, 'desc');
        }
        
        $totalArr['total'] = (object) array(
            'totalValue' => $totalSalleValue,
            'totalDelta' => $totalDelta,
            'totalValuePrevious' => $totalValuePrevious,
            'totalValueLastYear' => $totalValueLastYear,
            'totalDeltaPrevious' => $totalDeltaPrevious,
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
            $fld->FLD('contragentId', 'key(mvc=doc_Folders,select=name)', 'caption=Контрагент');
            
            $fld->FLD('groupList', 'keylist(mvc=crm_Groups,select=name)', 'caption=Група контрагенти');
            
            if ($rec->compare != 'no') {
                $fld->FLD('saleValue', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1}->Продажби");
                $fld->FLD('delta', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1}->Делта");
            } else {
                $fld->FLD('saleValue', 'double(smartRound,decimals=2)', 'smartCenter,caption=Продажби');
                $fld->FLD('delta', 'double(smartRound,decimals=2)', 'smartCenter,caption=Делта');
            }
            
            if ($rec->compare != 'no') {
                $fld->FLD('sellValueCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2}->Продажби,tdClass=newCol");
                $fld->FLD('deltaCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2}->Делта,tdClass=newCol");
                $fld->FLD('changeSales', 'double(smartRound,decimals=2)', 'smartCenter,caption=Промяна->Продажби');
                $fld->FLD('changeDeltas', 'double(smartRound,decimals=2)', 'smartCenter,caption=Промяна->Делти');
            }
        } else {
            $fld->FLD('groupList', 'keylist(mvc=crm_Groups,select=name)', 'caption=Група контрагенти');
            $fld->FLD('contragentId', 'varchar', 'caption=Контрагент');
            $fld->FLD('saleValue', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1}->Продажби");
            $fld->FLD('delta', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1}->Делта");
            if ($rec->compare != 'no') {
                $fld->FLD('sellValueCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2}->Продажби");
                $fld->FLD('deltaCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2}->Делта");
                $fld->FLD('changeSales', 'double(smartRound,decimals=2)', 'smartCenter,caption=Промяна->Продажби');
                $fld->FLD('changeDeltas', 'double(smartRound,decimals=2)', 'smartCenter,caption=Промяна->Делти');
            }
        }
        
        return $fld;
    }
    
    
    /**
     * Връща контрагента
     *
     * @param stdClass $dRec
     * @param bool     $verbal
     *
     * @return mixed $dueDate
     */
    private static function getContragent($dRec, $verbal = true, $rec)
    {
        if ($verbal === true) {
            if ($dRec->contragentId) {
                $contragentClassName = $dRec->contragentClassName;
                try {
                    $contragent = $contragentClassName::getShortHyperlink($dRec->contragentId);
                } catch (Exception $e) {
                    reportException($e);
                }
            }
        } else {
            if ($dRec->contragentId) {
                try {
                    $contragentClassName = $dRec->contragentClassName;
                    
                    $contragent = $contragentClassName::getTitleById($dRec->contragentId);
                } catch (Exception $e) {
                    reportException($e);
                }
            }
        }
        
        return $contragent;
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
        $row = new stdClass();
        $contragentClassName = '';
        
        try {
            if ($dRec->totalValue) {
                $row->contragentId = '<b>' . 'ОБЩО' . '</b>';
                
                $row->saleValue = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalValue) . '</b>';
                $row->saleValue = ht::styleNumber($row->saleValue, $dRec->totalValue);
                
                $row->delta = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalDelta) . '</b>';
                $row->delta = ht::styleNumber($row->delta, $dRec->totalDelta);
                
                $row->groupList = '';
                
                if ($rec->compare != 'no') {
                    if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                        $row->sellValueCompare = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalValuePrevious) . '</b>';
                        $row->sellValueCompare = ht::styleNumber($row->sellValueCompare, $dRec->totalValuePrevious);
                        
                        $row->deltaCompare = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalDeltaPrevious) . '</b>';
                        $row->deltaCompare = ht::styleNumber($row->deltaCompare, $dRec->totalDeltaPrevious);
                        
                        $changeSales = $dRec->totalValue - $dRec->totalValuePrevious;
                        $row->changeSales = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($changeSales) . '</b>';
                        $row->changeSales = ht::styleNumber($row->changeSales, $changeSales);
                        
                        $changeDeltas = $dRec->totalDelta - $dRec->totalDeltaPrevious;
                        $row->changeDeltas = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($changeDeltas) . '</b>';
                        $row->changeDeltas = ht::styleNumber($row->changeDeltas, $changeDeltas);
                    }
                    
                    if ($rec->compare == 'year') {
                        $row->sellValueCompare = '<b>' .core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalValueLastYear). '</b>';
                        $row->sellValueCompare = ht::styleNumber($row->sellValueCompare, $dRec->totalValueLastYear);
                        
                        $row->deltaCompare = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalDeltaLastYear) . '</b>';
                        $row->deltaCompare = ht::styleNumber($row->deltaCompare, $dRec->totalDeltaLastYear);
                        
                        $changeSales = $dRec->totalValue - $dRec->totalValueLastYear;
                        $row->changeSales = '<b>'  . core_Type::getByName('double(decimals=2)')->toVerbal($changeSales) . '</b>';
                        $row->changeSales = ht::styleNumber($row->changeSales, $changeSales);
                        
                        $changeDeltas = $dRec->totalDelta - $dRec->totalDeltaLastYear;
                        $row->changeDeltas = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($changeDeltas) . '</b>';
                        $row->changeDeltas = ht::styleNumber($row->changeDeltas, $changeDeltas);
                    }
                }
                
                return $row;
            }
            
            if (isset($dRec->code)) {
                $row->code = $dRec->code;
            }
            
            $row->contragentId = self::getContragent($dRec, true, $rec);
            
            foreach (array(
                'saleValue',
                'delta'
            ) as $fld) {
                $row->{$fld} = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->{$fld});
                $row->{$fld} = ht::styleNumber($row->{$fld}, $dRec->{$fld});
            }
            
            if ($rec->compare != 'no') {
                if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                    $row->sellValueCompare = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->sellValuePrevious);
                    $row->sellValueCompare = ht::styleNumber($row->sellValueCompare, $dRec->sellValuePrevious);
                    
                    $row->deltaCompare = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->deltaPrevious);
                    $row->deltaCompare = ht::styleNumber($row->deltaCompare, $dRec->deltaPrevious);
                    
                    $changeSales = $dRec->saleValue - $dRec->sellValuePrevious;
                    $row->changeSales = core_Type::getByName('double(decimals=2)')->toVerbal($changeSales);
                    $row->changeSales = ht::styleNumber($row->changeSales, $changeSales);
                    
                    $changeDeltas = $dRec->delta - $dRec->deltaPrevious;
                    $row->changeDeltas = core_Type::getByName('double(decimals=2)')->toVerbal($changeDeltas);
                    $row->changeDeltas = ht::styleNumber($row->changeDeltas, $changeDeltas);
                }
                
                if ($rec->compare == 'year') {
                    $row->sellValueCompare = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->sellValueLastYear);
                    $row->sellValueCompare = ht::styleNumber($row->sellValueCompare, $dRec->sellValueLastYear);
                    
                    $row->deltaCompare = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->deltaLastYear);
                    $row->deltaCompare = ht::styleNumber($row->deltaCompare, $dRec->deltaLastYear);
                    
                    $changeSales = $dRec->saleValue - $dRec->sellValueLastYear;
                    $row->changeSales = core_Type::getByName('double(decimals=2)')->toVerbal($changeSales);
                    $row->changeSales = ht::styleNumber($row->changeSales, $changeSales);
                    
                    $changeDeltas = $dRec->delta - $dRec->deltaLastYear;
                    $row->changeDeltas = core_Type::getByName('double(decimals=2)')->toVerbal($changeDeltas);
                    $row->changeDeltas = ht::styleNumber($row->changeDeltas, $changeDeltas);
                }
            }
            
            if (is_numeric($dRec->groupList)) {
                $row->groupList = crm_Groups::getVerbal($dRec->groupList, 'name').
                "<span class= 'fright'><span class= ''>" . 'Общо за групата ( стойност: '.
                core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupValues) .', делта: '.
                core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupDeltas) .' )'. '</span>';
            } else {
                if ($dRec->groupList) {
                    $row->group = $dRec->groupList.
                    "<span class= 'fright'><span class= ''>" . 'Общо за групата ( стойност: '.
                    core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupValues[$dRec->groupList]) .', делта: '.
                    core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupDeltas[$dRec->groupList]) .' )'. '</span>';
                } else {
                    unset($row->group);
                }
            }
        } catch (Exception $e) {
            reportException($e);
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
        $currency = currency_Currencies::getCodeById(acc_Periods::getBaseCurrencyId());
        
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
        
        if (isset($data->rec->compare)) {
            $fieldTpl->append('<b>' . $data->row->compare . '</b>', 'compare');
        }
        
        $fieldTpl->append('<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->total) . '</b>', 'total');
        
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
        $res->contragentId = self::getContragent($dRec, false, $rec);
        
        if ($rec->compare != 'no') {
            if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                $res->quantityCompare = $dRec->quantityPrevious;
                $res->sellValueCompare = $dRec->sellValuePrevious;
                $res->deltaCompare = $dRec->deltaPrevious;
                $res->changeSales = ($dRec->saleValue - $dRec->sellValuePrevious);
                $res->changeDeltas = ($dRec->delta - $dRec->deltaPrevious);
            }
            
            if ($rec->compare == 'year') {
                $res->quantityCompare = $dRec->quantityLastYear;
                $res->sellValueCompare = $dRec->sellValueLastYear;
                $res->deltaCompare = $dRec->deltaLastYear;
                $res->changeSales = ($dRec->sellValue - $dRec->sellValueLastYear);
                $res->changeDeltas = ($dRec->delta - $dRec->deltaLastYear);
            }
        }
        if ($res->totalValue) {
            $res->contragentId = 'ОБЩО:';
            $res->saleValue = $dRec->totalValue;
            $res->delta = $dRec->totalDelta;
            
            if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                $res->sellValueCompare = $dRec->totalValuePrevious;
                $res->deltaCompare = $dRec->totalDeltaPrevious;
                $res->changeSales = ($dRec->saleValue - $dRec->totalValuePrevious);
                $res->changeDeltas = ($dRec->delta - $dRec->totalDeltaPrevious);
            }
            
            if ($rec->compare == 'year') {
                $res->sellValueCompare = $dRec->totalValueLastYear;
                $res->deltaCompare = $dRec->totalDeltaLastYear;
                $res->changeSales = ($dRec->saleValue - $dRec->totalValueLastYear);
                $res->changeDeltas = ($dRec->delta - $dRec->totalDeltaLastYear);
            }
        }
    }
}
