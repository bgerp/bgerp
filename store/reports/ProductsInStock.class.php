<?php


/**
 * Мениджър на отчети за стоки на склад
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Склад » Складови наличности
 */
class store_reports_ProductsInStock extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, cat';
    
    
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
    protected $changeableFields;
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        
        $fieldset->FLD('date', 'date', 'caption=Към дата,after=title,single=none,mandatory');
        $fieldset->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,placeholder=Всички,after=date,single=none');
        
        $fieldset->FLD('selfPrices', 'enum(balance=По баланс, manager=Мениджърска)', 'notNull,caption=Филтри->Вид цени,after=storeId,single=none');
        
        $fieldset->FLD('group', 'keylist(mvc=cat_Groups,select=name)', 'caption=Филтри->Група артикули,placeholder=Всички,after=selfPrices,single=none');
        $fieldset->FLD('products', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax)', 'caption=Филтри->Артикули,placeholder=Всички,after=group,single=none,class=w100');
        $fieldset->FLD('availability', 'enum(Всички=Всички, Налични=Налични,Отрицателни=Отрицателни)', 'notNull,caption=Филтри->Наличност,maxRadio=3,columns=3,after=products,single=none');
        
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
        
        $form->setDefault('selfPrices', 'balance');
        $form->setDefault('availability', 'Всички');
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
        
        $storeItemId = $rec->storeId ? acc_Items::fetchItem('store_Stores', $rec->storeId)->id : null;
        $productItemId = $rec->products ? acc_Items::fetchItem('cat_Products', $rec->products)->id : null;
        
        $Balance = new acc_ActiveShortBalance(array('from' => $rec->date, 'to' => $rec->date, 'accs' => '321','item1' => $storeItemId, 'item2' => $productItemId, 'cacheBalance' => false, 'keepUnique' => true));
        $bRecs = $Balance->getBalance('321');
        
        foreach ($bRecs as $item) {

            $iRec = acc_Items::fetch($item->ent2Id);
            $id = $iRec->objectId;
            
            
            //Филтър по групи артикули
            if (isset($rec->group)) {
                
                $checkGdroupsArr = keylist::toArray($rec->group);
                if(!keylist::isIn($checkGdroupsArr, cat_Products::fetch($iRec->objectId)->groups))continue;
            }
              
            //Код на продукта
            list($productCode) = explode(' ', $iRec->num);
            
            //Продукт ID
            $productId = $iRec->objectId;
            
            //Име на продукта
            $productName = $iRec->title;
            
            
            //Количество в началото на периода
            $baseQuantity = $item->baseQuantity;  
          
            //Стойност в началото на периода
            $baseAmount = $item->baseAmount;
            
            //Дебит оборот количество
            $debitQuantity = $item->debitQuantity;
            
            //Дебит оборот стойност
            $debitAmount = $item->debitAmount;
            
            //Кредит оборот количество
            $creditQuantity = $item->creditQuantity;
            
            //Кредит оборот стойност
            $creditAmount = $item->creditAmount;
            
            //Количество в края на периода
            $blQuantity = $item->blQuantity;
            
            if(($rec->availability == 'Налични') && $blQuantity < 0)continue;
            if($rec->availability == 'Отрицателни' && $blQuantity >= 0)continue;
            
            //Стойност в края на периода
            $blAmount = $item->blAmount;
            
            // добавя в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    
                    'productId'=> $productId,
                    'code' => $productCode,
                    'productName' => $productName,
                    
                    'selfPrice' => '',
                    'amount' => '',
                    
                    'baseQuantity' => $baseQuantity,
                    'baseAmount' => $baseAmount,
                    
                    'debitQuantity' => $debitQuantity,
                    'debitAmount' => $debitAmount,
                    
                    'creditQuantity' => $creditQuantity,
                    'creditAmount' => $creditAmount,
                    
                    'blQuantity' => $blQuantity,
                    'blAmount' => $blAmount,
                    
                );
            } else {
                $obj = &$recs[$id];
                
                $obj->baseQuantity += $baseQuantity;
                $obj->baseAmount += $baseAmount;
                
                $obj->debitQuantity += $debitQuantity;
                $obj->debitAmount += $debitAmount;
                
                $obj->creditQuantity += $creditQuantity;
                $obj->creditAmount += $creditAmount;
                
                $obj->blQuantity += $blQuantity;
                $obj->blAmount += $blAmount;
            }
        }
        
        foreach ($recs as $key => $val){
          
            //Себестойност на артикула
            if ($rec->selfPrice == 'manager'){
                $val->selfPrice = cat_Products::getPrimeCost($key, null, $val->blQuantity, $rec->date);
            }else{
                
                $val->selfPrice =$val->blQuantity ? $val->blAmount / $val->blQuantity : null;
            }
            $val->amount = $val->selfPrice * $val->blQuantity;
           
        }
      
        arr::sortObjects($recs, 'productName', 'ASC', 'stri');
        
        return $recs;
    }
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec    - записа
     * @param bool     $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        
        $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        $fld->FLD('code', 'varchar', 'caption=Код,tdClass=centered');
        $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
        
        $fld->FLD('quantyti', 'double(smartRound,decimals=2)', 'caption=Количество');
        $fld->FLD('selfPrice', 'double(smartRound,decimals=2)', 'caption=Себестойност');
        $fld->FLD('amount', 'double(smartRound,decimals=2)', 'caption=Стойност');
        
       
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
        
        
        if (isset($dRec->code)) {
            $row->code = $dRec->code;
        }
        if (isset($dRec->productId)) {
            $row->productId = cat_Products::getLinkToSingle_($dRec->productId, 'name');
        }
        
        $row->measure = cat_UoM::fetchField(cat_Products::fetch($dRec->productId)->measureId, 'shortName');
        
        
        if (isset($dRec->blQuantity)) {
            $row->quantyti = $Double->toVerbal($dRec->blQuantity);
        }
        
        if (isset($dRec->selfPrice)) {
            $row->selfPrice = $Double->toVerbal($dRec->selfPrice);
        }
        
        $row->amount = $Double->toVerbal($dRec->selfPrice * $dRec->blQuantity);
        
        
        
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
        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        
        
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                <small><div><!--ET_BEGIN date-->|Към дата|*: [#date#]<!--ET_END date--></div></small>
                                <small><div><!--ET_BEGIN storeId-->|Склад|*: [#storeId#]<!--ET_END storeId--></div></small>
                                <small><div><!--ET_BEGIN group-->|Групи|*: [#group#]<!--ET_END group--></div></small>
                                <small><div><!--ET_BEGIN products-->|Артикул|*: [#products#]<!--ET_END products--></div></small>
                                <small><div><!--ET_BEGIN availability-->|Наличност|*: [#availability#]<!--ET_END availability--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
        if (isset($data->rec->date)) {
            $fieldTpl->append('<b>' .$Date->toVerbal($data->rec->date) . '</b>', 'date');
        }
        
        if (isset($data->rec->group)) {
            foreach (type_Keylist::toArray($data->rec->group) as $group) {
                $marker++;
                
                $groupVerb .= (cat_Groups::getTitleById($group));
                
                if ((count((type_Keylist::toArray($data->rec->group))) - $marker) != 0) {
                    $groupVerb .= ', ';
                }
            }
            
            $fieldTpl->append('<b>' . $groupVerb . '</b>', 'group');
        }
        
        
        if ((isset($data->rec->storeId))) {
            $fieldTpl->append('<b>'. store_Stores::getTitleById($data->rec->storeId) .'</b>', 'storeId');
        }
        
        if ((isset($data->rec->products))) {
            $fieldTpl->append('<b>'. cat_Products::getTitleById($data->rec->products) .'</b>', 'products');
        }
        
        if ((isset($data->rec->availability))) {
            $fieldTpl->append('<b>'. ($data->rec->availability) .'</b>', 'availability');
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
    }
}
