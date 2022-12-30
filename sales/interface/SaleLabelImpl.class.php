<?php


/**
 * Помощен клас-имплементация на интерфейса label_SequenceIntf за класа sales_Sales
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see label_SequenceIntf
 *
 */
class sales_interface_SaleLabelImpl
{
    /**
     * Инстанция на класа
     */
    public $class;


    /**
     * Връща наименованието на етикета
     *
     * @param int $id
     * @param string $series
     * @return string
     */
    public function getLabelName($id, $series = 'label')
    {
        $rec = $this->class->fetchRec($id);

        return '#' . $this->class->getHandle($rec);
    }


    /**
     * Връща масив с данните за плейсхолдерите
     *
     * @param int|NULL $objId
     * @param string $series
     *
     * @return array
     *               Ключа е името на плейсхолдера и стойностите са обект:
     *               type -> text/picture - тип на данните на плейсхолдъра
     *               len -> (int) - колко символа макс. са дълги данните в този плейсхолдер
     *               readonly -> (boolean) - данните не могат да се променят от потребителя
     *               hidden -> (boolean) - данните не могат да се променят от потребителя
     *               importance -> (int|double) - тежест/важност на плейсхолдера
     *               example -> (string) - примерна стойност
     */
    public function getLabelPlaceholders($objId = null, $series = 'label')
    {
        $placeholders = array();

        $placeholders['PRODUCT_NAME'] = (object) array('type' => 'text', 'hidden' => TRUE);
        $placeholders['CODE'] = (object) array('type' => 'text', 'hidden' => TRUE);
        $placeholders['VALIOR'] = (object) array('type' => 'text');
        $placeholders['QUANTITY'] = (object) array('type' => 'picture', 'hidden' => TRUE);
        $placeholders['QUANTITY_IN_PACK'] = (object) array('type' => 'picture', 'hidden' => TRUE);
        $placeholders['REFF'] = (object) array('type' => 'text');
        $placeholders['SALE_ID'] = (object) array('type' => 'text');
        $placeholders['MEASURE_ID'] = (object) array('type' => 'text', 'hidden' => TRUE);
        $placeholders['NOTES'] = (object) array('type' => 'text', 'hidden' => TRUE);
        $placeholders['PACKAGING_ID'] = (object) array('type' => 'text', 'hidden' => TRUE);

        if (isset($objId)) {
            $labelData = $this->getLabelData($objId, 1, true, null, $series);
            if (isset($labelData[0])) {
                foreach ($labelData[0] as $key => $val) {
                    if(is_object($placeholders[$key])){
                        $placeholders[$key]->example = $val;
                    }
                }
            }
        }

        return $placeholders;
    }


    /**
     * Броя на етикетите, които могат да се отпечатат
     *
     * @param int $id
     * @param string $series
     *
     * @return int
     */
    public function getLabelEstimatedCnt($id, $series = 'label')
    {
        $rec = $this->class->fetchRec($id);

        return sales_SalesDetails::count("#saleId = {$rec->id}");
    }


    /**
     * Връща масив с всички данни за етикетите
     *
     * @param int  $id
     * @param int  $cnt
     * @param bool $onlyPreview
     * @param stdClass $lRec
     * @param string $series
     *
     * @return array - масив от масив с ключ плейсхолдера и стойността
     */
    public function getLabelData($id, $cnt, $onlyPreview = false, $lRec = null, $series = 'label')
    {
        static $resArr = array();

        $key = $id . '|' . $cnt . '|' . $onlyPreview . '|' . core_Lg::getCurrent() . "|" . $series;

        if (isset($resArr[$key])) {

            return $resArr[$key];
        }

        $rec = $this->class->fetchRec($id);

        $handler = "#" . $this->class->getHandle($rec->id);
        $valior = dt::mysql2verbal($rec->valior, 'd.m.Y');

        $dQuery = sales_SalesDetails::getQuery();
        $dQuery->where("#saleId = {$rec->id}");
        $dQuery->orderBy('id', 'ASC');
        $recs = array_values($dQuery->fetchAll());
        array_unshift($recs, null);
        unset($recs[0]);

        $arr = array();
        for ($i = 1; $i <= $cnt; $i++) {
            $dRec = $recs[$i];
            if (!is_object($dRec)) continue;

            $code = cat_Products::fetchField($dRec->productId, 'code');
            $code = !empty($code) ? $code : "Art{$dRec->productId}";
            $name = trim(cat_Products::getVerbal($dRec->productId, 'name'));
            $measureId = cat_Products::fetchField($dRec->productId, 'measureId');
            $quantityInPack = cat_UoM::round($measureId, $dRec->quantityInPack);
            $measureName = cat_UoM::getShortName($measureId);

            Mode::push('text', 'plain');
            $quantity = core_Type::getByName('double(smartRound)')->toVerbal($dRec->packQuantity);
            $quantityInPack = core_Type::getByName('double(smartRound)')->toVerbal($quantityInPack);
            Mode::pop('text');

            $res = array('CODE' => $code,
                'PRODUCT_NAME' => $name,
                'SALE_ID' => "#" . $handler,
                'VALIOR' => $valior,
                'MEASURE_ID' => $measureName,
                'PACKAGING_ID' => cat_UoM::getSmartName($dRec->packagingId),
                'QUANTITY' => $quantity,
                'QUANTITY_IN_PACK' => $quantityInPack,
            );

            if (!empty($dRec->notes)) {
                Mode::push('text', 'plain');
                $res['NOTES'] = core_Type::getByName('richtext')->toVerbal($dRec->notes);
                Mode::pop('text');
            }

            if (!empty($rec->reff)) {
                $res['REFF'] = $rec->reff;
            }

            $Driver = cat_Products::getDriver($dRec->productId);
            $additionalFields = $Driver->getAdditionalLabelData($dRec->productId, $this->class);
            if (countR($additionalFields)) {
                $res += $additionalFields;
            }

            $arr[] = $res;
        }

        $resArr[$key] = $arr;

        return $resArr[$key];
    }


    /**
     * Връща дефолтен шаблон за печат на бърз етикет
     *
     * @param int  $id
     * @param stdClass|null  $driverRec
     *
     * @return int
     */
    public function getDefaultFastLabel($id, $driverRec = null)
    {
        return null;
    }


    /**
     * Връща попълнен дефолтен шаблон с дефолтни данни.
     * Трябва `getDefaultFastLabel` да върне резултат за да се покажат данните
     *
     * @param int  $id
     * @param int $templateId
     *
     * @return core_ET|null
     */
    public function getDefaultLabelWithData($id, $templateId)
    {
        return null;
    }


    /**
     * Кой е дефолтния шаблон за печат към обекта
     *
     * @param $id
     * @param string $series
     * @return int|null
     */
    public function getDefaultLabelTemplateId($id, $series = 'label')
    {
        return null;
    }
}