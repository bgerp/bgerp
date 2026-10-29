<?php


/**
 * Преглед на събития от iCalendar
 *
 *
 * @category  ef
 * @package   ical
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class ical_Parser extends core_Mvc
{
    /**
     * Парсира и извлича събитията от текста в iCalendar (.ics) формат
     */
    public static function getEvents($ics, $dtFormat = 'smartTime')
    {
        $modern = ical_Setup::get('VERSION') === '3.6.0';
        $path = $modern ? '/3.6.0/src/ICal/' : '/parser/';
        require_once(__DIR__ . $path . 'ICal.php');
        require_once(__DIR__ . $path . ($modern ? 'Event.php' : 'EventObject.php'));
        
        $ical = new ICal\ICal();
        
        $ical->initString($ics ?? '');
        
        $events = $ical->events();


        foreach ($events as $id => $e) {
            if ($modern) {
                // Вербалните полета са на bgERP, а не динамични свойства на библиотеката.
                $e = (object) (get_object_vars($e) + $e->additionalProperties);
                unset($e->additionalProperties);
                $e->lastmodified = $e->last_modified ?? null;
                foreach (array('dtstart', 'dtend') as $field) {
                    $timestamp = $e->{$field . '_array'}[2] ?? null;
                    // Целодневните дати нямат часова зона.
                    if ($timestamp !== null && strlen($e->{$field} ?? '') !== 8) {
                        $e->{$field} = date('Y-m-d H:i:s', $timestamp);
                    }
                }
                $events[$id] = $e;
            }

            if (!empty($e->dtstart)) {
                $e->dtstart = dt::timestamp2mysql(strtotime($e->dtstart));
                $e->dtstartVrb = dt::mysql2verbal($e->dtstart, $dtFormat);
            }
            
            if (!empty($e->dtend)) {
                $e->dtend = dt::timestamp2mysql(strtotime($e->dtend));
                $e->dtendVrb = dt::mysql2verbal($e->dtend, $dtFormat);
            }
            
            if (!empty($e->dtstamp)) {
                $e->dtstamp = dt::timestamp2mysql(strtotime($e->dtstamp));
                $e->dtstampVrb = dt::mysql2verbal($e->dtstamp, $dtFormat);
            }
            
            if (!empty($e->created)) {
                $e->created = dt::timestamp2mysql(strtotime($e->created));
                $e->createdVrb = dt::mysql2verbal($e->created, $dtFormat);
            }
            
            if (!empty($e->lastmodified)) {
                $e->lastmodified = dt::timestamp2mysql(strtotime($e->lastmodified));
                $e->lastmodifiedVrb = dt::mysql2verbal($e->lastmodified, $dtFormat);
            }
            
            if (!empty($e->duration)) {
                $d = new \DateInterval($e->duration);
                $t = '';
                if ($d->y) {
                    $t .= $d->y . ' years ';
                }
                if ($d->m) {
                    $t .= $d->m . ' months ';
                }
                if ($d->d) {
                    $t .= $d->d . ' days ';
                }
                if ($d->h) {
                    $t .= $d->h . ' hours ';
                }
                
                if ($d->i) {
                    $t .= $d->i . ' minutes ';
                }
                
                if ($d->s) {
                    $t .= $d->s . ' seconds ';
                }
                
                $tt = cls::get('type_Time');
                
                $e->duration = $tt->fromVerbal_($t);
                
                $e->durationVrb = $tt->toVerbal_($e->duration);
            }
            
            if (empty($e->summary)) {
                $e->summary = tr('Събитие') . ' ' . ($id + 1);
            }
            $e->summary = str_replace(array('\\n\\r', '\\r\\n', '\\n', '\\r'), "\n", $e->summary);
            $e->summaryVrb = str_replace("\n", "\n<br>", type_Varchar::escape($e->summary));
            
            $e->location = str_replace(array('\\n\\r', '\\r\\n', '\\n', '\\r'), "\n", $e->location ?? '');
            $e->locationVrb = str_replace("\n", "\n<br>", type_Varchar::escape($e->location));
            
            $e->description = str_replace(array('\\n\\r', '\\r\\n', '\\n', '\\r'), "\n", $e->description ?? '');
            $e->descriptionVrb = str_replace("\\n", "<br>", type_Varchar::escape($e->description));
            
            $Richtext = cls::get('type_Richtext');
            $e->descriptionVrb = preg_replace_callback(type_Richtext::URL_PATTERN, array($Richtext, '_catchUrls'), $e->descriptionVrb);

            if (is_array($Richtext->_htmlBoard ?? null)) {
                // Подготовка и заместване на плейсхолдерите
                foreach ($Richtext->_htmlBoard as $place => $text) {
                    $Richtext->_htmlBoard[$place] = new ET($text);
                }

                if (countR($Richtext->_htmlBoard)) {
                    $e->descriptionVrb = new ET($e->descriptionVrb);
                    $e->descriptionVrb->placeArray($Richtext->_htmlBoard);
                }
            }

            
            $e->organizer = str_replace(array('\\n\\r', '\\r\\n', '\\n', '\\r'), "\n", $e->organizer ?? '');
            $e->organizerVrb = self::getPersons($e->organizer_array ?? null);
            
            $e->attendee = str_replace(array('\\n\\r', '\\r\\n', '\\n', '\\r'), "\n", $e->attendee ?? '');
            $e->attendeeVrb = self::getPersons($e->attendee_array ?? null);
            
            switch ($e->status ?? null) {
                case 'TENTATIVE':
                    $e->statusVrb = tr('ПРЕДВАРИТЕЛНО||' . $e->status);
                    break;
                case 'CONFIRMED':
                    $e->statusVrb = tr('ПОТВЪРДЕНО||' . $e->status);
                    break;
                case 'NEEDS-ACTION':
                    $e->statusVrb = tr('НЕОБХОДИМО ДЕЙСТВИЕ||' . $e->status);
                    break;
                case 'COMPLETED':
                    $e->statusVrb = tr('ИЗПЪЛНЕНО||' . $e->status);
                    break;
                case 'IN-PROCESS':
                    $e->statusVrb = tr('В ПРОЦЕС||' . $e->status);
                    break;
                case 'DRAFT':
                    $e->statusVrb = tr('ЧЕРНОВА||' . $e->status);
                    break;
                case 'FINAL':
                    $e->statusVrb = tr('ФИНАЛНО||' . $e->status);
                    break;
                case 'CANCELLED':
                    $e->statusVrb = tr('ОТКАЗАНО||' . $e->status);
                    break;
            }
            
            if (isset($e->statusVrb)) {
                $e->statusVrb = str::mbUcfirst(mb_strtolower($e->statusVrb));
            }
            
            if (($e->transp ?? null) == 'TRANSPARENT') {
                $e->statusVrb = ($e->statusVrb ?? '') . (!empty($e->statusVrb) ? ', ' : '') . tr('Не ангажира време');
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
        
        if (is_array($p)) {
            $tE = cls::get('type_Email');
            foreach ($p as $el) {
                if (is_array($el) && !empty($el['CN'])) {
                    $res .= ($res ? "\n<br>" : '') . type_Varchar::escape($el['CN']);
                } elseif (is_string($el) && stripos($el, 'mailto:') !== false) {
                    $eml = type_Email::extractEmails($el);
                    if (is_array($eml)) {
                        foreach ($eml as $email) {
                            if ($res) {
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
        
        if (is_array($events)) {
            foreach ($events as $e) {
                $tpl = new ET(tr('|*' . getFileContent('ical/tpl/Event.shtml')));
                $tpl->placeObject($e);
                $tpl->removeBlocks();
                $res->append($tpl);
            }
        }
        
        return $res;
    }
}
