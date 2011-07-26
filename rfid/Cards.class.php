<?php

/**
 *  class Cards
 *
 *
 */

class rfid_Cards extends Core_Manager {
    /**
     *  @todo Чака за документация...
     */
    var $menuPage = 'Контрол на работното време';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Карти';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created,plg_RowTools, rfid_Wrapper';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        
        $this->FLD('userId', 'key(mvc=contacts_Contacts,select=name)', 'caption=Име');
        $this->FLD('rfid_55d', 'varchar(16)','caption=Rfid номер->WEG32 08h>55d<br>Завод ВТ');
        $this->FLD('rfid_10d', 'varchar(16)','caption=Rfid номер->1:1 08h>10d<br>Завод Леденик');
        
        $this->setDbUnique('rfid_55d');
        $this->setDbUnique('rfid_10d');
    }
    
    
    /**
     *  Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_BeforeSave($mvc, &$id, $rec)
    {
        if (!empty($rec->rfid_55d)) {
            $rec->rfid_10d = $this->convert55dTo10d($rec->rfid_55d);
            $rec->rfid_55d = (int) $rec->rfid_55d;
        } elseif (!empty($rec->rfid_10d)) {
            $rec->rfid_55d = $this->convert10dTo55d($rec->rfid_10d);
            $rec->rfid_10d = (int) $rec->rfid_10d;
        }
    }
    
    
    /**
     *
     * Enter description here ...
     * @param int $num
     */
    function convert55dTo10d($num)
    {
        $numLast5d = sprintf("%04s",dechex(substr($num,-5)));
        $numFirst5d = dechex(substr($num,0,strlen($num)-5));
        
        return hexdec($numFirst5d . $numLast5d);
    }
    
    
    /**
     *
     * Enter description here ...
     * @param int $num
     */
    function convert10dTo55d($num)
    {
        $numHex = dechex($num);
        $numLast5d = sprintf("%05d",hexdec(substr($numHex,-4)));
        $numFirst5d = hexdec(substr($numHex,0,strlen($numHex)-4));
        
        return ($numFirst5d . $numLast5d);
    }
}
?>