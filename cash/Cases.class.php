<?php

/**
 * Каса сметки
 */
class cash_Cases extends core_Manager {

    /**
     * Интерфайси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf, cash_CaseAccRegIntf';

    /**
     *  @todo Чака за документация...
     */
    var $title = 'Фирмени каси';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, tools=Пулт, title, location, cashier';
        
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, acc_plg_Registry, cash_Wrapper, plg_Selected';
    

    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';    
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title',    'varchar(255)',                            'caption=Наименование');
        $this->FLD('location', 'key(mvc=common_Locations, select=title)', 'caption=Локация');
        $this->FLD('cashier',  'key(mvc=core_User)',       'caption=Касиер');
    }
    
    
    /*******************************************************************************************
     * 
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_ContragentAccRegIntf
     * 
     ******************************************************************************************/
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = null;
        
        if ($rec = $self->fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->id,
                'title' => $rec->name,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    /**
     * @see crm_ContragentAccRegIntf::getLinkToObj
     * @param int $objectId
     */
    static function getLinkToObj($objectId)
    {
        $self = cls::get(__CLASS__);
        
        if ($rec  = $self->fetch($objectId)) {
            $result = ht::createLink($rec->name, array($self, 'Single', $objectId)); 
        } else {
            $result = '<i>неизвестно</i>';
        }
        
        return $result;
    }
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
        // @todo!
    }
    
    /**
     * КРАЙ НА интерфейса @see acc_RegisterIntf
     */    
    
}