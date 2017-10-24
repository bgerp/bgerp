<?php 


/**
 * Входящи документи
 *
 * Създава на документи от файлове.
 *
 * @category  bgerp
 * @package   incoming
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class incoming_Documents extends core_Master
{
    
    /**
     * Старото име на класа
     */
    var $oldClassName = 'doc_Incomings';
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = array(
        
            // Интерфейс за документ
            'doc_DocumentIntf', 
        
            // Интерфейс за създаване на входящ документ
            'fileman_FileActionsIntf',
        );
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Входящи документи';
    
    
    /**
     * @todo Чака за документация...
     */
    var $singleTitle = 'Входящ документ';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, doc';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'powerUser';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'admin, doc';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой има права за
     */
    var $canDoc = 'admin, doc, powerUser';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'incoming_Wrapper, plg_RowTools2, doc_DocumentPlg, doc_plg_SelectFolder,doc_DocumentIntf,
         plg_Printing, plg_Sorting, plg_Search, doc_ActivatePlg, bgerp_plg_Blank,change_Plugin';
    
    
    /**
     * Сортиране по подразбиране по низходяща дата
     */
    var $defaultSorting = 'createdOn=down';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'incoming/tpl/SingleLayoutIncomings.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/page_attach.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "D";
    
    
    /**
     * Полето "Заглавие" да е хипервръзка към единичния изглед
     */
    var $rowToolsSingleField = 'typeId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, typeId, number, date, total, createdOn, createdBy';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'typeId, number, date, total, description';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "18.6|Други";
    
    
    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    var $changableFields = 'fileHnd,typeId,number,date,total,description';
    
    
    /**
     * Кой може да променя записа?
     */
    public $canChangerec = 'powerUser';

    
    /**
     * Описание на модела
     */
    function description()
    {
        // $this->FLD('title', 'varchar', 'caption=Заглавие, width=100%, mandatory, recently');
        $this->FLD("typeId", "key(mvc=incoming_Types,allowEmpty,select=name)", 'caption=Тип,mandatory');
        $this->FLD('fileHnd', 'fileman_FileType(bucket=Documents)', 'caption=Файл, mandatory');
        $this->FLD('number', 'varchar(32)', 'caption=Номер, smartCenter');
        $this->FLD('date', 'date', 'caption=Дата');
        $this->FLD('total', 'double(decimals=2)', 'caption=Сума');
        $this->FLD('description', 'text', 'caption=Описание,oldFiledName=keywords');
        $this->FLD("dataId", "key(mvc=fileman_Data)", 'caption=Данни, input=none');
        $this->FLD('archTomNumber', 'int', 'column=none,caption=Архивиране->Том №,autohide');
        $this->FLD('archTomYear', 'enum(,2016,2017,2018,2019)', 'column=none,caption=Архивиране->Година,autohide');

        $this->setDbUnique('dataId');
    }
    

     
    /**
     * @todo Чака за документация...
     */
    static function on_AfterRenderSingleLayout($mvc, &$tpl, &$data)
    {
        // $tpl->replace(doclog_Documents::getSharingHistory($data->rec->containerId, $data->rec->threadId), 'shareLog');
    }
    

    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
        $title = incoming_Types::fetch($rec->typeId)->name . ' ';
        
        if(strlen($rec->number)) {
            $title .= '№' . $rec->number;
            if(strlen($rec->date)) {
                $title .= ' / ';
            }
        }

        if(strlen($rec->date)) {
            $title .= self::getVerbal($rec, 'date');
        }

        if($escaped) {
            $title = type_Varchar::escape($title);
        }

        return $title;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->title = self::getRecTitle($rec);
    }

    
    /**
     * @todo Чака за документация...
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
         
        // Манупулатора на файла
        $fileHnd = $mvc->db->escape(Request::get('fh'));
        
        
        // Ако създаваме документа от файл
        if (($fileHnd) && (!$data->form->rec->id)) {
            
            // Ескейпваме файл хендлъра
            $fileHnd = $mvc->db->escape($fileHnd);
            
            // Масив с баркодовете
            $text = fileman_Indexes::getTextForIndex( $fileHnd);
            
             
            // Попълваме описанието за файла
            $data->form->setDefault('description', $text);
            
            // Файла да е избран по подразбиране
            $data->form->setDefault('fileHnd', $fileHnd);
            
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        // Ако формата е изпратена
        if (($form->isSubmitted()) && (!$form->rec->id)) {
            
            // id от fileman_Data
            $dataId = fileman_Files::fetchByFh($form->rec->fileHnd, 'dataId');
            
            // Проверяваме да няма създаден документ за съответния запис
            if ($dRec = static::fetch("#dataId = '{$dataId}'")) {
                
                // Съобщение за грешка
                $error = "|Има създаден документ за файла|*";
                
                // Ако имаме права за single на документа
                if ($mvc->haveRightFor('single', $dRec)) {
                    
                    // Заглавието на документа
                    $title = static::getVerbal($dRec, 'typeId');
                    
                    // Създаваме линк към single'a на документа
                    $link = ht::createLink($title, array($mvc, 'single', $dRec->id));
                    
                    // Добавяме към съобщението за грешка самия линк
                    $error .= ": {$link}";
                }
                
                // Задаваме съобщението за грешка
                $form->setError('fileHnd', $error);
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function on_BeforeSave(&$invoker, &$id, &$rec)
    {
        // id от fileman_Data
        $dataId = fileman_Files::fetchByFh($rec->fileHnd, 'dataId');
        $rec->dataId = $dataId;
    }
    
    
    /**
     * Връща прикачения файл в документа
     *
     * @param mixed $rec - id' то на записа или самия запис, в който ще се търси
     *
     * @return arrray - Масив името на файла и манипулатора му (ключ на масива)
     */
    public static function getAttachments($rec)
    {
        // Ако не е обект, тогава вземаме записите за съответния документ
        if (!is_object($rec)) {
            $rec = static::fetch($rec);
        }
        
        // Маниппулатора на файла
        $fh = $rec->fileHnd;
        
        // Вземаме записа на файла
        $fRec = fileman_Files::fetchByFh($fh);
        
        // Масив с манипулатора и името на файла
        $file = array();
        $file[$fh] = $fRec->name;
        
        return $file;
    }
    
    
    /**
     * Реализация  на интерфейсния метод ::getThreadState()
     */
    static function getThreadState($id)
    {
        return 'opened';
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
        // Вземаме записите
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        $row->title = $this->getRecTitle($rec);
        
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        $row->authorId = $rec->createdBy;
        
        $row->state = $rec->state;
        
        $row->recTitle = $this->getRecTitle($rec, FALSE);
       
        return $row;
    }

    
    /**
     * В кои корици може да се вкарва документа
     *
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getCoversAndInterfacesForNewDoc()
    {
        return array('doc_ContragentDataIntf', 'doc_UnsortedFolders');
    }
    
    
    /**
     * Може ли входящ документ да се добави в посочената папка?
     * Входящи документи могат да се добавят само в папки с корица контрагент.
     *
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
        $coverClass = doc_Folders::fetchCoverClassName($folderId);
        
        return cls::haveInterface('doc_ContragentDataIntf', $coverClass) || ($coverClass == 'doc_UnsortedFolders');
    }


    /**
     * Преценява дали файла с посоченото име и дължина може да съдържа документ
     */
    public static function canKeepDoc($fileName, $fileLen)
    {
        static $typeToLen = array();
        if(!count($typeToLen)) {
            $typeToLen = arr::make("pdf=10,doc=10,docx=10,odt=10,xls=10,zip=10,rar=10,txt=1,rtf=2,tiff=20,tff=20,jpg=20,jpeg=20,png=20,bmp=50,csv=1", TRUE);
        }

        $ext = fileman_Files::getExt($fileName);

        if(($minLen = $typeToLen[$ext]) && ($minLen <= $fileLen)) {

            return TRUE;
        }
    }


    /**
     * Интерфейсен метод на fileman_FileActionsIntf
     * 
     * Връща масив с действия, които могат да се извършат с дадения файл
     * 
     * @param stdObject $fRec - Обект са данни от модела
     * 
     * @return array $arr - Масив с данните
     * $arr['url'] - array URL на действието
     * $arr['title'] - Заглавието на бутона
     * $arr['icon'] - Иконата
     */
    static function getActionsForFile($fRec)
    {
        if(self::canKeepDoc($fRec->name, $fRec->fileLen)) {

            $dfRec = doc_files::fetch("#fileHnd = '{$fRec->fileHnd}'");

            // Създаваме масива за съзване на визитка
            $arr = array();
            $inst = cls::get('incoming_Documents');
            $arr['incoming']['url'] = array($inst->className, 'add', 'fh' => $fRec->fileHnd, 'ret_url' => TRUE);
            if($dfRec) {
                $arr['incoming']['url']['defaultFolderId'] = $dfRec->folderId;
            }
            $arr['incoming']['title'] = 'Входящ документ';
            $arr['incoming']['icon'] = $inst->singleIcon;
        }

        return $arr;
    }
    
    
    /**
     * Връща файла, който се използва в документа
     * 
     * @param object $rec
     * 
     * @return array
     */
    function getLinkedFiles($rec)
    {
        $res = array();
        
        $rec = $this->fetchRec($rec);
        
        if (!$rec || !$rec->fileHnd) return $res;
        
        $fRec = fileman_Files::fetchByFh($rec->fileHnd);
        
        if (!$fRec) return $res;
        
        $res[$rec->fileHnd] = fileman_Files::getVerbal($fRec, 'name');
        
        return $res;
    }
    
    
    /**
     * 
     * @param incoming_Documents $mvc
     * @param NULL|string $res
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        // Инсталиране на кофата
        $res .= fileman_Buckets::createBucket('Documents', 'Файлове във входящите документи', NULL, '300 MB', 'user', 'user');
    }
}
