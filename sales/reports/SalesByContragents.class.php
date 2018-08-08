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
        $fieldset->FLD('dealers', 'users(rolesForAll=ceo|repAllGlobal, rolesForTeams=ceo|manager|repAll|repAllGlobal)', 'caption=Търговци,single=none,mandatory,after=to');
        $fieldset->FLD('contragent', 'keylist(mvc=doc_Folders,select=title,allowEmpty)', 'caption=Контрагенти->Контрагент,single=none,after=dealers');
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
        
        $salesQuery = sales_Sales::getQuery();
        
        $salesQuery->groupBy('folderId');
        
        $salesQuery->show('folderId, contragentId');
        
        while ($contragent = $salesQuery->fetch()) {
            if (!is_null($contragent->contragentId)) {
                $suggestions[$contragent->folderId] = crm_Companies::fetch($contragent->contragentId)->name;
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
        file_put_contents('debug.txt', serialize($rec));
        
        $recs = array();
        
        $query = sales_PrimeCostByDocument::getQuery();
        
        $query->EXT('groupMat', 'cat_Products', 'externalName=groups,externalKey=productId');
        
        $query->EXT('isPub }lic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        
        $query->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
        
        $query->where("#state != 'rejected'");
        
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
            $sellValuePrevious = $sellValueLastYear = $sellValue = $delta = 0;
            
            $DetClass = cls::get($recPrime->detailClassId);
            
            // контрагента по сделката
            $detClassName = $DetClass->className;
            $masterClassName = $DetClass->Master->className;
            $masterKey = $detClassName::fetchField($recPrime->detailRecId, "{$DetClass->masterKey}");
            $contragentId = $masterClassName::fetchField($masterKey, 'contragentId');
            
            if ($rec->contragent || $rec->crmGroup) {
                $checkContragentsArr = array();
                $contragentsId = array();
                
                // групите на контрагента по сделката
                if ($contragentId) {
                    $contragentGroups = crm_Companies::fetchField($contragentId, 'groupList');
                    
                    $contragentGroups = keylist::toArray($contragentGroups);
                }
                
                // избрани контрагенти
                $checkContragentsArr = keylist::toArray($rec->contragent);
                
                foreach ($checkContragentsArr as $val) {
                    $contragentsId[doc_Folders::fetch($val)->coverId] = doc_Folders::fetch($val)->coverId;
                }
                
                $checkContragent = in_array($contragentId, $contragentsId);
                
                $checkGroup = keylist::isIn($contragentGroups, $rec->crmGroup);
                
                // филтър по контрагент без група
                if (!$rec->crmGroup && $rec->contragent) {
                    if (!$checkContragent) {
                        continue;
                    }
                }
                
                
                //филтър по група без контрагент
                if ($rec->crmGroup && !$rec->contragent) {
                    if (!$checkGroup) {
                        continue;
                    }
                }
                
                //филтър по група и контрагент
                if ($rec->crmGroup && $rec->contragent) {
                    if (!$checkContragent || !$checkGroup) {
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
                        $sellValuePrevious = (-1) * $recPrime->sellCost * $recPrime->quantity;
                    } else {
                        $sellValuePrevious = $recPrime->sellCost * $recPrime->quantity;
                    }
                }
            }
            
            if ($rec->compare == 'year') {
                if ($recPrime->valior >= $fromLastYear && $recPrime->valior <= $toLastYear) {
                    if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {
                        $sellValueLastYear = (-1) * $recPrime->sellCost * $recPrime->quantity;
                    } else {
                        $sellValueLastYear = $recPrime->sellCost * $recPrime->quantity;
                    }
                }
            }
            
            if ($recPrime->valior >= $rec->from && $recPrime->valior <= $rec->to) {
                if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {
                    $quantity = (-1) * $recPrime->quantity;
                    
                    $sellValue = (-1) * $recPrime->sellCost * $recPrime->quantity;
                    
                    $delta = (-1) * $recPrime->delta;
                } else {
                    $quantity = $recPrime->quantity;
                    
                    $sellValue = $recPrime->sellCost * $recPrime->quantity;
                    
                    $delta = $recPrime->delta;
                }
            }
            
            // добавяме в масива събитието
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    
                    'contragentId' => $id,
                    'sellValuePrevious' => $sellValuePrevious,
                    'sellValueLastYear' => $sellValueLastYear,
                    'sellValue' => $sellValue,
                    'group' => cat_Products::fetchField($recPrime->productId, 'groups'),
                    'groupList' => $recPrime->groupList,
                    'delta' => $delta
                
                );
            } else {
                $obj = &$recs[$id];
                $obj->sellValuePrevious += $sellValuePrevious;
                $obj->sellValueLastYear += $sellValueLastYear;
                $obj->sellValue += $sellValue;
                $obj->delta += $delta;
            }
            
            $totalSalleValue += $sellValue;
            
            $totalDelta += $delta;
            
            $totalValuePrevious += $sellValuePrevious;
            
            $totalValueLastYear += $sellValueLastYear;
        }
        
        if (!is_null($recs)) {
            arr::sortObjects($recs, 'sellValue', 'desc', 'natural');
        }
        
        $recs['total'] = (object) array(
            'totalValue' => $totalSalleValue,
            'totalDelta' => $totalDelta,
            'totalValuePrevious' => $totalValuePrevious,
            'totalValueLastYear' => $totalValueLastYear
        );
        
        array_unshift($recs, $recs['total']);
        
        // bp($recs);
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
        
        $fld->FLD('contragentId', 'key(mvc=doc_Folders,select=name)', 'caption=Контрагент');
        
        if ($rec->compare != 'no') {
            $fld->FLD('sellValue', 'double(smartRound,decimals=2)', "smartCenter,caption=Продажби->{$name1}");
        } else {
            $fld->FLD('sellValue', 'double(smartRound,decimals=2)', 'smartCenter,caption=Продажби');
        }
        if ($rec->compare != 'no') {
            $fld->FLD('sellValueCompare', 'double(smartRound,decimals=2)', "smartCenter,caption=Продажби->{$name2}");
            $fld->FLD('compare', 'double(smartRound,decimals=2)', 'smartCenter,caption=Сравнение');
        }
        $fld->FLD('delta', 'double(smartRound,decimals=2)', 'smartCenter,caption=Делта');
        
        if ($export === true) {
            $fld->FLD('group', 'keylist(mvc=cat_groups,select=name)', 'caption=Група');
        }
        
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
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $groArr = array();
        
        $row = new stdClass();
        
        if (isset($dRec->code)) {
            $row->code = $dRec->code;
        }
        if ($dRec->contragentId) {
            $row->contragentId = crm_Companies::getShortHyperlink($dRec->contragentId, true);
        }
        foreach (array(
            'sellValue',
            'delta'
        ) as $fld) {
            $row->{$fld} = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->{$fld});
            if ($dRec->{$fld} < 0) {
                $row->{$fld} = "<span class='red'>" . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->{$fld}) . '</span>';
            }
        }
        
        if ($rec->compare != 'no') {
            if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                if (($dRec->sellValue - $dRec->sellValuePrevious) > 0 && $dRec->sellValuePrevious != 0) {
                    $color = 'green';
                    $marker = '+';
                } elseif (($dRec->sellValue - $dRec->sellValuePrevious) < 0.1) {
                    $color = 'red';
                    $marker = '';
                } else {
                    $color = 'black';
                    $marker = '';
                }
                
                $row->sellValueCompare = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->sellValuePrevious);
                
                if ($dRec->sellValuePrevious != 0) {
                    $compare = ($dRec->sellValue - $dRec->sellValuePrevious) / $dRec->sellValuePrevious;
                }
                
                $row->compare = "<span class= {$color}>" . $marker . cls::get('type_Percent')->toVerbal($compare) . '</span>';
            }
            
            if ($rec->compare == 'year') {
                if (($dRec->sellValue - $dRec->sellValueLastYear) > 0 && $dRec->sellValueLastYear != 0) {
                    $color = 'green';
                    $marker = '+';
                } elseif (($dRec->sellValue - $dRec->sellValueLastYear) < 0) {
                    $color = 'red';
                    $marker = '';
                } else {
                    $color = 'black';
                    $marker = '';
                }
                
                $row->sellValueCompare = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->sellValueLastYear);
                
                if ($dRec->sellValueLastYear != 0) {
                    $compare = ($dRec->sellValue - $dRec->sellValueLastYear) / $dRec->sellValueLastYear;
                }
                $row->compare = "<span class= {$color}>" . $marker . cls::get('type_Percent')->toVerbal($compare) . '</span>';
            }
        }
        if ($dRec->totalValue) {
            $row->contragentId = '<b>' . 'ОБЩО' . '</b>';
            $row->sellValue = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalValue) . '</b>';
            $row->delta = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalDelta) . '</b>';
            
            if ($rec->compare != 'no') {
                if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                    if (($dRec->totalValue - $dRec->totalValuePrevious) > 0 && $dRec->totalValuePrevious != 0) {
                        $color = 'green';
                        $marker = '+';
                    } elseif (($dRec->totalValue - $dRec->totalValuePrevious) < 0.1) {
                        $color = 'red';
                        $marker = '';
                    } else {
                        $color = 'black';
                        $marker = '';
                    }
                    
                    $row->sellValueCompare = '<b>' .core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalValuePrevious). '</b>';
                    
                    if ($dRec->totalValuePrevious != 0) {
                        $compare = ($dRec->totalValue - $dRec->totalValuePrevious) / $dRec->totalValuePrevious;
                    }
                    
                    $row->compare = "<span class= {$color}>" . $marker . cls::get('type_Percent')->toVerbal($compare) . '</span>';
                }
                
                if ($rec->compare == 'year') {
                    if (($dRec->totalValue - $dRec->totalValueLastYear) > 0 && $dRec->totalValueLastYear != 0) {
                        $color = 'green';
                        $marker = '+';
                    } elseif (($dRec->totalValue - $dRec->totalValueLastYear) < 0) {
                        $color = 'red';
                        $marker = '';
                    } else {
                        $color = 'black';
                        $marker = '';
                    }
                    
                    $row->sellValueCompare = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalValueLastYear);
                    
                    if ($dRec->totalValueLastYear != 0) {
                        $compare = ($dRec->totalValue - $dRec->totalValueLastYear) / $dRec->totalValueLastYear;
                    }
                    $row->compare = "<span class= {$color}>" . $marker . cls::get('type_Percent')->toVerbal($compare) . '</span>';
                }
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
}
