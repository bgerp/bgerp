<?php

 /**
 * Интерфейс за класове, даващи информация за важни дати
 *
 * @category   bgERP 2.0
 * @package    crm
 * @title:     Попълване на датите в календара
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 * @since      v 0.1
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