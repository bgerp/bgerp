<?php



/**
 * Поддържа системното меню и табове-те на пакета 'log'
 *
 *
 * @category  bgerp
 * @package   log
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class log_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $url = array('log_Documents');
        
        if ($containerId = Request::get('containerId')) {
            $url['containerId'] = $containerId;
        }
        
        $this->TAB(array_merge($url, array('action'=>'send')), 'Изпращания', 'admin, doc');
        $this->TAB(array_merge($url, array('action'=>'print')), 'Отпечатвания', 'admin, doc');
        if (!empty($url['containerId'])) {
            $this->TAB(array_merge($url, array('action'=>'open')), 'Виждания', 'admin, doc');
            
            $urlFiles = $url;
            $urlFiles[0] = 'log_Files';
            $urlFiles['action'] = 'download';
            $this->TAB($urlFiles, 'Файлове', 'admin,doc');
        }
        
        $this->title = 'История';
    }
    
    
}
