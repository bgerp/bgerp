<?php


/**
 * Клас 'cms_page_External' - Шаблон за публична страница
 *
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Стандартна публична страница
 */
class cms_page_External extends core_page_Active
{
    /**
     * Имплементирани интерфейси
     */
    public $interfaces = 'cms_page_WrapperIntf';
    
    
    /**
     * Подготовка на външната страница
     * Тази страница използва internal layout, header и footer за да
     * покаже една обща обвивка за съдържанието за вътрешни потребители
     */
    public function prepare()
    {
        // Параметри от конфигурацията
        $conf = core_Packs::getConfig('core');
        $this->prepend(cms_Domains::getSeoTitle(), 'PAGE_TITLE');

        // Ако е логнат потребител
        if (!core_Users::isContractor()) {
            bgerp_Notifications::subscribeCounter($this);
        } elseif(core_Packs::isInstalled('colab')){
            bgerp_Notifications::subscribeCounter($this);
        }

        // Броя на отворените нотификации
        $openNotifications = bgerp_Notifications::getOpenCnt();

        // Ако имаме нотификации, добавяме ги към титлата и контейнера до логото
        if ($openNotifications > 0) {

            // Добавяме броя в заглавието
            $this->append("({$openNotifications}) ", 'PAGE_TITLE');
        }

        // Евентуално се кешират страници за не user
        if (($expires = Mode::get('BrowserCacheExpires')) && !haveRole('user')) {
            $this->push('Cache-Control: public', 'HTTP_HEADER');
            $this->push('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT', 'HTTP_HEADER');
            $this->push('-Pragma', 'HTTP_HEADER');
        } else {
            $this->push('Cache-Control: private, max-age=0', 'HTTP_HEADER');
            $this->push('Expires: -1', 'HTTP_HEADER');
        }
        
        // Добавяме допълнителните хедъри
        $aHeadersArr = core_App::getAdditionalHeadersArr();
        foreach ($aHeadersArr as $hStr) {
            $this->push($hStr, 'HTTP_HEADER');
        }
        
        // Обличаме кожата
        $skin = cms_Domains::getCmsSkin();
        
        $pageTpl = getFileContent(($skin && $skin->layout) ? $skin->layout : 'cms/tpl/Page.shtml');
        
        if (isDebug() && !log_Debug::haveRightFor('list') && Request::get('Debug') && haveRole('debug')) {
            $pageTpl .= '[#Debug::getLog#]';
        }
        
        $this->replace(new ET($pageTpl), 'PAGE_CONTENT');
        if ($skin) {
            $skin->prepareWrapper($this);
        }

        // Скрипт за генериране на min-height, според устройството
        jquery_Jquery::run($this, 'setMinHeightExt();');
        
        // Добавка за разпознаване на браузъра
        $Browser = cls::get('log_Browsers');
        $this->append($Browser->renderBrowserDetectingCode(), 'BROWSER_DETECT');
        
        // Добавяме основното меню
        $this->replace(cms_Content::getMenu(), 'CMS_MENU');
        
        // Добавяме лейаута
        $this->replace(cms_Content::getLayout(), 'CMS_LAYOUT');
        
        // Добавяме лейаута
        $domainRec = cms_Domains::getPublicDomain();
        
        // Къде да добавим линковете
        $footerLinks = cms_Articles::addFooterLinks();
        if (Mode::is('screenMode', 'narrow')) {
            $this->append($footerLinks, 'FOOTER_CENTER_NARROW');
        } else {
            $this->append($footerLinks, 'FOOTER_CENTER_WIDE');
        }
        
        // Ако е логнат потребител, който не е powerUser
        if (core_Users::haveRole('partner')) {
            $this->placeExternalUserData();
        }
        
        $this->invoke('AfterPrepareExternalPage', array(&$this));
    }
    
    
    /**
     * Подготвя данните за контрактора
     */
    private function placeExternalUserData()
    {
        $currentTab = Mode::get('currentExternalTab');
        $Ctr = Request::get('Ctr');

        $selectedProfileClass = in_array($Ctr, array('cms_Profiles', 'colab_Folders', 'colab_Threads', 'colab_Search', 'school_GroupSchedules')) ? 'class=selected-external-tab' : '';
        $selectedBarcodeSearchClass = ($Ctr == 'barcode_Search') ? 'class=selected-external-tab' : '';

        $nick = core_Users::getNick(core_Users::getCurrent());

        $notificationCount = bgerp_Notifications::getOpenCnt();
        if($notificationCount){
            $attr = arr::make("title=Преглед на непрочетените известия");
            if($currentTab == 'colab_Notifications'){
                $attr['class'] = 'selected-external-tab';
            }
            $user = ht::createLink($notificationCount, array('colab_Notifications', 'Show'), false, $attr);
            $this->replace($user, 'USER_NOTIFICATIONS');
        }

        $user = ht::createLink($nick, array('cms_Profiles', 'single'), false, "ef_icon=img/16/user-black.png,title=Към профила,{$selectedProfileClass}");
        $logout = ht::createLink(tr('Изход'), array('core_Users', 'logout'), false, 'ef_icon=img/16/logout.png,title=Изход от системата');

        $barcodeLink = ht::createLink(tr('Баркод'), array('barcode_Search', 'list'), false, "ef_icon=img/16/barcode-icon.png,title=Търсене по баркод,{$selectedBarcodeSearchClass}");
        $this->replace($barcodeLink, 'BARCODE_LINK');
        $this->replace($user, 'USERLINK');
        $this->replace($logout, 'LOGOUT');
        $this->replace("class='cmsTopContractor'", 'TOP_CLASS');
        $this->replace("class='cmsContentContractor'", 'CONTENT_CLASS');
    }
    
    
    /**
     * Рендира менюто във футъра на публичната страница
     *
     * @return core_ET
     */
    public static function renderFooterMenu()
    {
        $conf = core_Packs::getConfig('cms');

        if ($conf->CMS_FOOTER_MENU && $conf->CMS_FOOTER_MENU == 'no') {
            return new ET('');
        }

        $maxItemsInColumn = max(1, (int) $conf->CMS_FOOTER_MAX_ELEMENTS_IN_COLUMN);

        $domainId = cms_Domains::getPublicDomain('id');
        if (!$domainId) {
            return new ET('');
        }

        $menuQuery = cms_Content::getQuery();
        $menuQuery->where("#state = 'active' AND #domainId = {$domainId}");
        $menuQuery->orderBy('#order', 'ASC');

        $html = '';
        while ($menuRec = $menuQuery->fetch()) {
            $menuHtml = self::getFooterMenuItems_($menuRec, $maxItemsInColumn);
            if (!$menuHtml) {
                continue;
            }

            $html .= $menuHtml;
        }

        return new ET($html);
    }


    /**
     * Връща HTML за футъра за съответното меню и източник
     *
     * @param stdClass $menuRec
     * @param int $maxItemsInColumn
     * @return string
     */
    private static function getFooterMenuItems_($menuRec, $maxItemsInColumn)
    {
        $items = array();
        $source = cls::getClassName($menuRec->source, true);

        if ($source && cls::load($source, true) && cls::haveInterface('cms_SourceIntf', $source)) {
            $Source = cls::getInterface('cms_SourceIntf', $source);
            $items = $Source->getFooterMenuItems($menuRec);
        }

        if (!is_array($items) || !count($items)) {
            $url = cms_Content::getContentUrl($menuRec);
            if (!$url) {
                $url = '#';
            }

            $items[] = (object) array(
                'id' => $menuRec->id,
                'parentId' => 0,
                'title' => $menuRec->menu,
                'url' => $url,
            );
        }

        return self::renderFooterMenuColumns_($items, $maxItemsInColumn, $menuRec->menu);
    }


    /**
     * Рендира елементите на футър менюто в колони
     *
     * @param array $items
     * @param int $maxItemsInColumn
     * @param string $menuTitle
     * @return string
     */
    private static function renderFooterMenuColumns_($items, $maxItemsInColumn, $menuTitle)
    {
        if (!is_array($items) || !count($items)) {
            return '';
        }

        $html = '';
        $menuTitle = type_Varchar::escape($menuTitle);
        foreach (array_chunk($items, $maxItemsInColumn) as $columnIndex => $columnItems) {
            $class = 'footer-menu-column';
            $titleClass = '';
            $titleAttr = '';
            if ($columnIndex) {
                $class .= ' footer-menu-column-continuation';
                $titleClass = " class='footer-menu-title-spacer'";
                $titleAttr = " aria-hidden='true'";
            }

            $html .= "
<div class='{$class}'>
    <p{$titleClass}{$titleAttr}>{$menuTitle}</p>
    <ul>";
            foreach ($columnItems as $item) {
                $html .= "
        <li>" . ht::createLink($item->title, $item->url)->getContent() . "</li>";
            }
            $html .= "
    </ul>
</div>";
        }

        return $html;
    }
    
    
    /**
     * Прихваща изпращането към изхода, за да постави нотификации, ако има
     */
    public static function on_Output(&$invoker)
    {
        // Генерираме хедъра и Линка към хедъра
        $invoker->appendOnce(cms_Feeds::generateHeaders(), 'HEAD');
        
        if (!Mode::get('lastNotificationTime')) {
            Mode::setPermanent('lastNotificationTime', time());
        }
        
        // Добавяне на включвания външен код
        cms_Includes::insert($invoker);
    }
}
