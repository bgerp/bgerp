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
    	$conf = core_Packs::getConfig('core');
    	
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
            
            $tpl->prepend("<h4>{$this->info->title}</h4>");

            $tpl->append("<ul><small><li>" . tr('Макс. размер') . ": {$this->info->maxFileSize}</li></small>");
            
            if(!$this->info->extensions) $this->info->extensions = '* (' . tr('всички') . ')';
            
            $tpl->append("<small><li>" . tr('Разширения') . ": {$this->info->extensions}</li></small></ul>");
            if($this->info->accept) {
                $tpl->replace("accept=\"{$this->info->accept}\"", 'ACCEPT');
            }

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
                    $title = "$title";
                }
                
                $tabs->TAB($name, $title, $url, $name);
            }
            
            $tabs->htmlClass = 'filemanUpload';

            $tpl = $tabs->renderHtml($tpl, $invoker->className);
            
            $tpl->prepend('<br>');
            
            $tpl->prepend(tr($this->info->title) . " « " . $conf->EF_APP_TITLE, 'PAGE_TITLE');
            
            $tpl->prepend("<style> 
            .filemanUpload .tab-title {
                    padding-left:20px;  
                    background-repeat:no-repeat;
                    background-position: 0px center;
                    font-size:1.1em;
            }
            .fileman_Buckets { background-image:url('" . sbf('img/16/database.png', '') . "');}
            .fileman_Upload { background-image:url('" . sbf('img/16/upload.png', '') . "');}
            .fileman_Get { background-image:url('" . sbf('img/16/world_link.png', '') . "');}
            .empty { background-image:url('" . sbf('img/16/new.png', '') . "');}

            </style>");
            return TRUE;
        }
    }


    /**
     * @todo Чака за документация...
     */
    function getTabsArr()
    {
        $tabs = array(
            'fileman_Upload' => 'caption=Качване,Ctr=fileman_Upload,Act=Dialog,icon=fileman/img/upload.gif',
            'fileman_Get' => 'caption=От URL,Ctr=fileman_Get,Act=Dialog,icon=fileman/img/url.gif',
            'fileman_Buckets' => 'caption=Кофи,Ctr=fileman_Buckets,Act=Browse,icon=img/16/database.png',
            'empty' => 'caption=Нов,Ctr=fileman_Empty,Act=CreateNewFile,icon=fileman/img/new.gif'
        );
        
        return $tabs;
    }
}