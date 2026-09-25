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
        $form->setField('menuPosition', 'input=none');
        $form->rec->menuPosition = 'above';
    }


    public function prepareWrapper($tpl)
    {
        // Ignore inherited positions saved before switching to this theme.
        if (!is_object($this->innerForm ?? null)) {
            $this->innerForm = new stdClass();
        }
        $this->innerForm->menuPosition = 'above';
        parent::prepareWrapper($tpl);
        $tpl->push('cms/css/CommerceMenu.css', 'CSS');
        $tpl->push('cms/css/CommerceCheckout.css', 'CSS');
        $tpl->appendOnce(' commerce-theme', 'BODY_CLASS_NAME');
        $tpl->push('cms/js/CommerceForms.js', 'JS');
        jquery_Jquery::run($tpl, 'initCommerceLogin(' . json_encode(tr('Покажи паролата')) . ', ' . json_encode(tr('Скрий паролата')) . ');');
    }


    /** Банер по подразбиране, когато няма изображение за текущия екран. */
    public function getHeaderImg()
    {
        $fields = Mode::is('screenMode', 'narrow') ? array('nImg') : array('wImg1', 'wImg2', 'wImg3', 'wImg4', 'wImg5', 'wImg6', 'wImg7', 'wImg8');
        foreach ($fields as $field) {
            if (!empty($this->innerForm->{$field})) {
                return parent::getHeaderImg();
            }
        }

        return ht::createElement('img', array(
            'src' => sbf('cms/img/commerce-banner.png', ''),
            'alt' => 'bgERP',
            'class' => 'headerImg commerce-default-banner',
        ));
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
