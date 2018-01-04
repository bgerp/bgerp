<?php



/**
 * Клас 'crm_Wrapper'
 *
 * Опаковка на визитника
 *
 *
 * @category  bgerp
 * @package   crm
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class crm_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на опаковката с табове
     */
    function description()
    {
        $this->TAB('crm_Companies', 'Фирми', 'powerUser');
        $this->TAB('crm_Persons', 'Лица', 'powerUser');
        $this->TAB('crm_Groups', 'Групи', 'powerUser');
        $this->TAB('crm_Locations', 'Локации', 'powerUser');
        $this->TAB('crm_Profiles', 'Профили', 'powerUser');
        $this->TAB('crm_ext_ContragentInfo', 'Информация', 'debug');
        
        $this->title = 'Контакти';
    }
}