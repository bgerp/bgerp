<?php


/**
 * Клас 'doc_Files' - Всички файлове в документите
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class doc_Files extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'doc_Wrapper, plg_Sorting';
    
    
    /**
     * Заглавие
     */
    var $title = "Файлове в папките";
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'user';
    
    
    /**
     * Кой може да добавя
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой може да го редактира
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Полетата, които ще се показват
     */
    var $listFields = 'fileHnd=Файл, threadId=Документ, date=Дата';
    
    
    /**
     * 
     */
    function description()
    {
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Контейнер,notNull,value=0');
        $this->FLD('folderId', 'key(mvc=doc_Folders)', 'caption=Папка,notNull,value=0');
        $this->FLD('threadId', 'key(mvc=doc_Threads)', 'caption=Нишка,notNull,value=0');
        $this->FLD("fileHnd", "varchar(" . strlen(FILEMAN_HANDLER_PTR) . ")",
            array('notNull' => TRUE, 'caption' => 'Манипулатор'));
        $this->FLD("dataId", "key(mvc=fileman_Data)", 'caption=Данни');
        $this->FNC('date', 'datetime', 'caption=Дата,input=none');
        
        
        $this->setDbUnique('containerId, fileHnd');
    }
    
    
    /**
     * Записваме/Обновяваме всики файлове в модела
     * 
     * @param core_Mvc $invoker - Класа, за който се отнася
     * @param object $rec - Записите за съответния модел
     */
    static function saveFile($invoker, $rec)
    {
        // Вземаме данните
        $id = $rec->id;
        $containerId = $rec->containerId;
        $folderId = $rec->folderId;
        $threadId = $rec->threadId;

        // Очакваме да има id
        expect($id);
        
        // Ако няна контейнер
        if (!$containerId) {
            
            // Намираме контейнера
            $rec = $invoker->fetch($id);
            $containerId = $rec->containerId;
        }
        
        // Очакваме да има такъв запис
        expect($containerId);
        
        // Вземаме всички линкнати файлове
        $linked = (array)$invoker->getLinkedFiles($rec);
        
        // Ако няма папка или тред
        if (!$folderId || !$threadId) {
            
            // Записите за контейнера
            $cRec= doc_Containers::fetch($containerId);
        }
        
        // Ако няма папка
        if (!$folderId) $folderId = $cRec->folderId;
        
        // Ако няма нишка
        if (!$threadId) $threadId = $cRec->threadId;

        // Вземаме всички файлове, за съответния контейнер (когато редактираме запис)
        $query = static::getQuery();
        $query->where("#containerId = '{$containerId}'");
        while ($fRec = $query->fetch()) {
            
            // Всички файлове от предишния запис
            $savedFh[$fRec->fileHnd] = $fRec->fileHnd;
        }
        
        // Обхождаме всички линкнати файлове        
        foreach ($linked as $fh => $name) {
            
            // Ако файла е бил записан
            if ($savedFh[$fh]) {
                
                // Премахваме от масива
                unset($savedFh[$fh]);    
                continue;
            }
            
            // Създаваме запис
            $nRec = new stdClass();
            $nRec->containerId = $containerId;
            $nRec->folderId = $folderId;
            $nRec->threadId = $threadId;
            $nRec->fileHnd = $fh;
            $nRec->dataId = fileman_Files::fetchByFh($fh, 'dataId');
            
            static::save($nRec, NULL, 'IGNORE');
        }
        
        // Ако са останали файлоаве, които не са премахнати от записите
        if (count($savedFh)) {
            
            // Обхождаме всики останали файлове
            foreach ($savedFh as $fileHnd => $dummy) {
                
                // Изтриваме ги от модела
                static::delete("#fileHnd = '{$fileHnd}' AND #containerId = '{$containerId}'");
            }
        }
    }
    
    
    /**
     * Филтрира по папка и ако е указано показва само оттеглените записи
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        // Очакваме да има id на папка
        expect($folderId = $data->listFilter->rec->folderId);
        
        // Очакваме да има такъв запис
        expect($folderRec = doc_Folders::fetch($folderId));

        // Подготвяме филтрите
        doc_Files::applyFilter($data->listFilter->rec, $data->query);
    }
    
    
    /**
     * 
     */
    static function on_AfterPrepareListRecs($mvc, &$res, $data)
    {
        // Ако няма запис, връщаме
        if (!count($data->recs)) return ;
        
        // Обхождаме всички записи
        foreach ($data->recs as $id => $rec) {
            
            // Ако нямаме права за треда
            if (!doc_Threads::haveRightFor('single', $rec->threadId)) unset($data->recs[$id]);
        }
    }

    
    /**
     * 
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('search', 'varchar', 'caption=Ключови думи,input,silent,recently');
        $data->listFilter->setField('folderId', 'input=hidden,silent');
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Търсене', 'default', 'id=filter,class=btn-filter');
        
        $data->listFilter->showFields = 'folderId,search';

        $data->listFilter->input(NULL, 'silent');
    }
    
    
	/**
     * Налага данните на филтъра като WHERE /GROUP BY / ORDER BY клаузи на заявка
     *
     * @param stdClass $filter
     * @param core_Query $query
     */
    static function applyFilter($filter, &$query)
    {
        if (!empty($filter->folderId)) {
            $query->where("#folderId = {$filter->folderId}");
        }
        
        // Името на таблицата
        $tableName = static::getDbTableName();
        
        // Налагане на условията за търсене
        if (!empty($filter->search)) {
            $query->EXT('containerSearchKeywords', 'doc_Containers', 'externalName=searchKeywords');
            $query->where('`' . doc_Containers::getDbTableName() . '`.`id`' . ' = ' . '`' . $tableName . '`.`container_id`');
            
            plg_Search::applySearch($filter->search, $query, 'containerSearchKeywords');
        }
        $query->orderBy('containerId', 'DESC');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Името на файла да е линк към singле' a му
        $row->fileHnd = fileman_Download::getDownloadLink($rec->fileHnd);
        
        // Документа
        $doc = doc_Containers::getDocument($rec->containerId);
        
        // Полетата на документа във вербален вид
        $docRow = $doc->getDocumentRow();
        
        // Атрибутеите на линка
        $attr['class'] = 'linkWithIcon';
        $attr['style'] = 'background-image:url(' . sbf($doc->getIcon()) . ');';
        $attr['title'] = $docRow->title;
        
        // Документа да е линк към single' а на документа
        $row->threadId = ht::createLink(str::limitLen($docRow->title,35), array($doc, 'single', $doc->that), NULL, $attr);
        
        // id' то на контейнера на пъривя документ
        $firstContainerId = doc_Threads::fetchField($rec->threadId, 'firstContainerId');
        
        if ($firstContainerId != $rec->containerId) {
            
            // Първия документ в нишката
            $docProxy = doc_Containers::getDocument($firstContainerId);
            
            // Полетата на документа във вербален вид
            $docProxyRow = $docProxy->getDocumentRow();
            
            // Атрибутеите на линка
            $attr['class'] = 'linkWithIcon';
            $attr['style'] = 'background-image:url(' . sbf($docProxy->getIcon()) . ');';
            $attr['title'] = tr('Първи документ|*: ') . $docProxyRow->title;
            
            // Темата да е линк към single' а на първиа документ документа
            $firstContainerLink = ht::createLink(str::limitLen($docProxyRow->title,35), array($docProxy, 'single', $docProxy->that), NULL, $attr);
            $row->threadId = $row->threadId . " <- " . $firstContainerLink;    
        }
        $fRec = fileman_Files::fetchByFh($rec->fileHnd);
        
        $row->date = fileman_Files::getVerbal($fRec, 'createdOn');;
    }
    
    
    /**
     * Подготвя титлата на папката с теми
     */
    static function on_AfterPrepareListTitle($mvc, &$res, $data)
    {
        // id' то на папката
        $folderId = Request::get('folderId', 'int');
        
        // Записите за файла
        $folderRec = doc_Folders::fetch($folderId);
        
        // Очакваме да има такъв запис
        expect($folderRec);
        
        // Вербалните полета
        $folderRow = doc_Folders::recToVerbal($folderRec);
        
        // Променяме титлата на полето
        $data->title =  "Файлове в папка|* {$folderRow->title}";
    }
    
    
    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако листваме
        if ($action == 'list') {
            
            // Вземамем папката от записа
            $folderId = $rec->folderId;
            
            // Ако не е зададена папка
            if (!$folderId) {
                
                // id' то на папката
                $folderId = Request::get('folderId', 'int');    
            } 

            // Ако нямаме права за signle na папката
            if (!doc_Folders::haveRightFor('single', $folderId)) {
                
                // Нямаме права и за разлгеждането на файловете
                $requiredRoles = 'no_one';   
            }
        }
    }

    
    /**
     * Връща броя на файловете в съответната папка
     * 
     * @param doc_Folders $folderId - id' то на папката, за която търсим
     * 
     * @return integer $count - Броя файловете
     */
    static function getCountInFolder($folderId=NULL) 
    {
        // Ако не е подадено $folderId, използваме id' то на последно активната папка
        $folderId = ($folderId) ? ($folderId) : mode::get('lastfolderId');
        
        // Ако все още няма папка, връщаме 0
        if (!$folderId) return 0;
        
        // Вземаме броя на файловете в папката
        $count = static::count("#folderId = '{$folderId}'");
        
        return $count;
    }
}