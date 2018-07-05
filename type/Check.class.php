<?php



/**
 * Клас  'type_Check' - Тип за избрана/неизбрана стойност
 *
 *
 * @category  bgerp
 * @package   type
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Check extends type_Enum
{
    
    
    /**
     * Параметър по подразбиране
     */
    public function init($params = array())
    {
        $yesCaption = isset($params['params']['label']) ? $params['params']['label'] : 'Да';
        $this->options = array('no' => 'Не е направен избор', 'yes' => $yesCaption);
        if (!empty($params['params']['errorIfNotChecked'])) {
            $this->params['errorIfNotChecked'] = $params['params']['errorIfNotChecked'];
        }
        parent::init($this->params);
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        $caption = tr($this->options['yes']);
        $attr['class'] .= ' checkbox';
        ht::setUniqId($attr);
        
        $errorClass = isset($attr['errorClass']) ? "errorclass=' inputError'": '';
        $tpl = new core_ET("<input type='checkbox' [#DATA_ATTR#] name='{$name}' {$errorClass} value='yes' class='{$attr['class']}' id='{$attr['id']}'" . ($value == 'yes' ? ' checked ' : '') . "> <label id='label_{$attr['id']}'>{$caption}</label>");
        
        return $tpl;
    }
    
    
    /**
     * Конвертира от вербална стойност
     */
    public function fromVerbal($value)
    {
        $value = ($value == 'yes') ? 'yes' : 'no';
        
        if (isset($this->params['mandatory']) && $value != 'yes') {
            $error = ($this->params['errorIfNotChecked']) ? $this->params['errorIfNotChecked'] : 'Стойността трябва да е избрана|*!';
            $this->error = $error;

            return false;
        }
        
        return $value;
    }
}
