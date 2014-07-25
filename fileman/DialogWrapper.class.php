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
            
            // Вземаме кофата
            $Buckets = cls::get('fileman_Buckets');
            
            $this->info = $Buckets->getAddFileInfo($bucketId);
            
            $tpl->prepend("<button onclick='javascript:window.close();' class='upload-close'>X</button><div class='dialogTitle'>{$this->info->title}</div>");

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
            
           // $tpl->prepend('<br>');
            
            $tpl->prepend($this->info->title . " « " . $conf->EF_APP_TITLE, 'PAGE_TITLE');
            
            $tpl->prepend("<style>
            		
                .fileman_Buckets { background-image:url('" . sbf('img/16/database.png', '') . "');}
                .fileman_Upload { background-image:url('" . sbf('img/16/upload.png', '') . "');}
                .fileman_Get { background-image:url('" . sbf('img/16/world_link.png', '') . "');}
                .empty { background-image:url('" . sbf('img/16/new.png', '') . "');}
                .tab-title.fileman_Log { background-image:url('" . sbf('img/16/databases.png', '') . "');}
    
                </style>");
            
            // Сетвама, таба който сме използвали
            static::setLastUploadTab($invoker->className);
            
            // Добавяме клас към бодито
            $tpl->append('dialog-window', 'BODY_CLASS_NAME');
            
            return TRUE;
        }
    }
    
    
    /**
     * Сетва последно използвания таб
     * 
     * @param string $className - Име на класа
     */
    static function setLastUploadTab($className)
    {
        // Сетваме таба
        Mode::setPermanent('lastUploadTab', $className);
    }
    
    
    /**
     * Връща последно използвания таб
     * 
     * @param unknown_type $callback
     */
    static function getLastUploadTab()
    {
        // Взема последно използвания таб
        $lastUploadTab = Mode::get('lastUploadTab');
        
        // Ако няма
        if (!$lastUploadTab) {
            
            // Качването на файлове да е избран
            $lastUploadTab = 'fileman_Upload';
        }
        
        return $lastUploadTab;
    }
    
    
    /**
     * Прихваща извикването на getActionForAddFile
     * Връща името на екшъна за добавяне на файл
     * 
     * @param unknown_type $mvc
     * @param unknown_type $res
     */
    static function on_AfterGetActionForAddFile($mvc, &$res)
    {
        // Ако не е сетнат
        if (!$res) {
            
            $res = 'Dialog';
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getTabsArr()
    {
        $tabs = array();
        
        // Ако има права за добавяне
        if (fileman_Upload::haveRightFor('add')) {
            $tabs['fileman_Upload'] = 'caption=Качване,Ctr=fileman_Upload,Act=Dialog';
        }
        
        // Ако има права за добавяне
        if (fileman_Get::haveRightFor('add')) {
            $tabs['fileman_Get'] = 'caption=От URL,Ctr=fileman_Get,Act=Dialog';
        }
        
        // Ако има права за листване
        if (fileman_Log::haveRightFor('list')) {
            $tabs['fileman_Log'] = 'caption=Последни,Ctr=fileman_Log,Act=Dialog';
        }
        
        return $tabs;
    }
}