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
        $form->FLD('wallpaper', 'fileman_FileType(bucket=gallery_Pictures)', 'caption=Хедър->Изображение,hint=Примерни размери 1920×1280px');
        $form->FLD('domainTitle', 'varchar(100)', 'caption=Хедър->Заглавие на домейна');
        $form->FLD('headTitle', 'varchar(100)', 'caption=Хедър->Заглавие');
        $form->FLD('subtitle', 'varchar(100)', 'caption=Хедър->Подзаглавие');
        $form->FLD('footerTitle', 'varchar(100)', 'caption=Футър->Заглавие');
        $form->FLD('footerSubtitle', 'varchar(100)', 'caption=Футър->Подзаглавие');
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
        Mode::set('theme', 'singlepage');
        if(!haveRole('user')) {
            $login = cls::get('core_Users')->act_Login();
            $loginHtml = "<button type='button'  class='get-started-btn scrollto boldText'  data-bs-toggle='modal' data-bs-target='#loginModal'>" . tr("Вход||Log in") . '</button>';
        } else {
            $loginHtml = ht::createLink(". . .", array('Portal', 'Show'), null, array('class' => "get-started-btn scrollto boldText"));
        }

        $content = $this->getCmsLayout();

        if ($content !== false) {
            $tpl->replace($loginHtml, "LOGIN_BTN");
            $tpl->replace($content, 'CMS_LAYOUT');
        }

        $menu = $this->getMenu();
        $tpl->replace($menu, 'MENU');

        $img = $this->innerForm->wallpaper;
        $css = "";

        $headTitle = $this->innerForm->headTitle;
        if ($headTitle) {
            $tpl->replace($headTitle, 'HEADTITLE');
        }

        $subtitle = $this->innerForm->subtitle;
        if ($subtitle) {
            $tpl->replace($subtitle, 'SUBTITLE');
        }

        if ($img) {
            $img = new thumb_Img(array($img, 1200, 220, 'fileman', 'isAbsolute' => false, 'mode' => 'large-no-change'));
            $imageURL = $img->getUrl('forced');
            $css .= "\n  #wallpaper-block {background: url({$imageURL}) center;} ";
        } else if (!$headTitle || $subtitle){
            $tpl->removeBlock("WALLPAPER_BLOCK");
        }

        $domainTitle = $this->innerForm->domainTitle ? $this->innerForm->domainTitle :  $this->innerForm->title;
        if ($domainTitle) {
            $tpl->replace($domainTitle, 'CORE_APP_NAME');
        }

        $footerTitle = $this->innerForm->footerTitle;
        if ($footerTitle) {
            $tpl->replace($footerTitle, 'FOOTER_TITLE');
        }
        $footerSubtitle = $this->innerForm->footerSubtitle;
        if ($footerSubtitle) {
            $tpl->replace($footerSubtitle, 'FOOTER_SUBTITLE');
        }

        $baseColor = $this->innerForm->baseColor;
        if($baseColor) {
            $activeColor = phpcolor_Adapter::changeColor($baseColor, 'mix', 1, '#666');

            $css .= "\n  .get-started-btn, .back-to-top, #footer .social-links a, .navbar>ul>li>a:before, #main h2::after, #login-form .button  {background: {$baseColor};} ";
            $css .= "\n  .get-started-btn:hover, .back-to-top:hover, #footer .social-links a:hover, #login-form .button:hover {background: #{$activeColor};} ";
            $css .= "\n  a, #footer .credits a, .navbar-mobile a:hover, .navbar-mobile .active, .navbar-mobile li:hover>a {color: {$baseColor};} ";
            $css .= "\n  a:hover, #footer .credits a:hover, .navbar-mobile li:hover>a {color: #{$activeColor};} ";
            $css .= "\n  #preloader:before { border: 6px solid {$baseColor};} ";
            $css .= "\n  #loginModal .formFields input:active, #loginModal .formFields input:focus, .company-box:hover { border-color: {$baseColor} !important;} ";
        }

        $tpl->append($css, 'STYLES');

        // добавяме css-a за структурата
        $tpl->push('cms/css/bootstrap.css', 'CSS');
        $tpl->push('cms/bootstrap-icons/bootstrap-icons.css', 'CSS');
        $tpl->push('cms/boxicons/css/boxicons.min.css', 'CSS');
        $tpl->push('cms/css/SinglePage.css', 'CSS');

        $tpl->push('cms/js/main.js', 'JS');
        $tpl->push('cms/js/bootstrap.min.js', 'JS');

        $tpl->append('<div class="modal " id="loginModal" area-hidden="true">');
        $tpl->append('<div class="modal-dialog  modal-dialog-centered">');
        $tpl->append('<div class="modal-content">');
        $tpl->append('<div class="modal-header"><h3>Вход в ' . $domainTitle . '</h3></div>');
        $tpl->append('<div class="modal-body">');
        $tpl->append($login);
        $tpl->append('</div>');
        $tpl->append('</div>');
        $tpl->append('</div>');
        $tpl->append('</div>');
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
            $content->append("<section id='item{$aRec->id}' {$style} class='{$fixedImageClass}'><div class='container'><h2>{$aRec->title}{$changeLink}</h2>");
            $content->append($body);
            $content->append("</div></section>");
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
        $cQuery->EXT('menuState', 'cms_Content', 'externalName=state,externalKey=menuId');
        $cDomainId = cms_Domains::getPublicDomain()->id;
        $cQuery->where(array("#domainId = '[#1#]'", $cDomainId));
        $cQuery->where(array("#menuState = 'active'"));

        $res = $cQuery->fetchAll();

        return $res;
    }
}