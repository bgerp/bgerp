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
     * Общото наименование на драйверите
     */
    public $driversCommonName = 'драйвери за визуални елементи';
    
    
    /**
     * Връща HTML представянето на обекта
     *
     * @param stdClass $rec Записа за елемента от модела-библиотека
     * @param $maxWidth int Максимална широчина на елемента
     * @param $isAbsolute bool Дали URL-тата да са абсолютни
     *
     * @return core_ET Представяне на обекта в HTML шабло
     */
    public function render($rec, $maxWidth, $isAbsolute = false)
    {
        return $this->class->render($rec, $maxWidth, $isAbsolute);
    }
    
    
    /**
     * Връща наименованието на обекта
     *
     * @param stdClass $rec запис в ембедъра
     *
     * @return string
     */
    public function getName($rec)
    {
        return $this->class->getName($rec);
    }
}
