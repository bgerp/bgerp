<?php


/**
 * Клас 'vislog_History' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    vislog
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class vislog_History extends core_Manager {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = 'Система';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'История на хитовете';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listItemsPerPage = 40;
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = "Countries=drdata_Countries,IpToCountry=drdata_IpToCountry";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canWrite = "no_one";
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('ip', 'varchar(15)', 'caption=Ip');
        
        $this->FLD('HistoryResourceId', 'key(mvc=vislog_HistoryResources,title=query)', 'caption=Query');
        
        $this->load('plg_Created,vislog_Wrapper,HistoryResources=vislog_HistoryResources,Refferer=vislog_Refferer');
        
        $this->setDbUnique('ip,HistoryResourceId');
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function add($query)
    {
        $rec->query = $query;
        
        $History = cls::get('vislog_History');
        
        $History->save($rec);
    }
    
    
    /**
     *  Извиква се след подготовката на toolbar-а за табличния изглед
     */
    function on_AfterPrepareListToolbar($mvc, $data)
    {
        $data->query->orderBy("createdOn=DESC");
    }
    
    
    /**
     *  Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_BeforeSave($mvc, $id, &$rec)
    {
        // Поставяме IP ако липсва
        if(!$rec->ip) $rec->ip = $_SERVER['REMOTE_ADDR'];
        
        // Съкращаваме заявката, ако е необходимо
        if(strlen($rec->query) > 255) {
            $i = 0; $q = '';
            
            while(strlen($q) <= 255) {
                $q .= mb_substr($rec->query, $i++, 1);
            }
            $rec->query = $q;
        }
        
        $rec->HistoryResourceId = $mvc->HistoryResources->fetchField(array("#query = '[#1#]'", $rec->query), 'id');
        
        if(!$rec->HistoryResourceId) {
            $sRec->query = $rec->query;
            $rec->HistoryResourceId = $mvc->HistoryResources->save($sRec);
        }
        
        $this->Refferer->add($rec->HistoryResourceId);
        
        // Ако имаме такъв запис - връщаме ИСТИНА, за да не продължи обработката
        if($mvc->fetch("#ip = '{$rec->ip}' AND #HistoryResourceId = {$rec->HistoryResourceId}")) {
            return FALSE;
        }
    }
    
    
    /**
     *  Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->ip = ht::createLink($row->ip, "http://bgwhois.com/?query=" . $rec->ip, NULL, array('target' => '_blank'));
        
        $row->ip->prepend($this->IpToCountry->get($rec->ip) . "&nbsp;" );
    }
}