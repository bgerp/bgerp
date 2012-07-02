<?php



/**
 * Клас 'sens_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'Core'
 *
 *
 * @category  bgerp
 * @package   sens
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class sens_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        
        
        $this->TAB('sens_Sensors', 'Сензори', 'sens, admin');
        $this->TAB('sens_IndicationsLog', 'Показания', 'sens, admin');
        $this->TAB('sens_MsgLog', 'Съобщения', 'sens, admin');
        $this->TAB('sens_Params', 'Параметри','sens, admin');
        $this->TAB('sens_Overviews', 'Мениджър изгледи');
        
        $this->title = 'Наблюдение';
    }
}