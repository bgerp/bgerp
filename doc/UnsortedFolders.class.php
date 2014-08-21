<?php



/**
 * Клас 'doc_UnsortedFolders' - Корици на папки с несортирани документи
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_UnsortedFolders extends core_Master
{
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'accda_DaFolderCoverIntf, price_PriceListFolderCoverIntf, trans_LinesFolderCoverIntf, frame_FolderCoverIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created,plg_Rejected,doc_Wrapper,plg_State,doc_FolderPlg,plg_RowTools,plg_Search';
    
    
    /**
     * Да се създаде папка при създаване на нов запис
     */
    var $autoCreateFolder = 'instant';
    
    
    /**
     * Заглавие
     */
    var $title = "Проекти";
    
    
    /**
     * var $listFields = 'id,title,inCharge=Отговорник,threads=Нишки,last=Последно';
     */
    var $oldClassName = 'email_Unsorted';
    
    
    /**
     * полета от БД по които ще се търси
     */
    var $searchFields = 'name';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Проект';
    
    
    /**
     * Път към картинка 16x16
     */
    var $singleIcon = 'img/16/project-archive.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'doc/tpl/SingleLayoutUnsortedFolder.shtml';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'powerUser';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'powerUser';
    
    
    /**
     * Кой може да го види?
     */
    var $canSingle = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin';
    
    
    /**
     * Кой може да го оттегли?
     */
    var $canReject = 'powerUser';
    
    
    /**
     * Кой може да го възстанови?
     */
    var $canRestore = 'powerUser';
    
    
    /**
     * Кой има права Rip
     */
    var $canWrite = 'powerUser';
    
    
    /**
     * Кои полета можем да редактираме, ако записът е системен
     */
    var $protectedSystemFields = 'none';
    
    
    /**  
     * Кой има право да променя системните данни?  
     */  
    var $canEditsysdata = 'admin';

    
    /**
     * Описание на полетата на модела
     */
    function description()
    {
        $this->FLD('name' , 'varchar(128)', 'caption=Наименование,mandatory');
        $this->FLD('description' , 'richtext', 'caption=Описание');
        $this->FLD('closeTime' , 'time', 'caption=Автоматично затваряне на нишките след->Време');
        $this->setDbUnique('name');
    }
    
    
    /**
     * 
     */
    function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
        // Ако е субмитната формата
        if ($data->form && $data->form->isSubmitted()) {
            
            // Променяма да сочи към single'a
            $data->retUrl = toUrl(array($mvc, 'single', $data->form->rec->id));
        }
    }
    
    
    /**
     * Зареждане на Cron задачите за автоматично затваряне на папка след setup на класа
     *
     * @param core_MVC $mvc
     * @param string $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        $Cron = cls::get('core_Cron');
        
        $rec = new stdClass();
        $rec->systemId = "self_closed_unsorted_folders";
        $rec->description = "Автоматично затваряне на папки";
        $rec->controller = "doc_UnsortedFolders";
        $rec->action = "SelfClosed";
        $rec->period = 24 * 60;
        $rec->offset = 17 * 60;
        $Cron->addOnce($rec);
        
        $res .= "<li style='color:#660000'>Автоматично затваряне на папки по крон</li>";
    }
    
    
    /**
     * Метод за Cron за зареждане на валутите
     */
    function cron_SelfClosed()
    {   
    	// сегашно време в секунди
    	$now = dt::mysql2timestamp(dt::now());
    	// заявка към текущата база
    	$query = $this->getQuery();
    	// заявка към базата на "нишките"
    	$queryThread = doc_Threads::getQuery();
     	
    	// търсим всички проекти, които не са отхвърлени и имат време за автоматично затваряне
        $query->where("#state != 'rejected' AND #closeTime IS NOT NULL");

        while ($rec = $query->fetch()) {
        	// търсим нишка, която отговаря на тази папка и е отворена
        	$queryThread->where("#folderId = '{$rec->folderId}' AND #state = 'opened'");
        	// и я взимаме
        	while ($recThread = $queryThread->fetch()) {
        		// ако тя последно е модифицирана преди (сега - времето за затваряне)
        		if ($recThread->modifiedOn <= dt::timestamp2mysql($now - $rec->closeTime)){
        		// автоматично я затваряме
        			$recThread->state = 'closed';
                   
        			doc_Threads::save($recThread, 'state');
        		}		
        	}
        }
    }

}
