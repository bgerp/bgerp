<?php


/**
 * Помощен клас-имплементация на интерфейса export_XmlExportIntf за класа store_ShipmentOrders
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see export_XmlExportIntf
 *
 */
class store_iface_ShipmentOrderToXmlImpl
{
    /**
     * Инстанция на класа
     */
    public $class;


    /**
     * Експортира документа в xml формат
     *
     * @see export_Xml
     * @param mixed $id
     * @return core_ET $tpl
     */
    public function exportAsXml($id)
    {
        $rec = $this->class->fetchRec($id);
        $fields = $this->class->selectFields();
        $fields['-single'] = true;

        $Detail = cls::get('store_ShipmentOrderDetails');
        core_Lg::push('bg');
        Mode::push('text', 'plain');
        $row = $this->class->recToVerbal($rec, $fields);
        Mode::pop();
        $tpl = getTplFromFile('store/tpl/ShipmentOrderXml.xml');
        core_Lg::pop();

        // Кои полета директно от записа ще се покажат в xml-а
        $data = new stdClass();
        $fieldsFromRec = array('id', 'valior', 'note', 'currencyRate', 'currencyId');
        foreach ($fieldsFromRec as $fld){
            $data->{$fld} = $rec->{$fld};
        }

        // Кои полета ще се вземат от вербалното показване
        $fieldsFromRow = array('storeId', 'locationId', 'MyCompany', 'MyCompanyVatNo', 'contragentName', 'eori', 'uicId', 'inlineContragentAddress', 'MyAddress', 'contragentUicId', 'responsible', 'weight', 'volume', 'amountDeliveredVat', 'amountDiscount', 'amountDelivered', 'lineForwarderId', 'lineVehicleId');
        foreach ($fieldsFromRow as $fld){
            $val = is_object($row->{$fld}) ? $row->{$fld}->getContent() : $row->{$fld};
            $data->{$fld} = trim(strip_tags($val));
        }

        $data->vatIncluded = in_array($rec->chargeVat, array('yes', 'separate')) ? 'yes' : 'no';
        $logisticData = $this->class->getLogisticData($rec);

        // Адресните данни ще се вземат от логистичния интерфейс
        $fieldsFromLogData = array('toPCode', 'toPlace', 'toAddress', 'toCompany', 'toPerson', 'loadingTime');
        foreach ($fieldsFromLogData as $fld){
            $data->{$fld} = $logisticData[$fld];
        }
        $deliveryCountryId = drdata_Countries::getIdByName($logisticData['toCountry']);
        $data->countryCode = drdata_Countries::fetchField($deliveryCountryId, 'letterCode2');
        $data->contragentClassId = cls::get($rec->contragentClassId)->singleTitle;

        $products = array();
        $dQuery = store_ShipmentOrderDetails::getQuery();
        $dQuery->where("#shipmentId = {$rec->id}");
        $dRecs = $dQuery->fetchAll();

        deals_Helper::fillRecs($this, $dRecs, $rec);

        while($dRec = $dQuery->fetch()){
            $dRow = store_ShipmentOrderDetails::recToVerbal($dRec);
            $pRec = new stdClass();
            $productRec = cat_Products::fetch($dRec->productId, 'name,code');
            $pRec->productId = $productRec->name;
            $pRec->code = !empty($productRec->code) ? $productRec->code : "#Art{$productRec->id}";
            $pRec->packQuantity = $dRec->packQuantity;
            $pRec->amount = core_Math::roundNumber($dRec->packPrice / $rec->currencyRate);
            $pRec->vat = core_Type::getByName('percent')->toVerbal(cat_Products::getVat($dRec->productId, $rec->valior));
            $fieldsFromDetailRow = array('packagingId', 'notes', 'weight', 'volume', 'discount');
            foreach ($fieldsFromDetailRow as $fld){
                $val = is_object($dRow->{$fld}) ? $dRow->{$fld}->getContent() : $dRow->{$fld};
                $pRec->{$fld} = trim(strip_tags($val));
            }

            $pRec->batches = array();
            $Def = batch_Defs::getBatchDef($dRec->productId);
            if (is_object($Def)) {
                $batches = "";
                $bQuery = batch_BatchesInDocuments::getQuery();
                $bQuery->where("#detailClassId = {$Detail->getClassId()} AND #detailRecId = {$dRec->id} AND #productId = {$dRec->{$Detail->productFld}} AND #operation = 'out'");

                while($bRec = $bQuery->fetch()){
                    $batches = batch_Defs::getBatchArray($dRec->productId, $bRec->batch);
                    $quantity = (countR($batches) == 1) ? $bRec->quantity : $bRec->quantity / countR($batches);
                    foreach ($batches as $b) {
                        Mode::push('text', 'plain');
                        $bVerbal = strip_tags($Def->toVerbal($b));
                        Mode::pop('text');
                        $pRec->batches[] = (object)array('name' => $bVerbal, 'quantity' => $quantity);
                    }
                }
            }
            $products[] = $pRec;
        }

        // Добавяне и на партидите, ако има
        $block = $tpl->getBlock('PRODUCT');
        foreach ($products as $pRow){
            $blockClone = clone $block;
            $blockClone->placeObject($pRow);

            foreach ($pRow->batches as $batchObject){
                $batchBlock = clone $block->getBlock('BATCH');
                $batchBlock->placeObject($batchObject);
                $batchBlock->removeBlocksAndPlaces();
                $blockClone->append($batchBlock, 'BATCHES');
            }

            $blockClone->removeBlocksAndPlaces();
            $tpl->append($blockClone, 'PRODUCTS');
        }

        $tpl->placeObject($data);

        return $tpl;
    }
}