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
class cms_SinglePageTheme extends cms_FancyTheme
{

    /**
     * Общ лейаут за темата
     */
    public $layout = 'cms/tpl/SinglePage.shtml';


    /**
     * Подготвя шаблона за статия от cms-а за широк режим
     *
     * @param $tpl
     *
     * @see cms_ThemeIntf::prepareWrapper()
     */
    public function prepareWrapper($tpl)
    {
        parent::prepareWrapper($tpl);


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
            $menu->append("<li> <a href='#item{$aRec->id}'> {$aRec->title}</a> </li>");
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
        $content = new ET();

        $aArr = $this->getArticlesRecs();
        if (empty($aArr)) {

            return false;
        }


        foreach ($aArr as $aRec) {
            $body = cms_Articles::getVerbal($aRec, 'body');
            $content->append("<section id='item{$aRec->id}'><div class='container'><h3>{$aRec->title}</h3>{$body}</div></section>");

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
