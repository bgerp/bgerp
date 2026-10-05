<?php


/**
 * Общ родител на темите за външната част
 *
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cms_ProtoTheme extends core_ProtoInner
{
    /**
     * Темата на текущия домейн или празна тема, ако няма такава
     *
     * @return cms_ProtoTheme
     */
    public static function getCurrent()
    {
        $skin = cms_Domains::getCmsSkin();
        if ($skin instanceof self) {

            return $skin;
        }

        return cls::get(__CLASS__);
    }


    /**
     * Шаблонът, който темата ползва вместо подадения
     *
     * @param string $path
     *
     * @return string
     */
    public function getTemplate($path)
    {
        return $path;
    }


    /**
     * Пътят на вложен в шаблон файл, който темата ползва вместо подадения
     *
     * @param string $path
     *
     * @return string
     */
    public function resolveIncludePath($path)
    {
        return $path;
    }


    /**
     * Добавя стиловете на темата за публичния магазин
     *
     * @param core_ET $tpl
     */
    public function addShopAssets($tpl)
    {
    }


    /**
     * Добавя стиловете на темата за публичния форум
     *
     * @param core_ET $tpl
     */
    public function addForumAssets($tpl)
    {
    }
}
