<?php

/**
 * Локации
 */
class crm_Locations extends core_Manager {
    
    /**
     * Интерфайси, поддържани от този мениджър
     */
    // var $interfaces = 'acc_RegisterIntf';

    /**
     *  @todo Чака за документация...
     */
    var $title = "Локации";
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, crm_Wrapper';
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = "id, contragent, title, typeId, countryId, city, pCode, address, comment, gln";
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {   
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'caption=Собственик->Клас');
        $this->FLD('contragentId', 'int', 'caption=Собственик->Id');
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
 }