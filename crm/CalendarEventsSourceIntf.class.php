<?php


/**
 * Интерфейс за класове, даващи информация за важни дати
 *
 *
 * @category  bgerp
 * @package   crm
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Попълване на датите в календара
 */
class crm_CalendarEventsSourceIntf
{
    
    
    
    /**
     * Връща вербалния изказ на събитието от тип '$type' на обекта $objectId
     */
    function getVerbalCalendarEvent($type, $objectId, $date)
    {
        return $this->class->getVerbalCalendarEvent($type, $objectId, $date);
    }
    
    
    
    /**
     * Връща събитията асоциирани с даден човек
     */
    function getCalendarEvents($objectId)
    {
        return $this->class->getCalendarEvents($objectId);
    }
}