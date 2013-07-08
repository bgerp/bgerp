<?php



/**
 * Транспорт - опаковка
 *
 *
 * @category  bgerp
 * @package   transport
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class transport_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
       
        
        $this->TAB('transport_Requests', 'Заявка', 'ceo,transport');
        $this->TAB('transport_Shipment', 'Пратки', 'ceo,transport');
        $this->TAB('transport_Claims', 'Претенции за рекламация', 'ceo,transport');
        $this->TAB('transport_Registers', 'Регистър', 'ceo,transport');
        
       
        $this->title = 'Транспорт « Логистика';
        Mode::set('menuPage', 'Логистика:Транспорт');
    }
}