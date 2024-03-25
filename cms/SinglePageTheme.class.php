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
        $content = $this->getCmsLayout();
        if ($content !== false) {
            $tpl->replace($content, 'CMS_LAYOUT');
        }

        $subtitle = $this->innerForm->subtitle;
        if ($subtitle) {
            $tpl->replace($subtitle, 'SUBTITLE');
        }

        $menu = $this->getMenu();
        $tpl->replace($menu, 'MENU');



        $img = $this->innerForm->wallpaper;

        if ($img) {
            $img = new thumb_Img(array($img, 1200, 220, 'fileman', 'isAbsolute' => false, 'mode' => 'large-no-change'));

            $imageURL = $img->getUrl('forced');

            $css = "\n  #wallpaper-block {background: url({$imageURL}) top center;} ";
            $tpl->append($css, 'STYLES');

            $title = $this->innerForm->title;
            if ($title) {
                $tpl->replace($title, 'CORE_APP_NAME');
            }
            $headTitle= $this->innerForm->headTitle;
            if ($headTitle) {
                $tpl->replace($headTitle, 'TITLE');
            }
        }


        // добавяме css-a за структурата
        $tpl->push('cms/css/bootstrap.css', 'CSS');
        $tpl->push('cms/bootstrap-icons/bootstrap-icons.css', 'CSS');
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
            $className = ($aRec->id % 2 == 0) ? "section-bg" : "";
            $content->append("<section id='item{$aRec->id}' class='{$className}'><div class='container'><h2>{$aRec->title}</h2>{$body}</div></section>");

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
