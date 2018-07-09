<?php


/**
 * Помощен клас-имплементация на интерфейса label_SequenceIntf за класа store_ShipmentOrders
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see label_SequenceIntf
 *
 */
class store_iface_ShipmentLabelImpl
{
    /**
     * Инстанция на класа
     */
    public $class;
    
    
    /**
     * Връща наименованието на етикета
     *
     * @param int $id
     *
     * @return string
     */
    public function getLabelName($id)
    {
        $rec = $this->class->fetchRec($id);
        
        return '#' . $this->class->getHandle($rec);
    }
    
    
    /**
     * Връща масив с данните за плейсхолдерите
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
        $placeholders['NOMER'] = (object) array('type' => 'text');
        $placeholders['DESTINATION'] = (object) array('type' => 'text');
        $placeholders['SPEDITOR'] = (object) array('type' => 'text');
        $placeholders['DATE'] = (object) array('type' => 'text');
        
        if (isset($objId)) {
            $labelData = $this->getLabelData($objId, 1, true);
            if (isset($labelData[0])) {
                foreach ($labelData[0] as $key => $val) {
                    $placeholders[$key]->example = $val;
                }
            }
        }
        
        return $placeholders;
    }
    
    
    /**
     * Броя на етикетите, които могат да се отпечатат
     *
     * @param int    $id
     * @param string $allowSkip
     *
     * @return int
     *
     * @see label_SequenceIntf
     */
    public function getLabelEstimatedCnt($id)
    {
        $rec = $this->class->fetchRec($id);
        
        $count = 0;
        $transUnits = is_array($rec->transUnits) ? $rec->transUnits : array();
        array_walk($transUnits, function ($e) use (&$count) {
            $count += $e;
        });
        $count = max(1, $count);
        
        if (isset($count)) {
            $count = ceil($count);
            if ($count % 2 == 1) {
                $count++;
            }
        }
        
        return $count;
    }
    
    
    /**
     * Връща масив с всички данни за етикетите
     *
     * @param int  $id
     * @param int  $cnt
     * @param bool $onlyPreview
     *
     * @return array - масив от масиви с ключ плейсхолдера и стойността
     */
    public function getLabelData($id, $cnt, $onlyPreview = false)
    {
        static $resArr = array();
        
        $key = $id . '|' . $cnt . '|' . $onlyPreview . '|' . core_Lg::getCurrent();
        
        if (isset($resArr[$key])) {
            
            return $resArr[$key];
        }
        
        $rec = $this->class->fetchRec($id);
        $logisticData = $this->class->getLogisticData($rec);
        $destination = trim("{$logisticData['toPCode']} {$logisticData['toPlace']}, {$logisticData['toCountry']}");
        $date = dt::mysql2verbal(dt::today(), 'd/m/y');
        
        $arr = array();
        for ($i = 1; $i <= $cnt; $i++) {
            $res = array('NOMER' => $rec->id, 'DESTINATION' => $destination, 'DATE' => $date);
            if (isset($rec->lineId)) {
                $res['SPEDITOR'] = trans_Lines::getTitleById($rec->lineId);
            }
            
            $arr[] = $res;
        }
        
        $resArr[$key] = $arr;
        
        return $resArr[$key];
    }
}
