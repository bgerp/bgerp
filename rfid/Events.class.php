<?php

/**
 *  class Events
 *
 *
 *
 *
 */

class rfid_Events extends core_Manager {
    /**
     *  @todo Чака за документация...
     */
    var $menuPage = 'Контрол на работното време';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Събития';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $refreshRowsTime = 5000;
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created,rfid_Cards,rfid_Readers,plg_RefreshRows,rfid_Wrapper,rfid_Synchronize';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        // Обща информация
        $this->FLD('cardId', 'key(mvc=rfid_Cards,select=userId)','caption=Картодържател');
        $this->FLD('readerId', 'key(mvc=rfid_Readers,select=name)','caption=Четец');
        $this->EXT('type','rfid_Readers','externalKey=readerId,caption=Тип');
        $this->EXT('rfid_55d','rfid_Cards','externalKey=cardId,caption=55d');
        $this->EXT('rfid_10d','rfid_Cards','caption=10d');
        $this->FLD('time', 'datetime','caption=Време на събитието');
        //$this->FLD('photo', 'fileman_FileType(folder=Photos,extension=jpj|jpeg|gif|bmp,maxSize=300kb)', 'column=none');
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function act_Add()
    {
        sleep(8);
        $this->cron_getEvents();
        
        return;
        
        // http://bgerp.local/rfid_Events/add/?unitId=1234567890&cardId=1249364915&timestamp=23452342123
        //$this->log(serialize($_POST));
        $cardId = Request::get('cardId');
        
        if(!$cardId) {
            return parent::act_Add();
        }
        
        $card = $this->rfid_Cards->fetch(array("#rfid = '[#1#]'", $cardId));
        $rec->cardId = $card->id;
        $reader = $this->rfid_Readers->fetch(array("#unitId = '[#1#]'", Request::get('unitId')));
        $rec->readerId = $reader->id;
        
        $time = Request::get('timestamp');
        $rec->time = substr($time, 0, 4) . "-" .
        substr($time, 4, 2) . "-" .
        substr($time, 6, 2) . " " .
        substr($time, 8, 2) . ":" .
        substr($time, 10, 2) . ":" .
        substr($time, 12, 2);
        //        echo ('<pre>');
        //        print_r($rec);
        //        die;
        if($rec->cardId && $rec->readerId) {
            $this->save($rec);
        }
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_afterPrepareData($mvc,$result,$data)
    {
        //bp($data);
        //$data->query->where("#readerId = #originalReaderId");
        $data->query->orderBy('#createdOn', 'DESC');
        $data->toolbar = NULL;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_afterRecToVerbal1($mvc, $row, $rec)
    {
        if($rec->photo) {
            $Download = Cls::get('fileman_Download');
            $link = $Download->getDownloadUrl($rec->photo);
            $row->cardId = HT::getLink($rec->cardId, $link);
        }
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function act_getEvents()
    {
        return $this->cron_getEvents();
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function cron_getEvents()
    {
        // Обикаляме всички регистрирани четци
        // и извличаме постъпилите събития в зависимост от параметрите на четеца
        $query = $this->rfid_Readers->getQuery();
        $query->show('*');
        
        while ($reader = $query->fetch()) {
            // Параметрите на четеца са - ip, type(вход или изход)
            $driver = new stdClass();
            
            switch ($reader->type) {
                case 'in': $readerType = 1;
                    break;
                case 'out': $readerType = 2;
                    break;
            }
            // Извличаме ip-то на четеца от номера му. Може би трябва да се отдели в отделен параметър
            $ip = (int) substr($reader->unitId,0,3) .
            "." .(int) substr($reader->unitId,3,3) .
            "." . (int) substr($reader->unitId,6,3) .
            "." . (int) substr($reader->unitId,9,3);
            
            $reader->params = array('ip'=>$ip,'type'=>$readerType);
            
            $driver = cls::get($reader->driver,$reader->params);
            // Извличаме всички събития за този четец от дадено време насам
            
            $rfidData = $driver->getData($reader->synchronizedDue);
            
            if (!is_array($rfidData)) $rfidData = array();
            $lastDateTime = $reader->synchronizedDue;
            
            foreach ($rfidData as $event) {
                if ($lastDateTime < $event->DATE_TIME_) $lastDateTime = $event->DATE_TIME_;
                // Първо търсим картата дали е регистрирана.
                // Взимаме впредвид типа на четеца.
                $card = $this->rfid_Cards->fetch("#rfid_{$reader->idFormat} = '{$event->RFID_CARD}'");
                
                if (is_object($card)) {
                    // Ако няма такова събитие в events/един и същ номер и време/ - записваме събитието 
                    $eventLocal = $this->fetch("#cardId={$card->id} AND #time='{$event->DATE_TIME_}'");
                    
                    if (!is_object($eventLocal)) {
                        // Записваме събитието
                        unset($rec);
                        $rec->cardId=$card->id;
                        $rec->readerId=$reader->id;
                        $rec->time=$event->DATE_TIME_;
                        $this->save($rec); $i++;
                    }
                }
            }
            // Обновяваме рийдъра с времето на последния запис
            $reader->synchronizedDue = $lastDateTime;
            $this->rfid_Readers->save($reader);
        }
        
        $this->log("Info: {$this->className} Последното събитие е от: {$event->DATE_TIME_} -- Добавени записи: $i");
    }
}
?>