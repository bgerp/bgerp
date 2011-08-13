<?php

/**
 * Клас 'common_Mvr'
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
class common_Mvr extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, common_Wrapper';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, city, account, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'МВР по страната';
    

    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'admin, common';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin, common';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin, common';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin, common';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('city',    'varchar', 'caption=Град, mandatory, export');
        $this->FLD('account', 'varchar', 'caption=Сметка, input=none, export');
        $this->setDbUnique('city');
    }
    
    
    /**
     * Сортиране по city
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('#city');
    }
    
}