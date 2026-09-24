<?php

/**
 * Съвременна тема за публичен каталог и онлайн магазин.
 *
 * @title Търговска CMS тема
 * @package cms
 */
class cms_CommerceTheme extends cms_FancyTheme
{
    /** Собствена подредба на навигацията и банера. */
    public $layout = 'cms/tpl/commerce/Page.shtml';


    /** Не наследяваме старото име на широката тема. */
    public $oldClassName = null;


    /**
     * Настройките за изображения и цветове остават съвместими с широката тема.
     */
    public function prepareEmbeddedForm(core_Form &$form)
    {
        parent::prepareEmbeddedForm($form);
        $form->setDefault('menuPosition', 'above');
    }


    public function prepareWrapper($tpl)
    {
        parent::prepareWrapper($tpl);
        $tpl->push('cms/css/CommerceMenu.css', 'CSS');
        $tpl->appendOnce(' commerce-theme', 'BODY_CLASS_NAME');
    }


    /**
     * Избира специализиран шаблон само за търговската тема.
     */
    public static function getShopTemplate($path)
    {
        if (cms_Domains::getCmsSkin() instanceof self) {
            $templates = array(
                'AllProducts', 'GroupButton', 'ProductGroups', 'ProductGroupsNarrow', 'SingleLayoutCartExternal',
                'ProductListGroup', 'ProductListGroupNarrow', 'ProductShow', 'ProductShowNarrow',
            );
            foreach ($templates as $name) {
                if ($path == "eshop/tpl/{$name}.shtml") {
                    return "cms/tpl/commerce/{$name}.shtml";
                }
            }
        }

        return $path;
    }


    /** Добавя стиловете на магазина само за тази тема. */
    public static function prepareShop($tpl)
    {
        if (cms_Domains::getCmsSkin() instanceof self) {
            $tpl->push('cms/css/CommerceShop.css', 'CSS');
            $tpl->appendOnce(' eshop-public', 'BODY_CLASS_NAME');
        }
    }
}
