<?php


/**
 * Интерфейс за класове, които добавят табове към единичния изглед на файл
 *
 * @category  bgerp
 * @package   fileman
 *
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class fileman_FileTabsIntf
{
    public $class;


    /**
     * Връща табове за даден файл.
     *
     * Всеки таб е обект с title, url и order. URL-ът се рендира от
     * fileman_Indexes чрез стандартния iframe изглед на файловите табове.
     *
     * @param stdClass $fRec
     *
     * @return array|null
     */
    public function getTabsForFile($fRec)
    {

        return $this->class->getTabsForFile($fRec);
    }
}
