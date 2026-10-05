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
     * Подготвя шаблона за статия от cms-а за широк режим
     */
    public function prepareWrapper($content)
    {

        return $this->class->prepareWrapper($content);
    }


    /**
     * Шаблонът, който темата ползва вместо подадения
     */
    public function getTemplate($path)
    {
        return $this->class->getTemplate($path);
    }


    /**
     * Добавя стиловете на темата за публичния магазин
     */
    public function addShopAssets($tpl)
    {
        return $this->class->addShopAssets($tpl);
    }


    /**
     * Добавя стиловете на темата за публичния форум
     */
    public function addForumAssets($tpl)
    {
        return $this->class->addForumAssets($tpl);
    }
}
