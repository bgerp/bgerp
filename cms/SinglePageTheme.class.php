<?php


/**
 * Единична страница
 *
 * @title     Единична страница
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cms_SinglePageTheme extends core_ProtoInner
{


    /**
     * Поддържан интерфейс
     */
    public $interfaces = 'cms_ThemeIntf';


    /**
     * Общ лейаут за темата
     */
    public $layout = 'cms/tpl/SinglePage.shtml';


    /**
     * @param core_FieldSet $form
     */
    public function addEmbeddedFields(core_FieldSet &$form)
    {
        $form->FLD('wallpaper', 'fileman_FileType(bucket=gallery_Pictures)', 'caption=Изображение');
        $form->FLD('headTitle', 'varchar(100)', 'caption=Заглавие');
        $form->FLD('subtitle', 'varchar(100)', 'caption=Подзаглавие');
        $form->FLD('baseColor', 'color_Type(AllowEmpty)', 'caption=Основен цвят');
    }


    /**
     * Подготвя шаблона за статия от cms-а за широк режим
     *
     * @param $tpl
     *
     * @see cms_ThemeIntf::prepareWrapper()
     */
    public function prepareWrapper($tpl)
    {
        if(!haveRole('user')) {
            $js = 'w=window.open("' . toUrl(array('core_Users', 'login', 'ret_url' => array('Portal', 'show'), 'popup' => 1)) . '","Login","width=484,height=316,resizable=no,scrollbars=no,location=0,status=no,menubar=0"); if(w) w.focus();';
            $loginHtml = "<a href='javascript:void(0)' class='get-started-btn scrollto boldText' oncontextmenu='{$js}' onclick='{$js}'>" . tr("Вход||Log in") . '</a>';
        } else {
            $loginHtml = ht::createLink(". . .", array('Portal', 'Show'), null, array('class' => "get-started-btn scrollto boldText"));
        }

        $tpl->replace($loginHtml, "LOGIN_BTN");

        $content = $this->getCmsLayout();
        if ($content !== false) {
            $tpl->replace($content, 'CMS_LAYOUT');
        }

        $menu = $this->getMenu();
        $tpl->replace($menu, 'MENU');

        $img = $this->innerForm->wallpaper;

        if ($img) {
            $img = new thumb_Img(array($img, 1200, 220, 'fileman', 'isAbsolute' => false, 'mode' => 'large-no-change'));

            $imageURL = $img->getUrl('forced');

            $css = "\n  #wallpaper-block {background: url({$imageURL}) top center;} ";

            $baseColor = $this->innerForm->baseColor;
            if($baseColor) {
                $activeColor = phpcolor_Adapter::changeColor($baseColor, 'mix', 1, '#666');

                $css .= "\n  .get-started-btn, .back-to-top, #footer .social-links a, .navbar>ul>li>a:before, #main h2::after  {background: {$baseColor};} ";
                $css .= "\n  .get-started-btn:hover, .back-to-top:hover, #footer .social-links a:hover {background: #{$activeColor};} ";
                $css .= "\n  #footer .credits a {color: {$baseColor};} ";
                $css .= "\n  #footer .credits a:hover, .navbar-mobile li:hover>a {color: #{$activeColor};} ";
                $css .= "\n  #preloader:before { border: 6px solid {$baseColor};} ";
            }

            $tpl->append($css, 'STYLES');

            $title = $this->innerForm->title;
            if ($title) {
                $tpl->replace($title, 'CORE_APP_NAME');
            }
            $headTitle = $this->innerForm->headTitle;
            if ($headTitle) {
                $tpl->replace($headTitle, 'TITLE');
            }
            $subtitle = $this->innerForm->subtitle;
            if ($subtitle) {
                $tpl->replace($subtitle, 'SUBTITLE');
            }
        }


        // добавяме css-a за структурата
        $tpl->push('cms/css/bootstrap.css', 'CSS');
        $tpl->push('cms/bootstrap-icons/bootstrap-icons.css', 'CSS');
        $tpl->push('cms/boxicons/css/boxicons.min.css', 'CSS');
        $tpl->push('cms/css/SinglePage.css', 'CSS');


        $tpl->push('cms/js/main.js', 'JS');
    }


    /**
     * Помощна функция за подготовка на менюто, което ще се покаже
     *
     * @return false|core_ET
     */
    protected function getMenu()
    {
        $menu = new ET();

        $aArr = $this->getArticlesRecs();
        if (empty($aArr)) {
            return false;
        }

        $menu->append("<nav id='navbar' class='navbar order-last order-lg-0'><ul>");

        foreach ($aArr as $aRec) {
            $menu->append("<li> <a class='nav-link scrollto' href='#item{$aRec->id}'> {$aRec->title}</a> </li>");
        }

        $menu->append("</ul></nav>");
        return $menu;
    }


    /**
     * Помощна функция за подготовка на лейаута, който ще се покаже
     *
     * @return false|core_ET
     */
    protected function getCmsLayout()
    {
        //$content = new ET("[#CMS_LAYOUT#]");
        $content = new ET("");

        $aArr = $this->getArticlesRecs();
        if (empty($aArr)) {

            return false;
        }

        foreach ($aArr as $aRec) {
            $body = cms_Articles::getVerbal($aRec, 'body');
            $wallpaper = $aRec->wallpaper;
            $background = $aRec->background;
            $style = "";
            $fixedImageClass = "";
            if($wallpaper) {
                $img = new thumb_Img(array($wallpaper, 1200, 220, 'fileman', 'isAbsolute' => false, 'mode' => 'large-no-change'));
                $imageURL = $img->getUrl('forced');
                $style="style = 'background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url({$imageURL}) fixed center center;'";
                $fixedImageClass = "fixed-bg";
            } else if ($background) {
                $style="style = 'background: {$background}'";
            }

            $changeLink = "";
            if (cms_Articles::haveRightFor('change', $aRec->id)) {
                $changeLink = cms_Articles::getChangeLink($aRec->id);
            }
            $content->append("<section id='item{$aRec->id}' {$style} class='{$fixedImageClass}'><div class='container'><h2>{$aRec->title}{$changeLink}</h2>{$body}</div></section>");
        }

        return $content;
    }


    /**
     * Помощна функция за вземане на активните статии към този домейн
     *
     * @return false|array
     */
    protected function getArticlesRecs()
    {
        static $res = false;
        if ($res !== false) {

            return $res;
        }
        $cQuery = cms_Articles::getQuery();
        $cQuery->where("#state = 'active'");
        $cQuery->orderBy('level');
        $cQuery->EXT('domainId', 'cms_Content', 'externalName=domainId,externalKey=menuId');
        $cDomainId = cms_Domains::getPublicDomain()->id;
        $cQuery->where(array("#domainId = '[#1#]'", $cDomainId));

        $res = $cQuery->fetchAll();

        return $res;
    }
}