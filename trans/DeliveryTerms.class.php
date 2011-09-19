<?php


/**
 * Клас 'trans_DeliveryTerms' - Условия на доставка
 *
 * Набор от стандартните условия на доставка (FOB, DAP, ...)
 *
 * @category   Experta Framework
 * @package    trans
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class trans_DeliveryTerms extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, trans_Wrapper';

    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, name, description';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Условия на доставка';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name',       'varchar', 'caption=Име');
        $this->FLD('description', 'text',   'caption=Oписание');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Записи за инициализиране на таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     */
    function on_AfterSetupMvc($mvc, &$res)
    {
 
    }
}