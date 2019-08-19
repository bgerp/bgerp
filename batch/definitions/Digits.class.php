<?php


/**
 * Базов драйвер за вид партида 'Цифри'
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title Цифри(255)
 */
class batch_definitions_Digits extends batch_definitions_Proto
{
    /**
     * Име на полето за партида в документа
     *
     * @param string
     */
    public $fieldCaption = 'lot';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('length', 'int', 'caption=Дължина,placeholder=255');
        $fieldset->FLD('autoValue', 'enum(yes=Автоматично,no=Без)', 'caption=Генериране');
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
        $batch = ($this->rec->autoValue == 'yes') ? $this->getNextBatch() : null;
        
        return $batch;
    }
    
    
    /**
     * Генерира следващия пореден номер от партидата
     *
     * @param datetime $expiryDate - срок на годност
     *
     * @return string|NULL - генерирания номер според типа на партидата
     */
    private function getNextBatch()
    {
        $existingBatches = batch_BatchesInDocuments::getBatchByType($this->getClassId(), 'batch');
        $existingBatches = arr::extractValuesFromArray($existingBatches, 'batch');
        rsort($existingBatches);
        
        $nextNumber = isset($existingBatches[0]) ? str::increment($existingBatches[0]) : 1;
        if (empty($this->rec->length) || strlen($nextNumber) <= $this->rec->length) {
            
            return $nextNumber;
        }
    }
    
    
    /**
     * Проверява дали стойността е невалидна
     *
     * @param string $value    - стойноста, която ще проверяваме
     * @param float  $quantity - количеството
     * @param string &$msg     - текста на грешката ако има
     *
     * @return bool - валиден ли е кода на партидата според дефиницията или не
     */
    public function isValid($value, $quantity, &$msg)
    {
        if (!ctype_digit($value)) {
            $msg = 'Не всички символи са цифри';
            
            return false;
        }
        
        if (strlen($value) > $this->rec->length) {
            $msg = "Над допустимите|* <b>{$this->rec->length}</b> |цифри|*";
            
            return false;
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
        $string = !isset($this->rec->length) ? 'varchar' : "varchar({$this->rec->length})";
        
        $Type = core_Type::getByName($string);
        
        return $Type;
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
        $res[] = (object) array('name' => 'Партида', 'classId' => $this->getClassId(), 'value' => $value);
        
        return $res;
    }
}
