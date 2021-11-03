<?php


/**
 * Клас  'cat_type_Uom'
 * Тип за мерни единици. Представлява на един ред числов инпут и до него комбобокс
 * с опции, производните на дадена мярка. Задължително е да има дефиниран параметър на полето
 * 'unit' със стойност систем ид-то на някоя мярка (@see cat_UoM)
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class cat_type_Uom extends type_Varchar
{
    /**
     * MySQL тип на полето в базата данни
     */
    public $dbFieldType = 'double';
    
    
    /**
     * Дължина на полето в mySql таблица
     */
    public $dbFieldLen = '11';
    
    
    /**
     * Стойност по подразбиране
     */
    public $defaultValue = 0;
    
    
    /**
     * Клас за <td> елемент, който показва данни от този тип
     */
    public $tdClass = 'rightCol';
    
    
    /**
     * @type_Double
     */
    protected $double;
    
    
    /**
     * Инициализиране на обекта
     */
    public function init($params = array())
    {
        parent::init($params);
        
        // Инстанциране на type_Double
        $this->double = cls::get('type_Double', $params);
    }
    
    
    /**
     * Конвертира от вербална стойност
     */
    public function fromVerbal_($value)
    {
        // Ако няма стойност
        if (!$value) {
            
            return;
        }
        
        // Ако стойността е двоично число
        if (is_numeric($value)) {
            $value = $this->double->fromVerbal($value);
            
            // Ако има проблем при обръщането сетва се грешка
            if ($value === false) {
                $this->error = 'Не е въведено валидно число';
                
                return false;
            }
            
            return $value;
        }
        
        if (!strlen($value['lP'])) {
            
            return;
        }
        
        // Ако стойността е масив
        if (is_array($value)) {
            $numPart = trim($value['lP']);
        } else {
            
            // Ако не е масив, ако идва от Hidden поле
            $numPart = $value;
        }
        
        // Обръщане в невербален вид
        $numPart = $this->double->fromVerbal($numPart);
        
        // Ако има проблем при обръщането сетва се грешка
        if ($numPart === false) {
            $this->error = 'Не е въведено валидно число';
            
            return false;
        }
        
        // Конвертиране във основна мярка ако стойността е масив
        if (is_array($value)) {
            // Конвертиране в основна мярка на числото от избраната мярка
            $numPart = cat_UoM::convertToBaseUnit($numPart, $value['rP']);
        }
        
        // Връщане на сумата в основна мярка
        return $numPart;
    }
    
    
    /**
     * Рендиране на полето
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        // Ако има запис, конвертира се в удобен вид
        $convObject = new stdClass();
        expect($baseUnitId = cat_UoM::fetchBySysId($this->params['unit'])->id);
        
        if ($value === null || $value === '') {
            $convObject->value = '';
            $convObject->measure = $baseUnitId;
        } elseif (empty($this->error)) {
            $convObject = cat_UoM::smartConvert($value, $this->params['unit'], false, true);
        } else {
            $convObject->value = $value['lP'];
            $convObject->measure = $value['rP'];
        }
        
        // Рендиране на частта за въвеждане на числото
        $inputLeft = $this->double->renderInput($name . '[lP]', $convObject->value, $attr);
        unset($attr['size']);
        
        // Извличане на всички производни мярки
        $options = cat_UoM::getSameTypeMeasures($baseUnitId, true);
        unset($options['']);
        
        $inputRight = ' &nbsp;' . ht::createSmartSelect($options, $name . '[rP]', $convObject->measure);
        $inputRight = "<span style='vertical-align: top'>" . $inputRight . '</span>';
        
        // Добавяне на дясната част към лявата на полето
        $inputLeft->append($inputRight);
        
        // Връщане на готовото поле
        return $inputLeft;
    }
    
    
    /**
     * Форматира числото в удобна за четене форма
     */
    public function toVerbal_($value)
    {
        if (!isset($value) || !is_numeric($value)) {
            
            return;
        }
        $value = abs($value);
        
        return cat_UoM::smartConvert($value, $this->params['unit']);
    }
}
