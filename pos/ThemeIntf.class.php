<?php



/**
 * Интерфейс за тема на POS-а
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pos_ThemeIntf
{
    /**
     * Подготвя пътя към темата
     * @return string пътя към темата
     */
    function getSbf()
    {
    	return $this->class->getSbf();
    }
}