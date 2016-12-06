<?php


/**
 * Клас  'ical_Parser' - Тип за време
 *
 *
 * @category  ef
 * @package   type
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class ical_Parser extends core_Mvc {
    
    /**
     * Парсира и извлича събитията от текста в iCalendar (.ics) формат
     */
    public static function getEvents($ics, $dtFormat = 'smartTime')
    {
        require_once(__DIR__ . '/parser/ICal.php');
        require_once(__DIR__ . '/parser/EventObject.php');

        $ical = new ICal\ICal();

        $ical->initString($ics);
        
        $events = $ical->events();

 
        foreach($events as $id => $e) { 

            if($e->dtstart) {
                $e->dtstart = dt::timestamp2mysql(strtotime($e->dtstart));
                $e->dtstartVrb = dt::mysql2verbal($e->dtstart, $dtFormat);
            }
            
            if($e->dtend) {
                $e->dtend  = dt::timestamp2mysql(strtotime($e->dtend));
                $e->dtendVrb = dt::mysql2verbal($e->dtend, $dtFormat);
            }
            
            if($e->dtstamp) {
                $e->dtstamp  = dt::timestamp2mysql(strtotime($e->dtstamp));
                $e->dtstampVrb = dt::mysql2verbal($e->dtstamp, $dtFormat);
            }
            
            if($e->created) {
                $e->created  = dt::timestamp2mysql(strtotime($e->created));
                $e->createdVrb = dt::mysql2verbal($e->created, $dtFormat);
            }
            
            if($e->lastmodified) {
                $e->lastmodified  = dt::timestamp2mysql(strtotime($e->lastmodified));
                $e->lastmodifiedVrb = dt::mysql2verbal($e->lastmodified, $dtFormat);
            }

            if($e->duration) {
                $d = new \DateInterval($e->duration);
                if($d->y) {
                    $t .= $d->y . ' years ';
                }
                if($d->m) {
                    $t .= $d->m . ' monts ';
                }
                if($d->d) {
                    $t .= $d->d . ' days ';
                }
                if($d->h) {
                    $t .= $d->h . ' hours ';
                }

                if($d->i) {
                    $t .= $d->i . ' minutes ';
                }
                
                if($d->s) {
                    $t .= $d->i . ' secs ';
                }
                
                $tt = cls::get('type_Time');

                $e->duration = $tt->fromVerbal_($t);

                $e->durationVrb = $tt->toVerbal_($e->duration);
            }
            
            if(!$e->summary) {
                $e->summary = tr('Събитие') . ' ' . ($id+1);
            }
            $e->summary = str_replace(array('\\n\\r', '\\r\\n', '\\n', '\\r'), "\n", $e->summary);
            $e->summaryVrb = str_replace("\n", "\n<br>", type_Varchar::escape($e->summary));

            $e->location = str_replace(array('\\n\\r', '\\r\\n', '\\n', '\\r'), "\n", $e->location);
            $e->locationVrb = str_replace("\n", "\n<br>", type_Varchar::escape($e->location));
 
            $e->description = str_replace(array('\\n\\r', '\\r\\n', '\\n', '\\r'), "\n", $e->description);
            $e->descriptionVrb = str_replace("\n", "\n<br>", type_Varchar::escape($e->description));

            $e->organizer = str_replace(array('\\n\\r', '\\r\\n', '\\n', '\\r'), "\n", $e->organizer);
            $e->organizerVrb = self::getPersons($e->organizer_array);

            $e->attendee = str_replace(array('\\n\\r', '\\r\\n', '\\n', '\\r'), "\n", $e->attendee);
            $e->attendeeVrb = self::getPersons($e->attendee_array);

            switch($e->status) {
                case "TENTATIVE":
                    $e->statusVrb = tr("ПРЕДВАРИТЕЛНО||" . $e->status);
                    break;
                case "CONFIRMED":
                    $e->statusVrb = tr("ПОТВЪРДЕНО||" . $e->status);
                    break;
                case "NEEDS-ACTION":
                    $e->statusVrb = tr("НЕОБХОДИМО ДЕЙСТВИЕ||" . $e->status);
                    break;
                case "COMPLETED":
                    $e->statusVrb = tr("ИЗПЪЛЕНО||" . $e->status);
                    break;
                case "IN-PROCESS":
                    $e->statusVrb = tr("В ПРОЦЕС||" . $e->status);
                    break;
                case "DRAFT":
                    $e->statusVrb = tr("ЧЕРНОВА||" . $e->status);
                    break;
                case "FINAL":
                    $e->statusVrb = tr("ФИНАЛНО||" . $e->status);
                    break;
                case "CANCELLED":
                    $e->statusVrb = tr("ОТКАЗАНО||" . $e->status);
                    break;
            }

            if(isset($e->statusVrb)) { 
                $e->statusVrb = str::mbUcfirst(mb_strtolower($e->statusVrb));
            }
            
            if($e->transp == 'TRANSPARENT') {
                $e->statusVrb .= ($e->statusVrb ? ', ' : '') . tr('Не ангажира време');
            }
        }

        return $events;
    }
    

    /**
     * Извлича информация за лице
     */
    private static function getPersons($p) 
    { 
        $res = '';
 
        if(is_array($p)) {
            $tE = cls::get('type_Email');
            foreach($p as $el) {
                if(is_array($el) && $el['CN']) {
                    $res .= ($res ? "\n<br>" : '') . $el['CN'];
                } elseif(is_string($el) && stripos($el, 'mailto:') !== FALSE) {
                    $eml = type_Email::extractEmails($el);
                    if(is_array($eml)) {
                        foreach($eml as $email) {
                            if($res) {
                                $res .= ' (' . $tE->toVerbal($email) . ')';
                            } else {
                                $res .= $tE->toVerbal($email);
                            }
                        }
                    }
                }
            }
        }

        return $res;
    }
    

    /**
     * Рендира в HTML събитията в подадения iCalendar стринг
     */
    public static function renderEvents($ics)
    {
        $events = self::getEvents($ics);
 
        $res = new ET();

        if(is_array($events)) {
            foreach($events as $e) {  
                $tpl = new ET (tr('|*' . getFileContent('ical/tpl/Event.shtml')));
                $tpl->placeObject($e);
                $tpl->removeBlocks();
                $res->append($tpl);
            }
        }

        return $res;
    }

 


    function act_Test()
    {
        $str = file_get_contents("c:/temp/Denmark-Holidays.ics");
 
 
        $events = self::renderEvents($str);

        return $events;
    }
}