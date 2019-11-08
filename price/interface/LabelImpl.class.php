<?php


/**
 * Помощен клас-имплементация на интерфейса label_SequenceIntf за класа price_reports_PriceList
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see label_SequenceIntf
 *
 */
class price_interface_LabelImpl
{
    /**
     * Инстанция на класа
     */
    public $class;
    
    
    /**
     * Връща масив с данните за плейсхолдерите
     *
     * @param int|NULL $objId
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
    public function getLabelPlaceholders($objId = null)
    {
        $placeholders = array();
        $placeholders['EAN'] = (object) array('type' => 'text', 'hidden' => true);
        $placeholders['CODE'] = (object) array('type' => 'text', 'hidden' => true);
        $placeholders['NAME'] = (object) array('type' => 'text', 'hidden' => true);
        $placeholders['CATALOG_CURRENCY'] = (object) array('type' => 'text', 'hidden' => true);
        $placeholders['CATALOG_PRICE'] = (object) array('type' => 'text', 'hidden' => true);
        
        return $placeholders;
    }
    
    
    /**
     * Връща масив с всички данни за етикетите
     *
     * @param int  $id
     * @param int  $cnt
     * @param bool $onlyPreview
     *
     * @return array - масив от масив с ключ плейсхолдера и стойността
     */
    public function getLabelData($id, $cnt, $onlyPreview = false)
    {
        $resArr = array();
        $rec = frame2_Reports::fetchRec($id);
        $recs = $rec->data->recs;
        $round = isset($rec->round) ? $rec->round : price_reports_PriceList::DEFAULT_ROUND;
        $Double = core_Type::getByName("double(decimals={$round})");
        
        $currentCount = 0;
        Mode::push('text', 'plain');
        if(is_array($recs)){
            foreach ($recs as $pRec){
                $ean = '';
                if($onlyPreview === true){
                    $ean = '0000000000000';
                }
                
                $name = cat_Products::getVerbal($pRec->productId, 'name');
                $name = str::limitLen($name, 70);
                $code = cat_Products::getVerbal($pRec->productId, 'code');
                $code = !empty($code) ? $code : "Art{$pRec->productId}";
                
                if($rec->showMeasureId == 'yes' && !empty($pRec->price)){
                    $res = array('EAN' => $ean, 'NAME' => $name, 'CATALOG_CURRENCY' => $rec->currencyId, 'CATALOG_PRICE' => $Double->toVerbal($pRec->price), "CODE" => $code);
                    $resArr[] = $res;
                    $currentCount++;
                    if($currentCount == $cnt) break;
                }
                
                foreach ($pRec->packs as $packRec){
                    $ean = $packRec->eanCode;
                    $res = array('EAN' => $ean, 'NAME' => $name, 'CATALOG_CURRENCY' => $rec->currencyId, 'CATALOG_PRICE' =>  $Double->toVerbal($pRec->price), "CODE" => $code);
                    $resArr[] = $res;
                    $currentCount++;
                    if($currentCount == $cnt) break;
                }
            }
        }
        
        Mode::pop('text', 'plain');
        
        return $resArr;
    }
    
    
    /**
     * Броя на етикетите, които могат да се отпечатат
     *
     * @param int $id
     *
     * @return int
     */
    public function getLabelEstimatedCnt($id)
    {
        $rec = frame2_Reports::fetchRec($id);
        
        $count = 0;
        if(is_array($rec->data->recs)){
            foreach ($rec->data->recs as $dRec){
                if($rec->showMeasureId == 'yes' && !empty($dRec->price)){
                    $count++;
                }
                $count += count($dRec->packs);
            }
        }
        
        return $count;
    }
    
    
    /**
     * Връща наименованието на етикета
     *
     * @param int $id
     *
     * @return string
     */
    public function getLabelName($id)
    {
        $rec = frame2_Reports::fetchRec($id);
        
        return $rec->title;
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
}