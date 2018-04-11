<?php

/**
 * Мениджър на отчети за продадени артикули продукти по групи и търговци
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Продажби » Продадени артикули
 */
class sales_reports_SoldProductsRep extends frame2_driver_TableData
{

    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, acc, rep_acc,rep_cat,sales';

    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     * @var string
     */
    protected $hashField = '$recIndic';

    /**
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var string
     */
    protected $newFieldToCheck = 'docId';

    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'group';

    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'from,to,compare,group,dealers,contragent,articleType';

    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset            
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('from', 'date(smartTime)', 'caption=От,after=title,single=none,mandatory');
        $fieldset->FLD('to', 'date(smartTime)', 'caption=До,after=from,single=none,mandatory');
        $fieldset->FLD('compare', 'enum(no=Без, previous=Предходен, year=Миналогодишен)', 
            'caption=Сравнение,after=to,single=none');
        $fieldset->FLD('group', 'keylist(mvc=cat_Groups,select=name)', 'caption=Група,after=compare,single=none');
        $fieldset->FLD('articleType', 'enum(yes=Стандартни,no=Нестандартни,all=Всички)', 
            "caption=Тип артикули,maxRadio=3,columns=3,removeAndRefreshForm,after=group");
        $fieldset->FLD('dealers', 
            'users(rolesForAll=ceo|rep_cat, rolesForTeams=ceo|manager|rep_acc|rep_cat,allowEmpty)', 
            'caption=Търговци,after=to');
        $fieldset->FLD('contragent', 
            'key2(mvc=doc_Folders,select=title,allowEmpty, restrictViewAccess=yes,coverInterface=crm_ContragentAccRegIntf)', 
            'caption=Контрагент,after=dealers');
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
            if (! ($form->rec->dealers)) {
                $form->setError('dealers', 'Нямате избран дилър');
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
        $form = &$data->form;
        $form->setDefault('articleType', 'all');
        
        // Размяна, ако периодите са объркани
        if (isset($form->rec->from) && isset($form->rec->to) && ($form->rec->from > $form->rec->to)) {
            $mid = $form->rec->from;
            $form->rec->from = $form->rec->to;
            $form->rec->to = $mid;
        }
    }

    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec            
     * @param stdClass $data            
     * @return array
     */
    protected function prepareRecs($rec, &$data = NULL)
    {
        $products = $recsYear = $recsLast = $recs = array();
        
        // Обръщаме се към трудовите договори ????
        $query = sales_PrimeCostByDocument::getQuery();
        $queryLast = sales_PrimeCostByDocument::getQuery();
        $queryLastYear = sales_PrimeCostByDocument::getQuery();
        
        $query->EXT('groupMat', 'cat_Products', 'externalName=groups,externalKey=productId');
        $query->EXT('art', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        $query->where("#valior >= '{$rec->from}' AND #valior <= '{$rec->to}'");
        
        if (isset($rec->dealers)) {
            
            if ((min(array_keys(keylist::toArray($rec->dealers))) >= 1)) {
                
                $dealers = keylist::toArray($rec->dealers);
                
                $query->whereArr("dealerId", $dealers, TRUE);
            }
        }
        
        if (isset($rec->group)) {
            $query->likeKeylist("groupMat", $rec->group);
        }
        
        if ($rec->articleType != 'all') {
            
            $query->where("#art = '{$rec->articleType}'");
        }
        
        // Last период
        
        if (isset($rec->compare) == 'previous') {
            $daysInPeriod = dt::daysBetween($rec->to, $rec->from) + 1;
            $fromLast = dt::addDays(- $daysInPeriod, $rec->from);
            $toLast = dt::addDays(- $daysInPeriod, $rec->to);
            
            $queryLast->where("#valior >= '{$fromLast}' AND #valior <= '{$toLast}'");
            
            if (isset($rec->dealers)) {
                
                $dealers = keylist::toArray($rec->dealers);
                
                $queryLast->whereArr("dealerId", $dealers, TRUE);
            }
        }
        
        // LastYear период
        
        if (isset($rec->compare) == 'year') {
            
            $fromLastYear = dt::addDays(- 365, $rec->from);
            $toLastYear = dt::addDays(- 365, $rec->to);
            
            $queryLastYear->where("#valior >= '{$fromLastYear}' AND #valior <= '{$toLastYear}'");
            
            if (isset($rec->dealers)) {
                
                $dealers = keylist::toArray($rec->dealers);
                
                $queryLastYear->whereArr("dealerId", $dealers, TRUE);
            }
        }
        
        $num = 1;
        $quantity = 0;
        $flag = FALSE;
        // за всеки един индикатор
        while ($recPrime = $query->fetch()) {
            
            $id = $recPrime->productId;
            
            $DetClass = cls::get($recPrime->detailClassId);
            
            if ($DetClass instanceof sales_SalesDetails) {
                
                $marker = FALSE;
                
                $saleId = sales_SalesDetails::fetch($recPrime->detailRecId)->saleId;
                
                $contoActionArr = explode(',', sales_Sales::fetch($saleId)->contoActions);
                
                foreach ($contoActionArr as $v) {
                    if ($v == 'ship') {
                        $marker = TRUE;
                    }
                }
                
                if (! $marker)
                    continue;
            }
            
            if (isset($recPrime->containerId)) {
                
                $origin = doc_Containers::getDocument($recPrime->containerId);
                
                if ($rec->contragent) {
                    
                    if ((cls::get(doc_Folders::fetch($rec->contragent)->coverClass)->fetch(
                        doc_Folders::fetch($rec->contragent)->coverId)->id) != ($origin->fetch()->contragentId)) {
                        
                        continue;
                    }
                }
                
                if ($origin->fetchField('state') != 'rejected') {
                    
                    if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {
                        $quantity = (- 1) * $recPrime->quantity;
                        $primeCost = (- 1) * $recPrime->sellCost * $recPrime->quantity;
                    } else {
                        $quantity = $recPrime->quantity;
                        $primeCost = $recPrime->sellCost * $recPrime->quantity;
                    }
                    
                    // добавяме в масива събитието
                    if (! array_key_exists($id, $recs)) {
                        
                        $recs[$id] = (object) array(
                            
                            'kod' => (cat_Products::fetchField($recPrime->productId, 'code')) ? cat_Products::fetchField(
                                $recPrime->productId, 'code') : "Art{$recPrime->productId}",
                            'measure' => cat_Products::getProductInfo($recPrime->productId)->productRec->measureId,
                            'productId' => $recPrime->productId,
                            'quantity' => $quantity,
                            'primeCost' => $primeCost,
                            'group' => cat_Products::fetchField($recPrime->productId, 'groups')
                        );
                    } else {
                        $obj = &$recs[$id];
                        $obj->quantity += $quantity;
                        $obj->primeCost += $primeCost;
                    }
                }
            }
        }
        
        // за всеки един индикатор
        while ($recPrimeLast = $queryLast->fetch()) {
            
            $id = $recPrimeLast->productId;
            
            $DetClass = cls::get($recPrimeLast->detailClassId);
            
            if ($DetClass instanceof sales_SalesDetails) {
                
                $marker = FALSE;
                
                $saleId = sales_SalesDetails::fetch($recPrimeLast->detailRecId)->saleId;
                
                if (! is_null($saleId)) {
                    $contoActionArr = explode(',', sales_Sales::fetch($saleId)->contoActions);
                } else
                    continue;
                
                foreach ($contoActionArr as $v) {
                    if ($v == 'ship') {
                        $marker = TRUE;
                    }
                }
                
                if (! $marker)
                    continue;
            }
            
            if (isset($recPrimeLast->containerId)) {
                
                $origin = doc_Containers::getDocument($recPrimeLast->containerId);
                
                if ($rec->contragent) {
                    
                    if ((cls::get(doc_Folders::fetch($rec->contragent)->coverClass)->fetch(
                        doc_Folders::fetch($rec->contragent)->coverId)->id) != $origin->fetch()->contragentId) {
                        
                        continue;
                    }
                }
                
                if ($origin->fetchField('state') != 'rejected') {
                    
                    if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {
                        $quantityLast = (- 1) * $recPrimeLast->quantity;
                    } else {
                        $quantityLast = $recPrimeLast->quantity;
                    }
                    
                    // добавяме в масива събитието
                    if (! array_key_exists($id, $recsLast)) {
                        $recsLast[$id] = (object) array(
                            
                            'quantityLast' => $quantityLast
                        );
                    } else {
                        $obj = &$recsLast[$id];
                        $obj->quantityLast += $quantityLast;
                    }
                }
            }
        }
        
        // за всеки един индикатор
        while ($recPrimeLastYear = $queryLast->fetch()) {
            
            $id = $recPrimeLastYear->productId;
            
            $DetClass = cls::get($recPrimeLastYear->detailClassId);
            
            if ($DetClass instanceof sales_SalesDetails) {
                
                $marker = FALSE;
                
                $saleId = sales_SalesDetails::fetch($recPrimeLastYear->detailRecId)->saleId;
                
                $contoActionArr = explode(',', sales_Sales::fetch($saleId)->contoActions);
                
                foreach ($contoActionArr as $v) {
                    if ($v == 'ship') {
                        $marker = TRUE;
                    }
                }
                
                if (! $marker)
                    continue;
            }
            
            if (isset($recPrimeLastYear->containerId)) {
                
                $origin = doc_Containers::getDocument($recPrimeLastYear->containerId);
                
                if ($rec->contragent) {
                    
                    if ((cls::get(doc_Folders::fetch($rec->contragent)->coverClass)->fetch(
                        doc_Folders::fetch($rec->contragent)->coverId)->id) != 

                    ($origin->fetch()->contragentId)) {
                        
                        continue;
                    }
                }
                
                if ($origin->fetchField('state') == 'rejected') {
                    
                    if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {
                        $quantityLastYear = (- 1) * $recPrimeLastYear->quantity;
                    } else {
                        $quantityLastYear = $recPrimeLastYear->quantity;
                    }
                    
                    // добавяме в масива събитието
                    if (! array_key_exists($id, $recsYear)) {
                        $recsYear[$id] = (object) array(
                            
                            'quantityLastYear' => $quantityLastYear
                        );
                    } else {
                        $obj = &$recsYear[$id];
                        $obj->quantityLastYear += $quantityLastYear;
                    }
                }
            }
        }
        
        if ($rec->compare == 'previous' && is_array($recsLast) && count($recsLast) >= 1) {
            foreach ($recs as $id => $r) {
                $r->quantityLast = $r->quantity - $recsLast[$id]->quantityLast;
            }
        }
        
        if ($rec->compare == 'year' && is_array($recsYear) && count($recsYear) >= 1) {
            foreach ($recs as $id => $r) {
                $r->quantityLast = $r->quantity - $recsYear[$id]->quantityLastYear;
            }
        }
        
        $recs = $this->groupRecs($recs, $rec->group);
        
        return $recs;
    }

    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *            - записа
     * @param boolean $export
     *            - таблицата за експорт ли е
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = FALSE)
    {
        $fld = cls::get('core_FieldSet');
        
        $fld->FLD('kod', 'varchar', 'caption=Код');
        $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
        $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Количество->Продадено');
        $fld->FLD('quantityLast', 'double(smartRound,decimals=2)', 'smartCenter,caption=Количество->Сравнение');
        $fld->FLD('primeCost', 'double(smartRound,decimals=2)', 'smartCenter,caption=Стойност');
        if ($export === TRUE) {
            $fld->FLD('group', 'keylist(mvc=cat_groups,select=name)', 'caption=Група');
        }
        
        return $fld;
    }

    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *            - записа
     * @param stdClass $dRec
     *            - чистия запис
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
        
        if (isset($dRec->kod)) {
            $row->kod = $dRec->kod;
        }
        
        $row->productId = cat_Products::getLinkToSingle_($dRec->productId, 'name');
        
        if (isset($dRec->measure)) {
            $row->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
        }
        
        foreach (array(
            'quantity',
            'primeCost',
            'quantityLast'
        ) as $fld) {
            $row->{$fld} = $Double->toVerbal($dRec->{$fld});
            if ($dRec->{$fld} < 0) {
                $row->{$fld} = "<span class='red'>{$row->{$fld}}</span>";
            }
        }
        
        if (isset($dRec->group)) {
            // и збраната позиция
            $rGroup = keylist::toArray($dRec->group);
            foreach ($rGroup as &$g) {
                $gro = cat_Groups::getVerbal($g, 'name');
            }
            
            $row->group = $gro;
        }
        
        return $row;
    }

    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver            
     * @param embed_Manager $Embedder            
     * @param core_ET $tpl            
     * @param stdClass $data            
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, 
        $fields = array())
    {
        $groArr = array();
        $artArr = array();
        
        $Date = cls::get('type_Date');
        $row->from = $Date->toVerbal($rec->from);
        $row->to = $Date->toVerbal($rec->to);
        $groupbyArr = array(
            'none' => 'Няма',
            'users' => 'Потребители'
        );
        $row->groupBy = $groupbyArr[$rec->groupBy];
        
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
            // избраната позиция
            $arts = keylist::toArray($rec->article);
            foreach ($arts as &$ar) {
                $art = cat_Products::fetchField("#id = '{$ar}'", 'name');
                array_push($artArr, $art);
            }
            
            $row->art = implode(', ', $artArr);
        }
        
        $arrCompare = array(
            'no' => 'Без',
            'previous' => 'Предходен',
            'year' => 'Миналогодишен'
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
        $fieldTpl = new core_ET(
            tr(
                "|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
							    <small><div><!--ET_BEGIN from-->|От|*: [#from#]<!--ET_END from--></div></small>
                                <small><div><!--ET_BEGIN to-->|До|*: [#to#]<!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN group-->|Групи|*: [#group#]<!--ET_END group--></div></small>
                                <small><div><!--ET_BEGIN art-->|Артикули|*: [#art#]<!--ET_END art--></div></small>
                                <small><div><!--ET_BEGIN compare-->|Сравнение|*: [#compare#]<!--ET_END compare--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
        
        if (isset($data->rec->from)) {
            $fieldTpl->append($data->row->from, 'from');
        }
        
        if (isset($data->rec->to)) {
            $fieldTpl->append($data->row->to, 'to');
        }
        
        if (isset($data->rec->group)) {
            $fieldTpl->append($data->row->group, 'group');
        }
        
        if (isset($data->rec->article)) {
            $fieldTpl->append($data->row->art, 'art');
        }
        
        if (isset($data->rec->compare)) {
            $fieldTpl->append($data->row->compare, 'compare');
        }
        
        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }

    /**
     * Групиране по продуктови групи
     *
     * @param array $recs            
     * @param string $group            
     * @param stdClass $data            
     * @return array
     */
    private function groupRecs($recs, $group)
    {
        $ordered = array();
        
        $groups = keylist::toArray($group);
        if (! count($groups)) {
            return $recs;
        } else {
            cls::get('cat_Groups')->invoke('AfterMakeArray4Select', array(
                &$groups
            ));
        }
        
        // За всеки маркер
        foreach ($groups as $grId => $groupName) {
            
            // Отделяме тези записи, които съдържат текущия маркер
            $res = array_filter($recs, 
                function (&$e) use($grId, $groupName) {
                    if (keylist::isIn($grId, $e->group)) {
                        $e->group = $grId;
                        return TRUE;
                    }
                    return FALSE;
                });
            
            if (count($res)) {
                arr::natOrder($res, 'kod');
                $ordered += $res;
            }
        }
        
        return $ordered;
    }
}