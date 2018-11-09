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
        $placeholders['PRODUCTID'] = (object) array('type' => 'text', 'hidden' => true);
        $placeholders['PRICE'] = (object) array('type' => 'text', 'hidden' => true);
        
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
       
        $currentCount = 0;
        foreach ($recs as $pRec){
            if($currentCount == $cnt) break;
            
            $res = array('PRODUCTID' => cat_Products::getTitleById($pRec->productId), 'PRICE' => $pRec->price);
            $currentCount++;
            
            $resArr[] = $res;
        }
        
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
        
        return count($rec->data->recs);
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
    
}