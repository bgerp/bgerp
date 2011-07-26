<?php

/**
 *  class Readers
 *
 */

class rfid_Readers extends core_Manager {
    /**
     *  @todo Чака за документация...
     */
    var $menuPage = 'Контрол на работното време';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Четци';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created,rfid_Wrapper,plg_RowTools';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name','varchar','caption=Име,mandatory');
        $this->FLD('unitId','varchar','caption=Хардуерен номер,oldFieldName=number,mandatory');
        $this->FLD('location','key(mvc=common_Locations,select=title)','caption=Местоположение');
        $this->FLD('type','enum(in=Вход,out=Изход)','caption=Тип');
        $this->FLD('idFormat','enum(10d=1 към 1,55d=WEG32)','caption=Видимо число');
        $this->FLD('driver', 'class(interface=intf_IpRfid)', 'caption=Драйвер,mandatory');
        $this->FLD('synchronizedDue', 'datetime', 'caption=Синхронизиран до:');
        
        //      Формата на серийният номер на картата се изчислява по следните начини и зависи от четеца
        
        //         Internal Number: Every single EM format transponder (card) has one unique Internal ID number which is a 10 digits of hexadecimal number (10H).  The Internal Number is divided into three parts:            
        //
        //        Version Code [V]: H9       Customer Code [C]: H8        ID Code [ID]: H7 ~ H0 
        //
        //        External Number: The number printed on the surface of transponder (card) is External Number. External Number is converted from Internal Number. The following are the most popular External Number formats. 
        //
        //        10H>13D: Convert [V]+[C]+[ID] to 13 digits of decimal number.
        //        08H>10D: Convert [ID] to 10 digits of decimal number.
        //        08H>55D: First divide [ID] into two parts (4H+4H), then convert each part to 5 digits of decimal number. 
        //               *Or named WEG32.
        //        06H>08D: Convert lowest 6 digits of [ID] to 8 digits of decimal number.
        //        2.4H>3.5D(A): Convert [V]+[C] to 3 digits decimal number. 
        //                      Convert lowest 4 digits of [ID] to 5 digits of decimal number.
        //        2.4H>3.5D(B): Convert highest 2 digits of [ID] to 3 digits decimal number. 
        //                      Convert lowest 4 digits of [ID] to 5 digits of decimal number.
        //        2.4H>3.5D(C): Convert H5 and H4 of [ID] to 3 digits decimal number. 
        //                      Convert lowest 4 digits of [ID] to 5 digits of decimal number. 
        //                      *Or named WEG24.
        //
        //        Example: Supposed the Internal Number is 01013EB28D (10H)
        //              then    10H>13D: 0004315853453
        //                    08H>10D: 0020886157
        //                    08H>55D: 00318,45709
        //                    06H>08D: 04108941
        //                    2.4H>3.5D(A): 001,45709
        //                    2.4H>3.5D(B): 001,45709
        //                    2.4H>3.5D(C): 062,45709
        
        $this->setDbUnique('unitId');
    }
    
    
    /**
     *  Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->synchronizedDue = Dt::mysql2Verbal($rec->synchronizedDue,"d-m-Y H:i:s");
    }
}
