<?php


/**
 * Интерфейс за тема на POS-а
 *
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pos_ThemeIntf
{
    /**
     * Подготвя пътя към темата
     *
     * @return string пътя към темата
     */
    public function getStyleFile()
    {
        return $this->class->getStyleFile();
    }
    
    
    /**
     * Връща шаблона на продуктите за бързо избиране
     *
     * @return core_ET - шаблон
     */
    public function getFavouritesTpl()
    {
        return $this->class->getStyleFile();
    }
}
