<?php

/**
 * Мениджър на отчети за продадени артикули продукти по групи и търговци
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Angel Trifonov angel.trifonoff@gmail.com
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
    public $canSelectDriver = 'ceo, acc, repAll, repAllGlobal, sales';

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
            'users(rolesForAll=ceo|repAllGlobal, rolesForTeams=ceo|manager|repAll|repAllGlobal,allowEmpty)', 
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
            
            // Проверка на периоди
            if (isset($form->rec->from) && isset($form->rec->to) && ($form->rec->from > $form->rec->to)) {
                $form->setError('from,to', 'Началната дата на периода не може да бъде по-голяма от крайната.');
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
        
        $query = sales_PrimeCostByDocument::getQuery();

        $query->EXT('groupMat', 'cat_Products', 'externalName=groups,externalKey=productId');
       
        $query->EXT('art', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        
        $query->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
        
        $query->EXT('saleId', 'sales_SalesDetails', 'externalName=saleId,externalKey=detailRecId,remoteKey=id');
        
        $query->EXT('contoActions', 'sales_Sales', 'externalName=contoActions,externalKey=saleId,remoteKey=id');
        
        $query->where("#valior >= '{$rec->from}' AND #valior <= '{$rec->to}'");
        
        $query->like('contoActions', 'ship',FALSE);
        
        if (isset($rec->dealers)) {
            
            if ((min(array_keys(keylist::toArray($rec->dealers))) >= 1)) {
                
                $dealers = keylist::toArray($rec->dealers);
                
                $query->whereArr("dealerId", $dealers, TRUE);
            }
        }
        
        if ($rec->contragent) {
        	
        	
        	$contragentId = doc_Folders::fetch($rec->contragent)->coverId;
        	
        	
      //	$query->EXT('contragentId', 'doc_Containers', 'externalName=contragentId,externalKey=containerId,remoteKey=id');
        	
        	
        
        	
        	//bp($origin,$contragentId,$rec->contragent,$aaa,$rec->containerId);
        	
        	
        	
        	
        }
        
        if (isset($rec->group)) {
            $query->likeKeylist("groupMat", $rec->group);
        }
        
        if ($rec->articleType != 'all') {
            
            $query->where("#art = '{$rec->articleType}'");
        }
      
        $num = 1;
        $quantity = 0;
        $flag = FALSE;
        // за всеки един индикатор
        while ($recPrime = $query->fetch()) {
            
        	$recPrimeArr[]=$recPrime;
        	
        	
            $id = $recPrime->productId;
            
            $DetClass = cls::get($recPrime->detailClassId);
            

            
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
                            
                            'kod' => $recPrime->code ?$recPrime->code : "Art{$recPrime->productId}",
                            'measure' => cat_Products::getProductInfo($recPrime->productId)->productRec->measureId,
                            'productId' => $recPrime->productId,
                            'quantity' => $quantity,
                            'primeCost' => $primeCost,
                            'group' =>$recPrime->groupMat
                        );
                    } else {
                        $obj = &$recs[$id];
                        $obj->quantity += $quantity;
                        $obj->primeCost += $primeCost;
                    }
                }
            }
        }
        

       
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


}