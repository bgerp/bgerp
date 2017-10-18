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
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Percent extends type_Double {
    

    /**
     * Клас за <td> елемент, който показва данни от този тип
     */
    var $tdClass = 'rightCol';
    

    /**
     * Инициализиране на типа
     */
    function init($params = array())
    {
        parent::init($params);
        setIfNot($this->params['decimals'], EF_PERCENT_DECIMALS);
    }
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност за проценти (0 - 100%)
     */
    function toVerbal($value)
    {
        if(!strlen($value)) return NULL;
        
        $value = $value * 100;
        
        $res = parent::toVerbal($value);
        
        if (Mode::is('text', 'plain')) {
            $res .= ' ';
        } else {
            $res .= '&nbsp;';
        }
        
        $res .= '%';
        
        return $res;
    }
    
    
    /**
     * Преобразуване от вербална стойност, към вътрешно представяне за процент (0 - 1)
     */
    function fromVerbal($value)
    {
        if(!strlen($value)) {

            return NULL;
        }
        $value = str_replace('%', '', $value);
        $value = parent::fromVerbal($value);
        $value = $value / 100;
        
        return $value;
    }
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност за проценти при рендиране (0 - 100%)
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        if (!($this->error) && isset($value)) {
            $value = (100 * $value) . ' %';
        }

        setIfNot($attr['placeholder'], '%');

        // Възможност за задаване на предложения
        if (!$this->suggestions) {
        	if($this->params['suggestions']) {
        		$this->suggestions = array('' => '') + arr::make(explode('|', $this->params['suggestions']), TRUE);
        	}
        }
        
        return parent::renderInput_($name, $value, $attr);
    }
    
}