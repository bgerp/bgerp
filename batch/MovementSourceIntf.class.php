<?php



/**
 * Интерфейс за документ генериращ партидни движения
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_MovementSourceIntf
{

	/**
	 * Инстанция на мениджъра имащ интерфейса
	 */
	public $class;
	
	
	function getMovements($id)
	{
		return $this->class->getMovements($id);
	}
}