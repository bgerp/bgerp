<?php


/**
 * Драйвер за партиди от тип `Номер + Срок на годност`
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title Номер + Срок на годност
 */
class batch_definitions_StringAndDate extends batch_definitions_Varchar
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'batch_definitions_Deni';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('format', "enum(m.Y=|*11.1999,d.m.Y=|*22.11.1999, d-m-Y=|*22-11-1999, d/m/Y=|*22/11/1999, m.d.Y=|*11.22.1999, m-d-Y=|*11-22-1999, m/d/Y=|*11/22/1999, d.m.y=|*22.11.99, d-m-y=|*22-11-99, d/m/y=|*22/11/99, m.d.y=|*11.22.99, m-d-y=|*11-22-99, m/d/y=|*11/22/99)'", 'caption=Маска');
        $fieldset->FLD('length', 'int', 'caption=Дължина');
        $fieldset->FLD('delimiter', 'enum(&#x20;=Интервал,.=Точка,&#44;=Запетая,&#47;=Наклонена,&#45;=Тире)', 'caption=Разделител');
        $fieldset->FLD('prefix', 'varchar(3)', 'caption=Префикс');
        $fieldset->FLD('autoValue', 'enum(yes=Автоматично,no=Без)', 'caption=Генериране');
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
        $batch = null;
        
        // Ако ще се генерира автоматична стойност
        if ($this->rec->autoValue == 'yes') {
            $time = cat_Products::getParams($this->rec->productId, 'expiryTime');
            if (empty($time)) {
                $time = $this->rec->time;
            }
            
            $date = dt::today();
            if (isset($time)) {
                $date = dt::addSecs($time, $date);
                $date = dt::verbal2mysql($date, false);
            }
            
            // Прави се опит за получаване на следващия свободен номер
            $batch = $this->getNextBatch($date);
        }
        
        return $batch;
    }
    
    
    /**
     * Генерира следващия пореден номер от партидата
     *
     * @param datetime $expiryDate - срок на годност
     *
     * @return string|NULL - генерирания номер според типа на партидата
     */
    private function getNextBatch($expiryDate)
    {
        $existingBatches = batch_BatchesInDocuments::getBatchByType($this->getClassId(), 'batch', $this->rec->id);
        $existingBatches = arr::extractValuesFromArray($existingBatches, 'batch');
        $delimiter = html_entity_decode($this->rec->delimiter, ENT_COMPAT, 'UTF-8');
        
        $normalized = array();
        foreach ($existingBatches as $batch) {
            $exploded = explode($delimiter, $batch);
            if(countR($exploded) == 2){
                $normalized[] = str_replace($this->rec->prefix, '', $exploded[0]);
            }
        }
        
        rsort($normalized);
        $max = $normalized[0];
       
        $nextNumber = isset($max) ? str::increment($max) : str_pad(1, $this->rec->length, '0', STR_PAD_LEFT);
        $nextNumber = "{$this->rec->prefix}{$nextNumber}";
        if (!empty($this->rec->length) && mb_strlen($nextNumber) > $this->rec->length) {
            
            return;
        }
        
        $date = dt::mysql2verbal($expiryDate, $this->rec->format);
        $nextNumber = "{$nextNumber}{$delimiter}{$date}";
        
        return $nextNumber;
    }
    
    
    /**
     * Проверява дали стойността е невалидна
     *
     * @param string $value    - стойноста, която ще проверяваме
     * @param float  $quantity - количеството
     * @param string &$msg     -текста на грешката ако има
     *
     * @return bool - валиден ли е кода на партидата според дефиницията или не
     */
    public function isValid($value, $quantity, &$msg)
    {
        // Ако артикула вече има партида за този артикул с тази стойност, се приема че е валидна
        if (batch_Items::fetchField(array("#productId = {$this->rec->productId} AND #batch = '[#1#]'", $value))) {
            
            return true;
        }
        
        $delimiter = html_entity_decode($this->rec->delimiter, ENT_COMPAT, 'UTF-8');
        
        if (strpos($value, $delimiter) === false) {
            $msg = 'В партидата трябва да има|* "' . $delimiter . '"';
            
            return false;
        }
        
        list($string, $date) = explode($delimiter, $value, 2);
        if (isset($this->rec->length)) {
            if (mb_strlen($string) > $this->rec->length) {
                $msg = "|*{$string} |е над допустимата дължина от|* <b>{$this->rec->length}</b>";
                
                return false;
            }
        }
        
        if (!dt::checkByMask($date, $this->rec->format)) {
            $f = dt::mysql2verbal(dt::today(), $this->rec->format);
            $msg = "|Срока на годност трябва да е във формата|* <b>{$f}</b>";
            
            return false;
        }
        
        if (isset($this->rec->prefix)) {
            $substring = substr($string, 0, mb_strlen($this->rec->prefix));
            if ($substring != $this->rec->prefix) {
                $msg = "|Партидата трябва да започва с|* <b>{$this->rec->prefix}</b>";
                
                return false;
            }
        }
        
        return parent::isValid($value, $quantity, $msg);
    }
    
    
    /**
     * Проверява дали стойността е невалидна
     *
     * @return core_Type - инстанция на тип
     */
    public function getBatchClassType()
    {
        $Type = core_Type::getByName('varchar');
        
        return $Type;
    }
    
    
    /**
     * Какви са свойствата на партидата
     *
     * @param string $value - номер на партидара
     *
     * @return array - свойства на партидата
     *               масив с ключ ид на партидна дефиниция и стойност свойството
     */
    public function getFeatures($value)
    {
        list($string, $date) = explode('|', $value);
        
        $varcharClassId = batch_definitions_Varchar::getClassId();
        $dateClassId = batch_definitions_ExpirationDate::getClassId();
        $date = dt::getMysqlFromMask($date, $this->rec->format);
        
        $res = array();
        $res[] = (object) array('name' => 'Номер', 'classId' => $varcharClassId, 'value' => $string);
        $res[] = (object) array('name' => 'Срок на годност', 'classId' => $dateClassId, 'value' => $date);
        
        return $res;
    }
    
    
    /**
     * Кой може да избере драйвера
     */
    public function toVerbal($value)
    {
        $delimiter = html_entity_decode($this->rec->delimiter, ENT_COMPAT, 'UTF-8');
        list($string, $date) = explode($delimiter, $value);
        
        $date = batch_definitions_ExpirationDate::displayExpiryDate($date, $this->rec->format, $this->rec->time);
        $string = core_Type::getByName('varchar')->toVerbal($string);
        
        $value = "{$string}{$delimiter}{$date}";
        
        return $value;
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
                list($aLeft, $aDate) = explode('|', $a);
                list($bLeft, $bDate) = explode('|', $b);
                
                $aString = dt::getMysqlFromMask($aDate, $this->rec->format);
                $bString = dt::getMysqlFromMask($bDate, $this->rec->format);
                $aTime = strtotime($aString);
                $bTime = strtotime($bString);
                
                if ($aTime == $bTime) {
                    $cmp = (strcasecmp($aLeft, $bLeft) < 0) ? -1 : 1;
                    
                    return $cmp;
                }
                
                return ($aTime < $bTime) ? -1 : 1;
            });
            
            $sorted = array();
            foreach ($dates as $date) {
                $sorted[$date] = $batches[$date];
            }
            
            $batches = $sorted;
        }
    }
}
