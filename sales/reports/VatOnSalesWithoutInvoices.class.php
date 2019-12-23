<?php


/**
 * Мениджър на отчети за начислено ДДС при продажба без фактура:-по арткули
 *
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Продажби » ДДС при продажба без фактура
 */
class sales_reports_VatOnSalesWithoutInvoices extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, store, sales, admin, purchase';
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver   $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = &$data->form;
        
        $form->setDefault('orderBy', 'name');
        
        $lastClosedMonth = dt::addMonths(-1, dt::today());
        
        $lastClosedMonthRec = acc_Periods::fetchByDate($lastClosedMonth);
        
        $form->setDefault('periodId', $lastClosedMonthRec->id);
        $form->setDefault('currency', acc_Periods::getBaseCurrencyCode());
    }
    
    
    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'periodId';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('periodId', 'key(mvc=acc_Periods,select=title)', 'caption=Период,after=title');
        $fieldset->FLD('orderBy', 'enum(name=Име,code=Код,quantity=Количество,amount=Стойност)', 'caption=Сортиране по,maxRadio=4,columns=4,after=periodId');
        $fieldset->FLD('totalVat', 'double(decimals=2)', 'caption=ДДС за периода,export=Csv,input=none');
        $fieldset->FLD('currency', 'varchar', 'caption=Валута,export=Csv,input=none');
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
        
        $query = sales_SalesDetails::getQuery();
        
        $query->EXT('closedOn', 'sales_Sales', 'externalKey=saleId');
        $query->EXT('chargeVat', 'sales_Sales', 'externalKey=saleId');
        $query->EXT('makeInvoice', 'sales_Sales', 'externalKey=saleId');
        $query->EXT('state', 'sales_Sales', 'externalKey=saleId');
        $query->EXT('code', 'cat_Products', 'externalKey=productId');
        $query->where("#state = 'closed'");
        $query->where(array("#closedOn >= '[#1#]' AND #closedOn <= '[#2#]'", acc_Periods::fetch($rec->periodId)->start, acc_Periods::fetch($rec->periodId)->end . ' 23:59:59'));
        
        $query->where("#makeInvoice = 'no'");
        $query->where(array("#chargeVat = '[#1#]' OR #chargeVat = '[#2#]'", 'yes', 'separate'));
        
        // $query->orderBy('code', 'ASC');
        
        $totalVat = 0;
        
        while ($articul = $query->fetch()) {
            $salesInfo = explode('/', sales_Sales::getRecTitle($articul->saleId));
            
            $id = $articul->productId;
            
            $discountedAmount = $articul->amount - ($articul->amount * $articul->discount);
            
            if ($articul->productId) {
                $totalVat += $discountedAmount * cat_Products::getVat($articul->productId);
            }
            
            if (!array_key_exists($id, $recs)) {
                $recs[$id] =
                    
                    (object) array(
                        'productId' => $articul->productId,
                        'name' => cat_Products::getVerbal($articul->productId, 'name'),
                        'measure' => cat_Products::fetchField($id, 'measureId'),
                        'quantity' => $articul->quantity,
                        'amount' => $discountedAmount,
                        'vat' => (double) 0,
                        'price' => $articul->price,
                        'code' => $articul->code,
                        'hint' => $salesInfo[0],
                    
                    );
            } else {
                $obj = &$recs[$id];
                
                $obj->quantity += $articul->quantity;
                
                $obj->amount += $discountedAmount;
                
                $obj->hint .= '; '.$salesInfo[0];
            }
            if ($articul->productId) {
                $recs[$id]->vat = (double) ($recs[$id]->amount * cat_Products::getVat($articul->productId));
            }
            
            $recs[$id]->price = (double) ($recs[$id]->amount / $recs[$id]->quantity);
        }
        
        $rec->totalVat = $totalVat;
        
        switch ($rec->orderBy) {
                
                case 'amount':
                    $f = 'orderByAmount';
                    break;
                
                case 'quantity':
                    $f = 'orderByQuantity';
                    break;
                
                case 'code':
                    $f = 'orderByCode';
                    break;
                
                case 'name':
                    $f = 'orderByName';
                    break;
            }
        
        if (is_array($recs)) {
            usort($recs, array($this, "${f}"));
        }
        
        return $recs;
    }
    
    public function orderByQuantity($a, $b)
    {
        return $a->quantity < $b->quantity;
    }
    
    public function orderByAmount($a, $b)
    {
        return $a->amount < $b->amount;
    }
    
    public function orderByCode($a, $b)
    {
        return strcmp($a->code, $b->code);
    }
    
    public function orderByName($a, $b)
    {
        return strcmp($a->name, $b->name);
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
        
        $fld->FLD('code', 'varchar', 'caption=Код');
        $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка');
        $fld->FLD('quantity', 'double', 'caption=Количество');
        $fld->FLD('price', 'double', 'caption=Ед.цена');
        $fld->FLD('amount', 'double', 'caption=Стойност');
        $fld->FLD('vat', 'double', 'caption=ДДС');
        
        return $fld;
    }
    
    
    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec  - записа
     * @param stdClass $dRec - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $Int = cls::get('type_Int');
        $Double = core_Type::getByName('double(smartRound)');
        $Date = cls::get('type_Date');
        
        $row = new stdClass();
        
        if (isset($dRec->productId)) {
            $row->productId = cat_Products::getLinkToSingle_($dRec->productId, 'name');
        }
        
        $row->code = ($dRec->code) ? ($dRec->code) : "Art{$dRec->productId}";
        
        if (isset($dRec->quantity)) {
            $row->quantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantity) ;
        }
        
        if (isset($dRec->measure)) {
            $row->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
        }
        
        if (isset($dRec->amount)) {
            $row->amount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->amount);
        }
        
        if (isset($dRec->price)) {
            $row->price = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->price);
        }
        
        if (isset($dRec->vat)) {
            $row->vat = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->vat);
            $row->vat = ht::createHint($row->vat, "{$dRec->hint}", 'notice');
        }
        
        return $row;
    }
    
    
    /**
     * След вербализирането на данните
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $row
     * @param stdClass            $rec
     * @param array               $fields
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
        if (isset($rec->periodId)) {
            $row->periodId = acc_Periods::getLinkForObject($rec->periodId);
        }
    }
}
