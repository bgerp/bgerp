<?php


/**
 * Базов драйвер за партиден клас 'срок на годност'
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title Срок на годност
 */
class batch_definitions_ExpirationDate extends batch_definitions_Date
{
    /**
     * Име на полето за партида в документа
     *
     * @param string
     */
    public $fieldCaption = 'Ср. год.';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        parent::addFields($fieldset);
        $fieldset->FLD('time', 'time(suggestions=1 ден|2 дена|1 седмица|1 месец)', 'caption=Срок по подразбиране,unit=след текущата дата');
    }
    
    
    /**
     * Връща автоматичния партиден номер според класа
     *
     * @param mixed         $documentClass - класа за който ще връщаме партидата
     * @param int           $id            - ид на документа за който ще връщаме партидата
     * @param int           $storeId       - склад
     * @param datetime|NULL $date          - дата
     *
     * @return mixed $value        - автоматичния партиден номер, ако може да се генерира
     */
    public function getAutoValue($documentClass, $id, $storeId, $date = null)
    {
        $date = dt::today();
        
        $time = cat_Products::getParams($this->rec->productId, 'expiryTime');
        if (empty($time)) {
            $time = $this->rec->time;
        }
        
        if (isset($time)) {
            $date = dt::addSecs($time, $date);
            $date = dt::verbal2mysql($date, false);
        }
        
        $date = dt::mysql2verbal($date, $this->rec->format);
        
        return $date;
    }
    
    
    /**
     * Оцветява датата според зададените данни
     * 
     * @param string $date
     * @param string $format
     * @param int $time
     * 
     * @return mixed $value
     */
    public static function displayExpiryDate($date, $format, $time)
    {
        if (Mode::isReadOnly()) {
            
            return cls::get('type_Html')->toVerbal($date);
        }
        
        $currentTime = strtotime(dt::today());
        $mysqlValue = dt::getMysqlFromMask($date, $format);
        
        if(stripos($format, 'd') === false){
            $mysqlValue = dt::getLastDayOfMonth($mysqlValue);
        }
        
        // Ако партидата е изтекла оцветяваме я в червено
        if (strtotime($mysqlValue) < $currentTime) { 
        $valueHint = ht::createHint($date, 'Срокът на годност на партидата е изтекъл', 'warning');
            $value = new core_ET("<span class='red'>[#value#]</span>");
            $value->replace($valueHint, 'value');
        } else {
            $value = $date;
            
            // Ако има срок на годност
            if (!empty($time)) {
               
                $startDate = dt::addSecs(-1 * $time, $mysqlValue);
                $startDate = dt::verbal2mysql($startDate, false);
                $startTime = strtotime($startDate);
                $endTime = strtotime($mysqlValue);
                
                // Намираме колко сме близо до изтичането на партидата
                $percent = ($currentTime - $startTime) / ($endTime - $startTime);
                $percent = round($percent, 2);
                
                // Оцветяваме я в оранжево ако сме наближили края и
                if ($percent > 0) {
                    $confPercent = core_Packs::getConfigValue('batch', 'BATCH_EXPIRYDATE_PERCENT');
                    $percentToCompare = 1 - $confPercent;
                    
                    if ($percent >= $percentToCompare) {
                        $valueHint = ht::createHint($value, 'Партидата изтича скоро', 'warning');
                        $value = new core_ET("<span style='color:orange'>[#value#]</span>");
                        $value->replace($valueHint, 'value');
                    }
                }
            }
        }
        
        return cls::get('type_Html')->toVerbal($value);
    }
    
    
    /**
     * Кой може да избере драйвера
     */
    public function toVerbal($value)
    {
        return self::displayExpiryDate($value, $this->rec->format, $this->rec->time);
    }
    
    
    /**
     * Връща масив с опции за лист филтъра на партидите
     *
     * @return array - масив с опции
     *               [ключ_на_филтъра] => [име_на_филтъра]
     */
    public function getListFilterOptions()
    {
        return array('expiration' => 'Срок на годност');
    }
    
    
    /**
     * Добавя филтър към заявката към  batch_Items възоснова на избраната опция (@see getListFilterOptions)
     *
     * @param core_Query $query          - заявка към batch_Items
     * @param string     $value          -стойност на филтъра
     * @param string     $featureCaption - Заглавие на колоната на филтъра
     *
     * @return void
     */
    public function filterItemsQuery(core_Query &$query, $value, &$featureCaption)
    {
        expect($query->mvc instanceof batch_Items, 'Невалидна заявка');
        $options = $this->getListFilterOptions();
        expect(array_key_exists($value, $options), "Няма такава опция|* '{$value}'");
        
        // Ако е избран филтър за срок на годност
        if ($value == 'expiration') {
            
            // Намиране на партидите със свойство 'срок на годност'
            $featQuery = batch_Features::getQuery();
            
            $name = batch_Features::canonize('Срок на годност');
            $featQuery->where("#name = '{$name}'");
            $featQuery->orderBy('value', 'ASC');
            $itemsIds = arr::extractValuesFromArray($featQuery->fetchAll(), 'itemId');
            $query->in('id', $itemsIds);
            
            // Ако има ще бъдат подредени по стойноста на срока им
            if (is_array($itemsIds) && countR($itemsIds)) {
                $count = 1;
                $case = 'CASE #id WHEN ';
                foreach ($itemsIds as $id) {
                    $when = ($count == 1) ? '' : ' WHEN ';
                    $case .= "{$when}{$id} THEN {$count}";
                    $count++;
                }
                $case .= ' END';
                $query->XPR('orderById', 'int', "({$case})");
                $query->orderBy('orderById');
            } else {
                $query->where('1 = 2');
            }
            
            $query->EXT('featureId', 'batch_Features', 'externalName=id,remoteKey=itemId');
        }
        
        $featureCaption = 'Срок на годност';
    }
    
    
    /**
     * Подрежда подадените партиди
     *
     * @param array         $batches - наличните партиди
     *                               ['batch_name'] => ['quantity']
     * @param datetime|NULL $date
     *                               return void
     */
    public function orderBatchesInStore(&$batches, $storeId, $date = null)
    {
        $dates = array_keys($batches);
        
        if (is_array($dates)) {
            usort($dates, function ($a, $b) {
                $aString = dt::getMysqlFromMask($a, $this->rec->format);
                $bString = dt::getMysqlFromMask($b, $this->rec->format);
                
                return (strtotime($aString) < strtotime($bString)) ? -1 : 1;
            });
            
            $sorted = array();
            foreach ($dates as $date) {
                $sorted[$date] = $batches[$date];
            }
            
            $batches = $sorted;
        }
    }
    
    
    /**
     * Какви са свойствата на партидата
     *
     * @param string $value - номер на партидара
     *
     * @return array - свойства на партидата
     *               o name    - заглавие
     *               o classId - клас
     *               o value   - стойност
     */
    public function getFeatures($value)
    {
        $res = array();
        $res[] = (object) array('name' => 'Срок на годност', 'classId' => $this->getClassId(), 'value' => $value);
        
        return $res;
    }
}
