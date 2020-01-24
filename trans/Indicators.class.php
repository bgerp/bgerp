<?php



/**
 * Мениджър на Показатели в логистиката
 *
 *
 * @category  bgerp
 * @package   trans
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 
 * @title     Показатели в логистиката
 */
class trans_Indicators extends core_BaseClass
{
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'hr_IndicatorsSourceIntf';
    
    
    
    /**
     * Интерфейсен метод на hr_IndicatorsSourceIntf
     *
     * @return array $result
     */
    public static function getIndicatorNames()
    {
        $result = array();
        
        // Индикатор за брой транспортни линии за шофьор
        $rec = hr_IndicatorNames::force('Брой_транспортни_линии', __CLASS__, 1);
        $result[$rec->id] = $rec->name;
        
        $rec = hr_IndicatorNames::force('Брой_доставени_пратки', __CLASS__, 2);
        $result[$rec->id] = $rec->name;
        
        $rec = hr_IndicatorNames::force('Доставено_тегло', __CLASS__, 3);
        $result[$rec->id] = $rec->name;
        
       
        
        // Връщане на всички индикатори
        return $result;
    }
    
    
    /**
     * Метод за вземане на резултатност на хората. За определена дата се изчислява
     * успеваемостта на човека спрямо ресурса, които е изпозлвал
     *
     * @param  datetime  $timeline - Времето, след което да се вземат всички модифицирани/създадени записи
     * @return array $result  - масив с обекти
     *
     * 			o date        - дата на стойноста
     * 		    o personId    - ид на лицето
     *          o docId       - ид на документа
     *          o docClass    - клас ид на документа
     *          o indicatorId - ид на индикатора
     *          o value       - стойноста на индикатора
     *          o isRejected  - оттеглена или не. Ако е оттеглена се изтрива от индикаторите
     */
    public static function getIndicatorValues($timeline)
    { 
        $result = array();
        
        // Индикатор за брой на транспортните линии
        $numberOfTransportLinesArr = self::getNumberOfTransportLines($timeline);
        $result = array_merge($numberOfTransportLinesArr, $result);
        
        // Индикатор за брой доставени пратки
        $numberOfShipmentsDeliveredArr = self::getNumberOfShipmentsDelivered($timeline);
        $result = array_merge($numberOfShipmentsDeliveredArr, $result);
        
        // Индикатор за доставено тегло
        $deliveredWeightArr = self::getDeliveredWeight($timeline);
        $result = array_merge($deliveredWeightArr, $result);
        
        return $result;
    }
    
    
    /**
     * Брой транспортни линии
     *
     * @param  datetime  $timeline
     * @return array $result
     */
    private static function getNumberOfTransportLines($timeline)
    {
        $result = array();
        $numberOfTransportLines = hr_IndicatorNames::force('Брой_транспортни_линии', __CLASS__, 1)->id;
        
        $from = trans_Setup::get('DATE_FOR_TRANS_INDICATORS');
        
        if (empty($from)) {
                
            return $result;
        }
     
        $query = trans_Lines::getQuery();
        
        $query->where("#start >= '{$from}'");
        
        $query->where("#modifiedOn >= '{$timeline}'");
        
        while ($iRec = $query->fetch()) {
            
            if (empty($iRec->forwarderPersonId)) {
                continue;
            }
            
        $personId =$iRec->forwarderPersonId;
        
        $Document = doc_Containers::getDocument($iRec->containerId);
        
        $docId = $Document->that;
        
        $docClassId = $Document->getClassId();
        
        $indicatorId = $numberOfTransportLines;
        
        $value = 1;
        
        $isRejected = $iRec->state == 'rejected'? true: false;
            
        hr_Indicators::addIndicatorToArray($result, $iRec->start, $personId,$docId,$docClassId, $indicatorId, $value, $isRejected);
        }
        
        return $result;
    }
    
    /**
     * Брой доставени пратки
     *
     * @param  datetime  $timeline
     * @return array $result
     */
    private static function getNumberOfShipmentsDelivered($timeline)
    {
        $result = array();
        $numberOfShipmentsDelivered = hr_IndicatorNames::force('Брой_доставени_пратки', __CLASS__, 2)->id;
        
        $from = trans_Setup::get('DATE_FOR_TRANS_INDICATORS');
        
        if (empty($from)) {
            
            return $result;
        }
        
        $details = array();
        
        $detQuery = trans_LineDetails::getQuery();
        $detQuery->EXT('modifiedOn', trans_Lines, "externalName=modifiedOn,externalKey=lineId");
        $detQuery->EXT('start', trans_Lines, "externalName=start,externalKey=lineId");
        $detQuery->where("#start >= '{$from}'");
        $detQuery->where("#modifiedOn >= '{$timeline}'");
        
        while ($detRec = $detQuery->fetch()) {
            
            $details[$detRec->id] = $detRec->lineId;
            
        }
       
        $query = trans_Lines::getQuery();
        
        $query->where("#start >= '{$from}'");
        
        $query->where("#modifiedOn >= '{$timeline}'");
        
        while ($iRec = $query->fetch()) {
            
            if (empty($iRec->forwarderPersonId)) {
                continue;
            }
            
            
            
            $personId =$iRec->forwarderPersonId;
            
            $Document = doc_Containers::getDocument($iRec->containerId);
            
            $docId = $Document->that;
            
            $docClassId = $Document->getClassId();
            
            $indicatorId = $numberOfShipmentsDelivered;
            
            $value = 0;
            foreach ($details as $val){
                
                if ($val == $iRec->id){
                    $value ++;
                }
            }
            
            $isRejected = $iRec->state == 'rejected'? true: false;
            
            hr_Indicators::addIndicatorToArray($result, $iRec->start, $personId,$docId,$docClassId, $indicatorId, $value, $isRejected);
        }
        
        return $result;
    }
    
    /**
     * Доставено тегло
     *
     * @param  datetime  $timeline
     * @return array $result
     */
    private static function getDeliveredWeight($timeline)
    {
        $result = array();
        $deliveredWeight = hr_IndicatorNames::force('Доставено_тегло', __CLASS__, 3)->id;
        
        $from = trans_Setup::get('DATE_FOR_TRANS_INDICATORS');
        
        if (empty($from)) {
            
            return $result;
        }
        
        $weights = array();
        
        $detQuery = trans_LineDetails::getQuery();
        $detQuery->EXT('modifiedOn', trans_Lines, "externalName=modifiedOn,externalKey=lineId");
        $detQuery->EXT('start', trans_Lines, "externalName=start,externalKey=lineId");
        $detQuery->where("#start >= '{$from}'");
        $detQuery->where("#modifiedOn >= '{$timeline}'");
        
        while ($detRec = $detQuery->fetch()) {
            
            $Document = doc_Containers::getDocument($detRec->containerId);
            $transInfo = $Document->getTransportLineInfo($detRec->lineId);
            $weights[$detRec->lineId] += $transInfo['weight'];
            
        }
        
        $query = trans_Lines::getQuery();
        
        $query->where("#start >= '{$from}'");
        
        $query->where("#modifiedOn >= '{$timeline}'");
        
        while ($iRec = $query->fetch()) {
            
            if (empty($iRec->forwarderPersonId)) {
                continue;
            }
            
            
            
            $personId =$iRec->forwarderPersonId;
            
            $Document = doc_Containers::getDocument($iRec->containerId);
            
            $docId = $Document->that;
            
            $docClassId = $Document->getClassId();
            
            $indicatorId = $deliveredWeight;
            
            $value = 0;
            foreach ($weights as $key => $val){
                
                if ($key == $iRec->id){
                    $value +=$val;
                }
            }
            
            $isRejected = $iRec->state == 'rejected'? true: false;
            
            hr_Indicators::addIndicatorToArray($result, $iRec->start, $personId,$docId,$docClassId, $indicatorId, $value, $isRejected);
        }
        
        return $result;
    }
   
}
