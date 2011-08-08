<?php

/**
 * Локации
 */
class common_Locations extends core_Manager {
    
    /**
     * Интерфайси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf';

    /**
     *  @todo Чака за документация...
     */
    var $title = "Локации";
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, CrmPersons=crm_Persons, common_Wrapper, acc_RegisterPlg';
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = "id, contactId, title, typeId, countryId, city, pCode, address, comment, gln";
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('contactId', 'key(mvc=crm_Persons, select=name)', 'caption=Собственик');
        $this->FLD('title', 'varchar(255)', 'caption=Наименование');
        $this->FLD('typeId', 'key(mvc=common_LocationTypes, select=name)', 'caption=Тип');
        $this->FLD('countryId', 'key(mvc=drdata_Countries, select=commonName, allowEmpty)', 'caption=Юрисдикция');
        $this->FLD('city', 'varchar(64)', 'caption=Град');
        $this->FLD('pCode', 'varchar(16)', 'caption=П. код');
        $this->FLD('address', 'varchar(255)', 'caption=Адрес');
        $this->FLD('comment', 'text', 'caption=Коментари');
        $this->FLD('gln', 'gs1_TypeEan13', 'caption=GLN код');
        $this->FLD('gpsCoords', 'location_Type', 'caption=Координати');
    }
    
    
    /**
     * Връща заглавието на локацията
     *
     * Част от интерфейса: intf_Register
     */
    function getAccItemRec($rec)
    {
        return (object) array( 'title' => $rec->title,
        );
    }
    
    
    /**
     * Признаци, по които могат да се групират продукти
     *
     * @see intf_Register::getGroupTypes()
     */
    function getGroupTypes()
    {
        return array(
            'group' => 'Type',
        );
    }
    
    
    /**
     * Възможни стойности на зададен признак за групиране.
     *
     * @see intf_Register::getGroups()
     */
    function getGroups($groupType)
    {
        $method = 'get' . ucfirst($groupType) . 'Groups';
        
        if (method_exists($this, $method)) {
            return $this->{$method}();
        }
    }
    
    
    /**
     * Връща ид на продукти, групирани по стойност на зададения критерий
     *
     * @see intf_Register::getGroupObjects()
     */
    function getGroupObjects($groupType, $groupValue = NULL)
    {
        $method = 'get' . ucfirst($groupType) . 'GroupObjects';
        
        if (method_exists($this, $method)) {
            return $this->{$method}($groupValue);
        }
    }
}