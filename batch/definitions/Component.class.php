<?php


/**
 * Базов драйвер за партиден клас 'Символ + цифри'
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title Символи + цифри
 */
class batch_definitions_Component extends batch_definitions_Proto
{
    /**
     * Име на полето за партида в документа
     *
     * @param string
     */
    public $fieldCaption = '№';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('delimiter', 'enum(&#x20;=Интервал,.=Точка,&#44;=Запетая,&#47;=Наклонена,&#45;=Тире)', 'caption=Разделител,mandatory');
        $fieldset->FLD('numberLetters', 'int(Min=0)', 'caption=Брой букви в началото');
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
        $msg = null;
        
        // Ако артикула вече има партидаза този артикул с тази стойност, се приема че е валидна
        if (batch_Items::fetchField(array("#productId = {$this->rec->productId} AND #batch = '[#1#]'", $value))) {
            
            return true;
        }
        
        $delimiter = html_entity_decode($this->rec->delimiter);
        $parts = explode($delimiter, $value);
        if (count($parts) != 2) {
            $msg = "Партидата трябва да съдържа '{$delimiter}'";
            
            return false;
        }
        
        if (!preg_match('/^\d+$/', $parts[1])) {
            $msg .= "След|* '{$delimiter}' |трябва да има само цифри|*.";
        }
        
        $begining = substr($parts[0], 0, $this->rec->numberLetters);
        if (!preg_match('/^[a-zA-ZА-Яа-я]+$/u', $begining)) {
            $msg .= "Първите|* '{$this->rec->numberLetters}' |символа трябва да са букви|*.";
        }
        
        if (strlen($parts[1]) > 16) {
            $msg .= "Максимална дължина|* 16 |цифри след|* {$delimiter}.";
        }
        
        if (!empty($msg)) {
            
            return false;
        }
        
        return parent::isValid($value, $quantity, $msg);
    }
    
    
    /**
     * Нормализира стойноста на партидата в удобен за съхранение вид
     *
     * @param string $value
     *
     * @return string $value
     */
    public function normalize($value)
    {
        $delimiter = html_entity_decode($this->rec->delimiter);
        $value = str_replace($delimiter, '|', $value);
        
        return ($value == '') ? null : $value;
    }
    
    
    /**
     * Вербализиране на записа
     */
    public function toVerbal($value)
    {
        $delimiter = html_entity_decode($this->rec->delimiter);
        $value = str_replace('|', $delimiter, $value);
        
        return $value;
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
        list($string, $number) = explode('|', $value);
        
        $res = array();
        $res[] = (object) array('name' => 'Партида', 'classId' => batch_definitions_Varchar::getClassId(), 'value' => $string);
        $res[] = (object) array('name' => 'Партида', 'classId' => batch_definitions_Varchar::getClassId(), 'value' => $number);
        
        return $res;
    }
}
