<?php


/**
 * Безкрайна тема за статии във външната част
 *
 * @title     Безкрайна CMS тема
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
class cms_InfinityTheme extends cms_FancyTheme
{


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

        $tpl->append(".title {border: 1px solid black;}", 'STYLES');
        $tpl->append(".menu {border: 1px solid red;}", 'STYLES');
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

        foreach ($aArr as $aRec) {
            $menu->append("<span class='menu' id=menu_'{$aRec->id}'> {$aRec->title} </span>");
        }

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
            $content->append("<div class='title' id=title_'{$aRec->id}'>{$aRec->title}</div>");
            $body = cms_Articles::getVerbal($aRec, 'body');
            $content->append("<div class='body' id=body_'{$aRec->id}'>{$body}</div>");
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
