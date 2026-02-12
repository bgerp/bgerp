<?php


/**
 * Клас  'type_Varchar' - Тип за символни последователности (стринг)
 *
 *
 * @category  ef
 * @package   type
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class type_Varchar extends core_Type
{
    /**
     * MySQL тип на полето в базата данни
     */
    public $dbFieldType = 'varchar';
    
    
    /**
     * Дължина на полето в mySql таблица
     */
    public $dbFieldLen = 255;
    
    
    /**
     * Рендира HTML инпут поле
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        // Сигнализиране на потребителя, ако въведе по-дълъг текст от допустимото
        $size = $this->params['size'] ?? $this->params[0] ?? $this->dbFieldLen ?? null;
        $attr['onblur'] ??= '';
        if (empty($this->params['noTrim'])) {
            $attr['onblur'] .= 'this.value = this.value.trim();';
        }
        
        if ($size > 0) {
            $attr['onkeyup'] ??= '';
            $attr['onblur'] .= "colorByLen(this, {$size}, true); if(this.value.length > {$size}) alert('" .
                 tr('Въведената стойност е дълга') . " ' + this.value.length + ' " . tr('символа, което е над допустимите') . " ${size} " . tr('символа') . "');";
            $attr['onkeyup'] .= "colorByLen(this, {$size});";
        }
        
        if (!empty($this->inputType)) {
            $attr['type'] = $this->inputType;
        }
        
        if (!empty($this->params['autocomplete'])) {
            $attr['autocomplete'] = $this->params['autocomplete'];
        }

        if (!empty($this->params['readonly'])) {
            $attr['readonly'] = 'readonly';
        }
        
        $tpl = $this->createInput($name, $value, $attr);
        
        return $tpl;
    }
    
    
    /**
     * Този метод трябва да конвертира от вербално към вътрешно
     * представяне дадената стойност
     *
     *
     */
    public function fromVerbal_($value)
    {
        //Ако няма параметър noTrim, тогава тримваме стойността
        if (empty($this->params['noTrim'])) {
            
            //Тримвано стойността
            $value = trim($value);
        }
        
        if (($this->params['utf8mb4'] ?? null) == 'utf8') {
            $value = i18n_Charset::utf8mb4ToUtf8($value);
        }
        
        $value = parent::fromVerbal_($value);
        
        // За някои случаи вместо празен стринг е по-добре да получаваме NULL
        if (!empty($this->params['nullIfEmpty']) || !empty($this->nullIfEmpty)) {
           
            if (!strlen($value)) {
                $value = null; 
            }
        }
 
        if(strlen($value) > 4) {
            // Проверка за опити за хакване
            core_HackDetector::check($value, $this->params['hackTolerance'] ?? null);
        }

        return $value;
    }
    
    
    /**
     * Този метод трябва да конвертира от вътрешно към вербално
     * представяне дадената стойност
     */
    public function toVerbal_($value)
    {
        $res = parent::toVerbal_($value);
        
        if (Mode::is('htmlEntity', 'none')) {
            $res = html_entity_decode($res, ENT_QUOTES, 'UTF-8');
        }
        
        return $res;
    }
}
