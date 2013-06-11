<?php



/**
 * Интерфейс за импортиране
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_ImportIntf
{
    
	/**
	 * Импортиране на данни от csv файл в мениджър
	 * @param string $hnd - хендлър на качен csv файл
	 * @param string $text - ръчно въведен csv текст
	 * @return string $res - съобщение с резултата от импорта
	 */
    function import($hnd, $text)
    {
        return $this->class->import($hnd, $text);
    }
    
    
    /**
     * В кой мениджър ще се импортират данните
     */
	function getDestinationManager()
    {
        return $this->class->getDestinationManager();
    }
}