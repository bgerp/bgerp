<?php

/**
 *  class Synchronize
 *
 */

class rfid_Synchronize extends core_Manager {
    /**
     *  @todo Чака за документация...
     */
    var $menuPage = 'Контрол на работното време';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Синхронизиране';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'rfid_Wrapper,Events=rfid_Events,core_Logs';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('eventId','key(mvc=rfid_Events)', 'caption=Събитие');
        
        $this->setDbUnique('eventId');
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function act_Sync()
    {
        return $this->cron_Sync();
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function cron_Sync()
    {
        $query = $this->Events->getQuery();
        
        //$query->where("#createdOn >= '" . dt::addDays(-65) . "' AND #createdOn ,<= '" . dt::addDays(-50) . "'");
        $query->where("#time >= '" . dt::addDays(-10) . "'");
        
        while($eventRec = $query->fetch()) {
            
            if(!$this->fetch("#eventId = {$eventRec->id}")) {
                
                // Изпращаме в стария БГЕРП
                // bp($eventRec);
                $search = array('{card}','{stamp}','{term}');
                $card = $eventRec->rfid_55d;
                $stamp= str_replace(array(':','-',' '),'', $eventRec->time);
                $term = ($eventRec->type=='in')?'1001':'1002';
                
                $replace = array($card, $stamp, $term);
                
                $urlBgerpOld = "http://server2.extrapack.com/ep_bgerp/action.php?cls=ep_cardsecurity&method=register&realm_id=ep&card={card}&stamp={stamp}&term={term}&ver=1.0&secret=145";
                
                $urlBgerpOld = str_replace($search,$replace,$urlBgerpOld);
                //                 bp($urlBgerpOld);
                $res = @file_get_contents($urlBgerpOld);
                
                if (FALSE === $res) {
                    $this->log("Error: Неработещо URL.");
                    exit;
                }
                
                if (strpos($res,'inserted')!==FALSE) {
                    $rec = new stdClass();
                    $rec->eventId = $eventRec->id;
                    $this->save($rec);
                    $cnt++;
                } else {
                    $this->log("Warning: $res");
                }
                //                 $r[] = "<li>" . $res;
            }
        }
        $this->log("Info: Успешно прехвърлени $cnt събития");
        //          bp($r);
    }
}
