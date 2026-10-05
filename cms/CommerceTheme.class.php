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


    /** Папка с шаблоните; наследникът задава своя и държи в нея само различните. */
    public $tplDir = 'cms/tpl/commerce';


    /** Шаблоните на магазина, които темата подменя. */
    protected $shopTemplates = array(
        'AllProducts', 'GroupButton', 'ProductGroups', 'ProductGroupsNarrow', 'SingleLayoutCartExternal',
        'ProductListGroup', 'ProductListGroupNarrow', 'ProductShow', 'ProductShowNarrow',
    );


    /** Намерените шаблони в рамките на хита. */
    protected $templatePaths = array();


    /**
     * Наследник със свой Page.shtml в tplDir не трябва да сменя и layout
     */
    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->layout = $this->getTemplate($this->layout);
    }


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
        $tpl->push('cms/css/CommerceColors.css', 'CSS');
        $tpl->push('cms/css/CommerceMenu.css', 'CSS');
        $tpl->push('cms/css/CommerceCheckout.css', 'CSS');
        $tpl->push('cms/css/CommerceMobile.css', 'CSS');
        $tpl->push('cms/css/CommerceColab.css', 'CSS');
        $tpl->appendOnce(' commerce-theme', 'BODY_CLASS_NAME');
        $tpl->push('cms/js/CommerceForms.js', 'JS');
        jquery_Jquery::run($tpl, 'initCommerceLanguages();');
        jquery_Jquery::run($tpl, 'initCommerceNavigation(' . json_encode(tr('Меню')) . ', ' . json_encode(tr('Категории и филтри')) . ', ' . json_encode(tr('Категории')) . ');');
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
     * Шаблон от папката на темата, а ако го няма там - от най-близкия ѝ родител
     */
    public function getTemplate($path)
    {
        if (isset($this->templatePaths[$path])) {
            
            return $this->templatePaths[$path];
        }
        
        $name = null;
        if (strpos($path, 'cms/tpl/commerce/') === 0) {
            $name = basename($path, '.shtml');
        } elseif (preg_match('#^eshop/tpl/(\w+)\.shtml$#', $path, $matches) && in_array($matches[1], $this->shopTemplates)) {
            $name = $matches[1];
        }
        
        $res = $path;
        if (isset($name)) {
            $class = get_class($this);
            while ($class && is_a($class, __CLASS__, true)) {
                $vars = get_class_vars($class);
                if (getFullPath("{$vars['tplDir']}/{$name}.shtml")) {
                    $res = "{$vars['tplDir']}/{$name}.shtml";
                    break;
                }
                $class = get_parent_class($class);
            }
        }
        $this->templatePaths[$path] = $res;
        
        return $res;
    }
    
    
    /**
     * Подменя само вложените файлове на тази тема, другите остават непроменени
     */
    public function resolveIncludePath($path)
    {
        if (strpos($path, 'cms/tpl/commerce/') !== 0) {
            
            return $path;
        }
        
        return $this->getTemplate($path);
    }
    
    
    /**
     * Добавя стиловете на темата за публичния магазин
     */
    public function addShopAssets($tpl)
    {
        $tpl->push('cms/css/CommerceShop.css', 'CSS');
        $tpl->appendOnce(' eshop-public', 'BODY_CLASS_NAME');
    }
    
    
    /**
     * Добавя стиловете на темата за публичния форум
     */
    public function addForumAssets($tpl)
    {
        $tpl->push('cms/css/CommerceForum.css', 'CSS');
        $tpl->appendOnce(' commerce-forum', 'BODY_CLASS_NAME');
    }
    
    
    /**
     * @deprecated cms_ProtoTheme::getCurrent()->getTemplate()
     */
    public static function getShopTemplate($path)
    {
        return cms_ProtoTheme::getCurrent()->getTemplate($path);
    }
    
    
    /**
     * @deprecated cms_ProtoTheme::getCurrent()->addShopAssets()
     */
    public static function prepareShop($tpl)
    {
        cms_ProtoTheme::getCurrent()->addShopAssets($tpl);
    }
    
    
    /**
     * @deprecated cms_ProtoTheme::getCurrent()->addForumAssets()
     */
    public static function prepareForum($tpl)
    {
        cms_ProtoTheme::getCurrent()->addForumAssets($tpl);
    }
}
