<?php


/**
 * Интерфейс за тема на cms-системата
 *
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Източник на публично съдържание
 */
class cms_ThemeIntf extends core_InnerObjectIntf
{
    /**
     * Връща шаблона за статия от cms-а за широк режим
     *
     * @return файла на шаблона
     */
    public function wrapContent($content)
    {
        return $this->class->wrapContent($content);
    }
}
