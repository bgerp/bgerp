<?php


/**
 * @todo Чака за документация...
 */
defIfNot('EF_PERCENT_DECIMALS', 2);


/**
 * Клас  'type_Percent' - Тип за проценти
 *
 *
 * @category  ef
 * @package   type
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class type_Percent extends type_Double
{
    /**
     * Клас за <td> елемент, който показва данни от този тип
     */
    public $tdClass = 'rightCol';
    
    
    /**
     * Инициализиране на типа
     */
    public function init($params = array())
    {
        parent::init($params);
        setIfNot($this->params['decimals'], EF_PERCENT_DECIMALS);
    }
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност за проценти (0 - 100%)
     */
    public function toVerbal($value)
    {
        if (!strlen($value)) {
            
            return;
        }
        
        $value = $value * 100;
        
        $res = parent::toVerbal($value);
        
        $res .= '%';
        
        return $res;
    }
    
    
    /**
     * Преобразуване от вербална стойност, към вътрешно представяне за процент (0 - 1)
     */
    public function fromVerbal($value)
    {
        if (!strlen($value)) {
            
            return;
        }
        $value = str_replace('%', '', $value);
        $value = parent::fromVerbal($value);
        $value = $value / 100;
        
        return $value;
    }
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност за проценти при рендиране (0 - 100%)
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        if (!($this->error) && isset($value)) {
            $value = (100 * $value) . ' %';
        }
        
        setIfNot($attr['placeholder'], '%');
        
        // Възможност за задаване на предложения
        if (!$this->suggestions) {
            if (!empty($this->params['suggestions'])) {
                $this->suggestions = array('' => '') + arr::make(explode('|', $this->params['suggestions']), true);
            }
        }
        
        return parent::renderInput_($name, $value, $attr);
    }
}
