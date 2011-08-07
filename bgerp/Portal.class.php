<?php

/**
 * Портален изглед на състоянието на системата
 *
 * Има възможност за кустумизиране за всеки потребител
 *
 * @category   Experta Framework
 * @package    bgerp
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 * @since      v 0.1
 */
class bgerp_Portal extends core_Manager
{
    /**
     *  Неща за зареждане в началото
     */
    var $loadList = 'plg_Created, plg_RowTools, bgerp_Wrapper';
    
    
    /**
     *  Заглавие на мениджъра
     */
    var $title = 'Елементи на портала';
    
    // Права
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('column', 'enum(1,2,3,4)', 'caption=Колона, mandatory');
        $this->FLD('blockSource', 'class(interface=bgerp_BlockSource)', 'caption=Контролер, mandatory');
        $this->FLD('params', 'text', 'caption=Настройки,input=none');
        $this->FLD('userId', 'key(mvc=core_Users)', 'caption=Потребител');
        $this->FLD('mobile', 'enum(no=Не,yes=Да)', 'caption=Мобилен');
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function act_Show()
    {
        requireRole('user');
        
        return "Портал ...";
    }
}