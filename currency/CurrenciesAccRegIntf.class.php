<?php

/**
 * Интерфейс за пера - валути
 *
 * @category   bgERP 2.0
 * @package    dma
 * @title:     Валути
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 * @since      v 0.1
 */
class currency_CurrenciesAccRegIntf extends acc_RegisterIntf
{    
	
	/**
	 * Имат ли обектите на регистъра размерност?
	 *
	 * @return boolean
	 */
	function isDimensional()
	{
		return TRUE;
	}
}