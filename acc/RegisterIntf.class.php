<?php



/**
 * Интерфейс за регистри източници на пера
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за регистри източници на пера
 */
class acc_RegisterIntf
{
    
    /**
     * Инстанция на мениджъра имащ интерфейса
     */
    public $class;
    
    
    /**
     * Преобразуване на запис на регистър към запис за перо в номенклатура (@see acc_Items)
     *
     * @param int $objectId ид на обект от регистъра, имплементиращ този интерфейс
     * @return stdClass запис за модела acc_Items:
     *
     * o num
     * o title
     * o uomId (ако има)
     * o features - списък от признаци за групиране
     */
    function getItemRec($objectId)
    {
        return $this->class->getItemRec($objectId);
    }
    
    
    /**
     * Нотифицира регистъра, че обекта е станал (или престанал да бъде) перо
     *
     * @param int $objectId ид на обект от регистъра, имплементиращ този интерфейс
     * @param boolean $inUse true - обекта е перо; false - обекта не е перо
     */
    function itemInUse($objectId, $inUse)
    {
        return $this->class->itemInUse($objectId, $inUse);
    }
    
    
    /**
     * Връща сметките, върху които може да се задават лимити на перото
     * 
     * @param stdClass $rec
     * @return array
     */
    public static function getLimitAccounts($rec)
    {
    	return $this->class->getLimitAccounts($rec);
    }
}