<?php



/**
 * Мениджър на отчети за налични количества
 *
 * @category  bgerp
 * @package   store
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Склад » Артикули налични количества
 */

class store_reports_ProductAvailableQuantity extends frame2_driver_TableData
{


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,manager,store';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Артикул,mandatory');
        $fieldset->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,after=title');
        $fieldset->FLD('minQuantity', 'double(decimals=2)', 'caption=Мин к-во');
        $fieldset->FLD('maxQuantity', 'double(decimals=2)', 'caption=Макс к-во');
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

            if ($form->rec->minQuantity < 0 || $form->rec->maxQuantity < 0) {
                $form->setError('minQuantity, maxQuantity', 'Количествата трябва  да са положителни');
            }

            if(isset($form->rec->minQuantity,$form->rec->maxQuantity)) {

                if ($form->rec->maxQuantity < $form->rec->minQuantity) {
                    $form->setError('minQuantity, maxQuantity', 'Максималното количество не може да бъде по-малко от минималното');
                }
            }
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
        $recs = array();

        $query = store_Products::getQuery();

        $query->where("#productId = $rec->productId");
        if(isset($rec->storeId)){
            $query->where("#storeId = $rec->storeId");
        }

        $quantityMark = '';
        $conditionColor = '';

        while($recProduct = $query->fetch())
        {
            $id = $recProduct->productId;

            $quantity = store_Products::getQuantity($id, $recProduct->storeId, FALSE);

            if(($quantity < $rec->minQuantity)){
                $quantityMark = 'под минимум';
            } elseif (($quantity > $rec->maxQuantity)){
                $quantityMark = 'свръх наличност';
            } else{
                $quantityMark = 'ok';
            }

            if(!isset($rec->maxQuantity)){
                if($quantity > $rec->minQuantity){
                    $quantityMark = 'ok';
                }
            }

            if(!array_key_exists($id,$recs)) {
                $recs[$id]=
                    (object) array (

    //                    'kod' => cat_Products::fetchField($recProduct->productId, 'code'),
                        'measure' => cat_Products::fetchField($id, 'measureId'),
                        'productId' => $recProduct->productId,
                        'storeId' => $rec->storeId,
                        'quantity' => $quantity,
                        'minQuantity'=> $rec->minQuantity,
                        'maxQuantity'=> $rec->maxQuantity,
                        'conditionQuantity' => $quantityMark,
                        'conditionColor' => $conditionColor
                    );
            } else {
                $obj = &$recs[$id];
                $obj->quantity += $recProduct->quantity;

            }

        }

        return $recs;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec   - записа
     * @param boolean $export - таблицата за експорт ли е
     * @return core_FieldSet  - полетата
     */
    protected function getTableFieldSet($rec, $export = FALSE)
    {
        $fld = cls::get('core_FieldSet');

        if($export === FALSE){
      //      $fld->FLD('kod', 'varchar','caption=Код');
            $fld->FLD('productId', 'varchar', 'caption=Артикул');
            $fld->FLD('storeId', 'varchar', 'caption=Склад,tdClass=centered');
            $fld->FLD('measure', 'varchar', 'caption=Мярка,tdClass=centered');
            $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'caption=Наличност,smartCenter');
            if(isset($rec->minQuantity)) {
                $fld->FLD('minQuantity', 'double', 'caption=Минимално,smartCenter');
            }
            if(isset($rec->maxQuantity)) {
                $fld->FLD('maxQuantity', 'double', 'caption=Максимално,smartCenter');
            }
            if((isset($rec->minQuantity)) || (isset($rec->maxQuantity))) {
                $fld->FLD('conditionQuantity', 'text', 'caption=Състояние,tdClass=centered');
            }
        } else {
      //      $fld->FLD('kod', 'varchar','caption=Код');
            $fld->FLD('productId', 'varchar', 'caption=Артикул');
            $fld->FLD('storeId', 'varchar', 'caption=Склад,tdClass=centered');
            $fld->FLD('measure', 'varchar', 'caption=Мярка,tdClass=centered');
            $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'caption=Наличност,smartCenter');
            if(isset($rec->minQuantity)) {
                $fld->FLD('minQuantity', 'double', 'caption=Минимално,smartCenter');
            }
            if(isset($rec->maxQuantity)) {
                $fld->FLD('maxQuantity', 'double', 'caption=Максимално,smartCenter');
            }

            if((isset($rec->maxQuantity)) || (isset($rec->maxQuantity))) {
                $fld->FLD('conditionQuantity', 'text', 'caption=Състояние,tdClass=centered');
            }
        }

        return $fld;
    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec  - записа
     * @param stdClass $dRec - чистия запис
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {

        if($dRec->quantity<$dRec->minQuantity){
            $conditionColor = 'red';
        }
        elseif ($dRec->quantity>$dRec->maxQuantity) {
            $conditionColor = 'blue';
        }else{$conditionColor = 'green';}

        $row = new stdClass();

        if(isset($dRec->productId)) {
            $row->productId =  cat_Products::getShortHyperlink($dRec->productId);
        }

        if(isset($dRec->quantity)) {
            $row->quantity = $dRec->quantity;
        }

        if(isset($dRec->storeId)) {
            $row->storeId = store_Stores::getShortHyperlink($dRec->storeId);
        }

        if(isset($dRec->measure)) {
            $row->measure = cat_UoM::fetchField($dRec->measure,'shortName');
        }

        if(isset($dRec->minQuantity)) {
            $row->minQuantity = $dRec->minQuantity;
        }

        if(isset($dRec->maxQuantity)) {
            $row->maxQuantity = $dRec->maxQuantity;
        }

        if((isset($dRec->conditionQuantity) && ((isset($dRec->minQuantity)) || (isset($dRec->maxQuantity))))){
            $row->conditionQuantity = "<span style='color: $conditionColor'>{$dRec->conditionQuantity}</span>";
         }

        return $row;
    }

}