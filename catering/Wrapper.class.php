<?php



/**
 * Клас 'catering_Wrapper'
 *
 *
 * @category  bgerp
 * @package   catering
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class catering_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        
        
        $this->TAB('catering_Menu', 'Меню', 'admin, catering');
        $this->TAB('catering_Companies', 'Фирми', 'catering,admin');
        $this->TAB('catering_EmployeesList', 'Столуващи', 'catering, admin');
        $this->TAB('catering_Requests', 'Заявки', 'catering, admin, user');
        $this->TAB('catering_Orders', 'Поръчки', 'catering, admin');
        
        $this->title = 'Кетаринг';
       
    }
}