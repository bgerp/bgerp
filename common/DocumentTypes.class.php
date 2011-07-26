<?php


/**
 * Клас 'common_DocumentTypes' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    common
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class common_DocumentTypes extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, plg_State,  plg_State2, common_Wrapper';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Тип');
        $this->FLD('manager', 'varchar', 'caption=Мениджър');
        $this->FLD('state', 'enum(visible=Видим,hidden=Скрит)', 'caption=Видимост');
    }
}