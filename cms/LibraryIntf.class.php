<?php


/**
 * Интерфейс за мултимедиен обект от библиотеката
 *
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за драйвер на обект от библиотеката
 */
class cms_LibraryIntf extends embed_DriverIntf
{
    /**
     * Връща HTML представянето на обекта 
     */
    public function render($rec)
    {
        return $this->class->render($data);
    }
    
    
    /**
     * Връща наименованието на обекта
     */
    public function getName($rec)
    {
        return $this->class->getName($rec);
    }
    
}
