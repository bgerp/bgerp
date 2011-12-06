<?php

/**
 * Клас 'doc_Folders' - Папки с нишки от документи
 *
 * @category   Experta Framework
 * @package    doc
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class doc_Threads extends core_Manager
{   
    var $loadList = 'plg_Created,plg_Rejected,plg_Modified,plg_State,doc_Wrapper, plg_Select';

    var $title    = "Нишки от документи";
    
    var $listFields = 'hnd=Номер,title,author=Автор,createdOn=Създаване,allDocCnt=Документи,last=Последно';

    
    /**
     *
     */
    function description()
    {
        // Информация за нишката
        $this->FLD('folderId' ,  'key(mvc=doc_Folders,select=title,silent)', 'caption=Папки');
        $this->FLD('title' ,  'varchar(128)', 'caption=Заглавие');
        $this->FLD('state' , 'enum(opened,waiting,closed,rejected)', 'caption=Състояние,notNull');
        $this->FLD('allDocCnt' , 'int', 'caption=Брой документи->Всички');
        $this->FLD('pubDocCnt' , 'int', 'caption=Брой документи->Публични');
        $this->FLD('last' , 'datetime', 'caption=Последно');

        // Ключ към първия контейнер за документ от нишката
        $this->FLD('firstContainerId' , 'key(mvc=doc_Containers)', 'caption=Начало,input=none,column=none,oldFieldName=firstThreadDocId');

        // Достъп
        $this->FLD('shared' , 'keylist(mvc=core_Users, select=nick)', 'caption=Споделяне');
        
        // Манипулатор на нишката (thread handle)
        $this->FLD('handle', 'varchar(32)', 'caption=Манипулатор');
    }
    

    /**
     * Подготвя титлата на папката с теми
     */
    function on_AfterPrepareListTitle($mvc, $res, $data)
    {
        expect($data->folderId = Request::get('folderId', 'int'));
        
        $title = new ET("[#user#] » [#folder#]");
        
        $folder = doc_Folders::getTitleById($data->folderId);

        $folderRec = doc_Folders::fetch($data->folderId);

        $title->replace(ht::createLink($folder, array('doc_Threads', 'list', 'folderId' => $data->folderId)), 'folder');

        $user = core_Users::fetchField($folderRec->inCharge, 'nick');

        $title->replace($user, 'user');
        
        $data->title = $title;
    }
    


    /**
     * Филтрира по папка
     */
    function on_AfterPrepareListFilter($mvc, $res, $data)
    {
        expect($folderId = Request::get('folderId', 'int'));
        
        doc_Folders::requireRightFor('single');

        expect($folderRec = $mvc->fetch($folderId));

        doc_Folders::requireRightFor('single', $folderRec);

        $data->query->where("#folderId = {$folderId}");
    }


    /**
     *
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->createdOn = dt::addVerbal($row->createdOn);
        
        $docProxy = doc_Containers::getDocument($rec->firstContainerId);
         
        $docRow = $docProxy->getDocumentRow();

        $attr['class'] .= 'linkWithIcon';
        $attr['style'] = 'background-image:url(' . sbf($docProxy->instance->singleIcon) . ');';

        $row->title = ht::createLink($docRow->title, 
                                     array('doc_Containers', 'list', 
                                           'threadId' => $rec->id, 
                                           'folderId' => $rec->folderId), 
                                     NULL, $attr);

        $row->author = $docRow->author;
  
        $row->hnd = "<div  class='clearfix21'>";
        
        $row->hnd .= "<div class=\"stateIndicator state-{$docRow->state}\">&nbsp;</div>&nbsp;";
        
        $row->hnd .= "<div style='float:right;'>";
        $row->hnd .= $rec->handle ? $rec->handle : $docProxy->getHandle();
        $row->hnd .= '</div>';

        $row->hnd .= '</div>';
     }


    
    /**
     * Създава нов тред
     */
    function create($folderId)
    {
        $rec->folderId = $folderId;

        self::save($rec);

        return $rec->id;
    }


    /**
     * Обновява информацията за дадена тема. 
     * Обикновенно се извиква след промяна на doc_Containers
     */
    function updateThread_($id)
    {
        // Вземаме записа на треда
        $rec = doc_Threads::fetch($id);
        
        $tdQuery = doc_Containers::getQuery();
        $tdQuery->where("#threadId = {$id}");
        $tdQuery->orderBy('#createdOn');

        // Публични документи в треда
        $rec->pubDocCnt = 0;

        while($tdRec = $tdQuery->fetch()) {
            $tdArr[] = $tdRec;
            if($tdRec->state != 'hidden') {
                $rec->pubDocCnt++;
            }
        }
        
        if(count($tdArr)) {
            // Общо документи в треда
            $rec->allDocCnt = count($tdArr);
            
            // Първи документ в треда
            $firstTdRec = $tdArr[0];
            $rec->firstContainerId = $firstTdRec->id;
            $rec->state = $firstTdRec->state;
            
            // Последния документ в треда
            $lastTdRec = $tdArr[$rec->allDocCnt-1];
            $rec->last = $lastTdRec->createdOn;

            doc_Threads::save($rec, 'last, allDocCnt, pubDocCnt, firstContainerId, state');

        } else {
             $this->delete($id);
        }

        doc_Folders::updateFolderByContent($rec->folderId);
    }

    

    /**
     * Само за дебуг
     */
    function act_Update()
    {
        requireRole('admin');
        expect(isDebug());

        $query = $this->getQuery();

        while($rec = $query->fetch()) {
            $this->updateThread($rec->id);
        }
    }


    /**
     *
     */
    function on_AfterPrepareListToolbar($mvc, $res, $data)
    {
        $data->toolbar->addBtn('MO', array('acc_Articles', 'add', 'folderId' => $data->folderId, 'ret_url' => TRUE));
        $data->toolbar->addBtn('LBT', array('lab_Tests', 'add', 'folderId' => $data->folderId, 'ret_url' => TRUE));
    }
    
    
    /**
     * Намира нишка по манипулатор на нишка.
     *
     * @param string $handle манипулатор на нишка
     * @return int key(mvc=doc_Threads) NULL ако няма съответена на манипулатора нишка
     */
    public static function getByHandle($handle)
    {
    	$id = static::fetchField(array("#handle = '[#1#]'", $handle), 'id');
    	
    	if (!$id) {
    		$id = NULL;
    	}
    	
    	return $id;
    }
    
    
    /**
     * Генерира и връща манипулатор на нишка.
     *
     * @param int $id key(mvc=doc_Threads)
     * @return string манипулатора на нишката
     */
    public static function getHandle($id)
    {
    	$rec = static::fetch($id, 'id, handle, firstContainerId');
    	
    	expect($rec);
    	
    	if (!$rec->handle) {
    		expect($rec->firstContainerId);
    		
			$rec->handle = doc_Containers::getHandle($rec->firstContainerId);
	    	
	    	expect($rec->handle);
		    	
	    	// Записваме току-що генерирания манипулатор в данните на нишката. Всеки следващ 
	    	// опит за вземане на манипулатор на тази нишка ще връща тази записана стойност
	    	static::save($rec);
    	}
    	
    	return $rec->handle;
    }

 }
