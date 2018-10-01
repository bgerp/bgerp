<?php


/**
 * Колко цифри след запетаята да се показват
 */
defIfNot('EF_HUMIDITYTYPE_DECIMALS', 0);


/**
 * Клас  'physics_HumidityType' - Тип за температура
 *
 *
 * @category  vendors
 * @package   physics
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class physics_HumidityType extends type_Percent
{
    /**
     * Инициализиране на типа
     */
    public function init($params = array())
    {
        parent::init($params);
        $this->params['decimals'] = EF_HUMIDITYTYPE_DECIMALS;
    }
    
    
    /**
     * Преобразуване от вербална стойност, към вътрешно представяне за процент (0 - 1)
     */
    public function fromVerbal($value)
    {
        $value = parent::fromVerbal($value);
        
        if (($value < 0) || ($value > 1)) {
            $this->error = 'Стойността на полето трябва да е между 0 и 100%';
            
            return false;
        }
        
        return $value;
    }
}
