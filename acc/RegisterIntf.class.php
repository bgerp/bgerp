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
	 * Хипервръзка към този обект
	 *
	 * @param int $objectId ид на обект от регистъра, имплементиращ този интерфейс
	 * @return mixed string или ET (@see ht::createLink())
	 */
	function getLinkToObj($objectId)
	{
		return $this->class->getLinkToObj($objectId);
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
	 * Имат ли обектите на регистъра размерност?
	 *
	 * @return boolean
	 */
	function isDimensional()
	{
		return $this->class->isDimensional();
	}
}