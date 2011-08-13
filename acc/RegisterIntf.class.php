<?php

/**
 * Интерфейс за регистри източници на пера
 *
 * @category   bgERP 2.0
 * @package    acc
 * @title:     Източник на пера
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 * @since      v 0.1
 */
class acc_RegisterIntf
{    
    /**
     * Връща записа на перото за посочения обект. Този запис включва: номер, титла, масив от черти
     *
     * @param int $id id на запис от модела на мениджъра, който имплементира този интерфейс
     * @return stdClass запис за модела acc_Items
     *
     *  o title
     *  o uomId (ако има)
     *  о features = array()
     */
    function getAccItemRec($objId) 
    {
        return $this->class->getAccItemRec($objId);
    }

    /**
     * Нотифицира обекта, че този запис се използва/не се използва като перо.  
     * Това означава, че мениджъра на обектите от тук нататък ще информира 
     * acc_Lists при всяка промяна на информацията за този обект
     * 
     * @param int $id id на запис от модела на мениджъра, който имплементира този интерфейс
     *
     */
    function itemInUse($id, $state = TRUE)
    {
        return $this->class->itemInUse($id, $state);
    }
    

    /**
     * Връща хипервръзка към този обект
     *
     * @param int $id id на запис от модела на мениджъра, който имплементира този интерфейс
     * @return core_Et шаблон с линк към обекта
     */
    function getLinkToObj($id)
    {
        return $this->class->getLinkToObj($id);
    }
}