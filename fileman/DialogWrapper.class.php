<?php



/**
 * Клас 'fileman_DialogWrapper' - опаковка на пакета 'fileman'
 *
 *
 * @category  vendors
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_DialogWrapper extends core_Plugin
{
    
    
    /**
     * @todo Чака за документация...
     */
    function on_afterRenderDialog($invoker, &$tpl)
    {
        if(strtolower(Request::get('Act')) == 'dialog') {
            
            Mode::set('wrapper', 'fileman_view_DialogWrapper');
            
            $tabs = cls::get('core_Tabs');
            
            Request::setProtected('callback,bucketId');
            
            // Вземаме параметрите от заявката
            $bucketId = Request::get('bucketId', 'int');
            $callback = Request::get('callback', 'identifier');
            
            // Вземаме инфото на обекта, който ще получи файла
            $Files = cls::get('fileman_Files');
            $Buckets = cls::get('fileman_Buckets');
            
            $this->info = $Buckets->getAddFileInfo($bucketId);
            
            $tpl->prepend("<center><h3>{$this->info->title}</h3></center>");
            $tpl->append($this->info->title . " » ", 'PAGE_TITLE');
            $tpl->append("<small><li>" . tr('Макс. размер') . ": {$this->info->maxFileSize}</small>");
            
            if(!$this->info->extensions) $this->info->extensions = '* (' . tr('всички') . ')';
            $tpl->append("<small><li>" . tr('Разширения') . ": {$this->info->extensions}</small>");
            
            $tabArr = $this->getTabsArr();
            
            $url = array(
                'bucketId' => $bucketId,
                'callback' => $callback);
            
            foreach($tabArr as $name => $params) {
                $params = arr::make($params);
                $url['Ctr'] = $params['Ctr'];
                $url['Act'] = $params['Act'];
                $url['selectedTab'] = $name;
                
                $title = $params['caption'];
                
                if($params['icon'] && !Mode::is('screenMode', 'narrow')) {
                    $src = sbf($params['icon'], '');
                    $title = "|*<img src='{$src}' style='margin-right:1px;border:none;'>|$title";
                }
                
                $tabs->TAB($name, $title, $url);
            }
            
            $tpl = $tabs->renderHtml($tpl, $invoker->className);
            
            $tpl->prepend('<br>');
            
            $tpl->append(tr($invoker->title) . " » " . EF_APP_TITLE, 'PAGE_TITLE');
            
            return TRUE;
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function on_AfterRenderHtml($invoker, $tpl)
    {
        $tpl->append($this->info->title, 'PAGE_TITLE');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getTabsArr()
    {
        $tabs = array(
            'fileman_Upload' => 'caption=Качване,Ctr=fileman_Upload,Act=Dialog,icon=fileman/img/upload.gif',
            'fileman_Get' => 'caption=От URL,Ctr=fileman_Get,Act=Dialog,icon=fileman/img/url.gif',
            'fileman_Buckets' => 'caption=Кофи,Ctr=fileman_Buckets,Act=Browse,icon=fileman/img/folder.gif',
            'empty' => 'caption=Нов,Ctr=fileman_Empty,Act=CreateNewFile,icon=fileman/img/new.gif'
        );
        
        return $tabs;
    }
}