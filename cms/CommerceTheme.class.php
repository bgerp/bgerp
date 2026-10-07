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


    /** Шаблоните на модулите, които темата подменя със свои от tplDir; наследникът добавя своите. */
    protected $templateMap = array(
        'eshop/tpl/AllProducts.shtml' => 'AllProducts',
        'eshop/tpl/GroupButton.shtml' => 'GroupButton',
        'eshop/tpl/ProductGroups.shtml' => 'ProductGroups',
        'eshop/tpl/ProductGroupsNarrow.shtml' => 'ProductGroupsNarrow',
        'eshop/tpl/SingleLayoutCartExternal.shtml' => 'SingleLayoutCartExternal',
        'eshop/tpl/ProductListGroup.shtml' => 'ProductListGroup',
        'eshop/tpl/ProductListGroupNarrow.shtml' => 'ProductListGroupNarrow',
        'eshop/tpl/ProductShow.shtml' => 'ProductShow',
        'eshop/tpl/ProductShowNarrow.shtml' => 'ProductShowNarrow',
        'blogm/tpl/Layout.shtml' => 'BlogLayout',
        'blogm/tpl/LayoutNarrow.shtml' => 'BlogLayoutNarrow',
        'blogm/tpl/Browse.shtml' => 'BlogBrowse',
        'blogm/tpl/Article.shtml' => 'BlogArticle',
        'cms/themes/default/Articles.shtml' => 'Articles',
        'cms/themes/default/ArticlesNarrow.shtml' => 'Articles',
        'cms/themes/default/WideArticles.shtml' => 'WideArticles',
        'cms/tpl/Feeds.shtml' => 'Feeds',
    );


    /** Ресурсите по раздели; page е за всяка страница, а CommerceCheckout.css е там, защото стилизира и формите за вход. */
    public $assets = array(
        'page' => array(
            'css' => array('cms/css/CommerceColors.css', 'cms/css/CommerceMenu.css', 'cms/css/CommerceCheckout.css', 'cms/css/CommerceMobile.css', 'cms/css/CommerceColab.css'),
            'js' => array('cms/js/CommerceForms.js'),
            'body' => 'commerce-theme',
        ),
        'shop' => array('css' => array('cms/css/CommerceShop.css'), 'body' => 'eshop-public'),
        'checkout' => array('css' => array('cms/css/CommerceShop.css'), 'body' => 'eshop-public commerce-checkout'),
        'forum' => array('css' => array('cms/css/CommerceForum.css'), 'body' => 'commerce-forum'),
        'blog' => array('css' => array('cms/css/CommerceBlog.css'), 'body' => 'commerce-blog'),
        'article' => array('css' => array('cms/css/CommerceBlog.css', 'cms/css/CommerceArticles.css'), 'body' => 'commerce-blog commerce-article'),
        'feeds' => array('css' => array('cms/css/CommerceFeeds.css'), 'body' => 'commerce-feeds'),
    );


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

        // Браузърът сам проверява дали поддържа :has(), на който стъпват стиловете на темата
        $form->FNC('browserSupportsTheme', 'enum(,yes,no)', 'input=hidden,silent');
        $js = new core_ET('');
        jquery_Jquery::run($js, "var themeCss = window.CSS && CSS.supports && CSS.supports('selector(:has(a))'); $('input[name=browserSupportsTheme]').val(themeCss ? 'yes' : 'no');");
        $form->info = empty($form->info) ? $js : new core_ET('[#1#][#2#]', $form->info, $js);
    }


    /**
     * Предупреждава, ако браузърът на потребителя не може да покаже темата
     */
    public function checkEmbeddedForm(core_Form &$form)
    {
        parent::checkEmbeddedForm($form);

        if ($form->isSubmitted() && ($form->rec->browserSupportsTheme ?? null) == 'no') {
            $form->setWarning('theme', 'Браузърът ви е твърде стар за тази тема и сайтът няма да се показва правилно в него. Нужен е Chrome 105, Safari 15.4, Firefox 121 или по-нов|*!');
        }

        // Не е настройка на темата и не се записва
        unset($form->rec->browserSupportsTheme);
    }


    public function prepareWrapper($tpl)
    {
        // Ignore inherited positions saved before switching to this theme.
        if (!is_object($this->innerForm ?? null)) {
            $this->innerForm = new stdClass();
        }
        $this->innerForm->menuPosition = 'above';
        parent::prepareWrapper($tpl);
    }


    /**
     * Функциите са в CommerceForms.js, затова без него не се викат
     */
    public function getScripts($section, $jsFiles)
    {
        if ($section != 'page' || !in_array('cms/js/CommerceForms.js', $jsFiles)) {

            return array();
        }

        return array(
            'commerceLanguages' => 'initCommerceLanguages();',
            'commerceNavigation' => 'initCommerceNavigation(' . json_encode(tr('Меню')) . ', ' . json_encode(tr('Категории и филтри')) . ', ' . json_encode(tr('Категории')) . ');',
            'commerceLogin' => 'initCommerceLogin(' . json_encode(tr('Покажи паролата')) . ', ' . json_encode(tr('Скрий паролата')) . ');',
        );
    }


    /** Банер по подразбиране; наследникът задава свой. */
    public $defaultBanner = 'cms/img/commerce-banner.png';


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
            'src' => sbf($this->defaultBanner, ''),
            'alt' => 'bgERP',
            'class' => 'headerImg commerce-default-banner',
        ));
    }


    /** По-големи тъмбнейли в магазина. */
    public $productThumbSize = array(640, 360);


    /** Прозорецът за вход побира новата форма. */
    public $loginWindowFeatures = 'width=560,height=560,resizable=yes,scrollbars=yes';


    public function getBlogPagerLabels()
    {
        return array('← ' . tr('По-стари'), tr('По-нови') . ' →');
    }


    public function getArchiveMonthTitle($month, $year)
    {
        return dt::getMonth($month, 'F') . ' ' . $year;
    }


    public function getCartToolbarLabels()
    {
        return array(tr('Към магазина||Back to shop'), tr('Изчисти количката||Clear cart'));
    }


    public function prepareCartLink($tpl, &$className, $cartName, $count)
    {
        $className .= ' commerce-cart-link';
        $tpl = new core_ET('<span class="commerce-cart-label">[#name#]</span><span class="count">[#count#]</span>');
        $tpl->replace($cartName, 'name');
        $tpl->replace($count, 'count');

        return $tpl;
    }


    public function prepareFavouriteBtn(&$attr, $isIn)
    {
        $attr['ef_icon'] = $isIn ? 'cms/img/heart-filled.svg' : 'cms/img/heart-outline.svg';
        $attr['class'] .= $isIn ? ' is-favourite' : '';
        $attr['role'] = 'button';
        $attr['aria-pressed'] = $isIn ? 'true' : 'false';
        $attr['aria-label'] = $attr['title'];
    }


    public function getSharingLinkContent($img, $cnt)
    {
        return $img . ht::createElement('span', array('class' => 'commerce-sharing-count'), (int) $cnt);
    }


    public function prepareLoginNote(&$info, &$loginHtml, $js)
    {
        $info = new ET("<div id='editStatus' class='commerce-login-note'>[#1#] [#link#]</div>", tr('Имате профил?'));
        $loginHtml = ht::createElement('a', array('href' => 'javascript:void(0)', 'onclick' => $js, 'oncontextmenu' => $js), tr('Вход в профила'));
    }


    /**
     * По-старите статии може да имат собствено заглавие в текста
     */
    public function prepareArticleContent($content, $rec)
    {
        if (!empty($rec->title) && !preg_match('/^\s*(?:<div\b[^>]*>\s*)*<h[12]\b/i', $content->content)) {
            $content->prepend('<h1 class="cms-article-title">' . type_Varchar::escape($rec->title) . '</h1>');
        }
    }


    public function getArticleEditLink($mvc, $rec)
    {
        if (core_Users::getCurrent() > 0 && $mvc->haveRightFor('changerec', $rec)) {

            return ht::createLink('', $mvc->getChangeUrl($rec->id), null, 'ef_icon=img/16/edit.png,class=commerce-article-edit,title=Редактиране на статията');
        }

        return null;
    }


    /**
     * Менюто заема TOP_PAGE, а на мобилен се ползват същите места във футъра
     */
    public function getNewsbarPlace($position, $placeholderName, $tpl)
    {
        $positions = array('topPage' => 'COMMERCE_TOP_NEWS', 'beforeFooter' => 'BEFORE_FOOTER', 'afterFooter' => 'AFTER_FOOTER', 'topNav' => 'TOP_CONTENT', 'bottomNav' => 'BOTTOM_CONTENT');
        $placeholderName = $positions[$position] ?? $placeholderName;
        if (in_array($placeholderName, array('TOP_CONTENT', 'BOTTOM_CONTENT')) && !$tpl->isPlaceholderExists($placeholderName)) {
            $placeholderName = 'COMMERCE_' . $placeholderName;
        }

        return $placeholderName;
    }


    /**
     * Темата няма навигация; старите записи там остават редактируеми и се показват в съдържанието
     */
    public function prepareNewsbarPositions(&$positions, $rec)
    {
        unset($positions['topNav'], $positions['bottomNav']);
        $positions['topPage'] = 'Над менюто';
        $positions['bottomHeader'] = 'Под банера';
        if (!empty($rec->id) && in_array($rec->position, array('topNav', 'bottomNav'))) {
            $positions[$rec->position] = 'Стара позиция в навигацията';
        }
    }


    public function wrapMenuTools($tools)
    {
        $res = new core_ET('<span class="cms-menu-tools"><span id="cart-external-status">[#USERCART#]</span>[#TOOLS#]</span>');
        $res->replace($tools, 'TOOLS');

        return $res;
    }


    public function getMenuLoginContent(&$title)
    {
        $title = haveRole('user') ? 'Към системата||Go to system' : 'Вход||Log in';
        $icon = ht::createImg(array('path' => 'cms/img/account.svg', 'alt' => '', 'aria-hidden' => 'true'));
        $label = ht::createElement('span', array('class' => 'cms-menu-label'), tr($title));

        return $icon->getContent() . $label->getContent();
    }


    /**
     * При повече от два езика - падащо меню вместо глобус
     */
    public function renderMenuLangs($tpl, $usedLangsArr)
    {
        if (countR($usedLangsArr) <= 2) {
            parent::renderMenuLangs($tpl, $usedLangsArr);

            return;
        }

        $lang = cms_Content::getLang();
        $currentLabel = htmlspecialchars($lang == 'bg' ? 'БГ' : strtoupper($lang), ENT_QUOTES, 'UTF-8');
        $languageLinks = '';
        foreach ($usedLangsArr as $lg) {
            $nativeName = drdata_Languages::fetchField("#code = '{$lg}'", 'nativeName');
            $label = htmlspecialchars($lg == 'bg' ? 'БГ' : strtoupper($lg), ENT_QUOTES, 'UTF-8');
            $flag = '';
            if (getFullPath('img/flags/' . $lg . '.png')) {
                $flag = ht::createElement('img', array('src' => sbf('img/flags/' . $lg . '.png', ''), 'alt' => ''))->getContent();
            }
            if ($lg == $lang) {
                $currentLabel = $flag . $currentLabel;
            }
            $attr = array('class' => 'commerce-language-link', 'hreflang' => $lg, 'lang' => $lg, 'title' => $nativeName, 'aria-label' => $nativeName ?: strtoupper($lg));
            if ($lg == $lang) {
                $attr['aria-current'] = 'true';
            }
            $languageLinks .= ht::createLink($flag . $label, array('cms_Content', 'SelectLang', 'lang' => $lg), null, $attr);
        }
        $tpl->append('<details class="commerce-languages"><summary aria-label="' . htmlspecialchars(tr('Език||Language'), ENT_QUOTES, 'UTF-8') . '">' . $currentLabel . '</summary><div class="commerce-language-options">' . $languageLinks . '</div></details>');
    }


    public function getMenuLangContent($img, $lg)
    {
        return $img . ht::createElement('span', array('class' => 'cms-menu-label'), $lg == 'bg' ? 'БГ' : strtoupper($lg));
    }


    /**
     * @deprecated cms_ProtoTheme::getCurrent()->getTemplate()
     */
    public static function getShopTemplate($path)
    {
        return cms_ProtoTheme::getCurrent()->getTemplate($path);
    }
    
    
    /**
     * @deprecated cms_ProtoTheme::getCurrent()->addAssets($tpl, 'shop')
     */
    public static function prepareShop($tpl)
    {
        cms_ProtoTheme::getCurrent()->addAssets($tpl, 'shop');
    }
    
    
    /**
     * @deprecated cms_ProtoTheme::getCurrent()->addAssets($tpl, 'forum')
     */
    public static function prepareForum($tpl)
    {
        cms_ProtoTheme::getCurrent()->addAssets($tpl, 'forum');
    }
}
