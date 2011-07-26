<?php

/**
 * Връзки в основното меню
 */
class bgerp_Portal extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, bgerp_Wrapper';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Елементи на портала';
    
    // Права
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('column', 'enum(1,2,3,4)', 'caption=Колона, mandatory');
        $this->FLD('portalBlockSource', 'class(inerface=portalBlockSource)', 'caption=Контролер, mandatory');
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