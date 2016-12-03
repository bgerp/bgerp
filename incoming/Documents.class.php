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
            'incoming_CreateDocumentIntf',
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
    var $loadList = 'incoming_Wrapper, plg_RowTools, doc_DocumentPlg, doc_plg_BusinessDoc,doc_DocumentIntf,
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
    var $searchFields = 'typeId, fileHnd, number, date, total, description';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "18.6|Други";
    
    
    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    var $changableFields = 'fileHnd,typeId,number,date,total,description';

    
    /**
     * Описание на модела
     */
    function description()
    {
        // $this->FLD('title', 'varchar', 'caption=Заглавие, width=100%, mandatory, recently');
        $this->FLD("typeId", "key(mvc=incoming_Types)", 'caption=Тип,mandatory');
        $this->FLD('fileHnd', 'fileman_FileType(bucket=Documents)', 'caption=Файл, mandatory');
        $this->FLD('number', 'varchar(32)', 'caption=Номер, smartCenter');
        $this->FLD('date', 'date', 'caption=Дата');
        $this->FLD('total', 'double(decimals=2)', 'caption=Сума');
        $this->FLD('description', 'richtext(bucket=Notes)', 'caption=Описание,oldFiledName=keywords');
        $this->FLD("dataId", "key(mvc=fileman_Data)", 'caption=Данни, input=none');
        
        $this->setDbUnique('dataId');
    }
    

    function act_Test()
    {
        incoming_Setup::addTypes();
    }
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterRenderSingleLayout($mvc, &$tpl, &$data)
    {
        // $tpl->replace(doclog_Documents::getSharingHistory($data->rec->containerId, $data->rec->threadId), 'shareLog');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
         
        // Манупулатора на файла
        $fileHnd = $mvc->db->escape(Request::get('fh'));
        
        // Вземаме текстовата част
        // TODO може и да се направи форматиране - Интервалите да се заменят с един
        // може и повтарящите думи да се премахнат
        $content = trim(fileman_Indexes::getInfoContentByFh($fileHnd, 'text'));
        
        // Вземаме текста извлечен от OCR
        $contentOcr = trim(fileman_Indexes::getInfoContentByFh($fileHnd, 'textOcr'));
        
        // Ключовите думи ги вземаме от OCR текста, ако няма тогава от обикновенния
        $keyWords = ($contentOcr) ? $contentOcr : $content;
        
        // Ако създаваме документа от файл
        if (($fileHnd) && (!$data->form->rec->id)) {
            
            // Ескейпваме файл хендлъра
            $fileHnd = $mvc->db->escape($fileHnd);
            
            // Масив с баркодовете
            $barcodesArr = fileman_Indexes::getInfoContentByFh($fileHnd, 'barcodes');
            
             
            // Попълваме описанието за файла
            $data->form->setDefault('description', $keyWords);
            
            // Файла да е избран по подразбиране
            $data->form->setDefault('fileHnd', $fileHnd);
            
            // Файла да е само за четене
            //            $data->form->setReadOnly('fileHnd'); // TODO след като се промени core_FieldSet
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
     * Връща ключовите думи на документа
     * @todo Да се реализира
     *
     * @return;
     */
    static function getKeywords($fileHnd)
    {
        
        return "test {$fileHnd}";
    }
    
    
 
    
    /**
     * Връща прикачения файл в документа
     *
     * @param mixed $rec - id' то на записа или самия запис, в който ще се търси
     *
     * @return arrray - Масив името на файла и манипулатора му (ключ на масива)
     */
    function getAttachments($rec)
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
        
        $row->title = $this->getVerbal($rec, 'typeId');
        
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        $row->authorId = $rec->createdBy;
        
        $row->state = $rec->state;
        
        $row->recTitle = $this->getVerbal($rec, 'typeId');
       
        return $row;
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        // Инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('Documents', 'Файлове във входящите документи', NULL, '300 MB', 'user', 'user');
    }
    
    
    /**
     * Връща файла, който се използва в документа
     *
     * @param object $rec - Запис
     */
    function getLinkedFiles($rec)
    {
        // Ако не е обект
        if (!is_object($rec)) {
            
            // Извличаваме записа
            $rec = $this->fetch($rec);
        }
        
        // Вземаме записите за файла
        $fRec = fileman_Files::fetchByFh($rec->fileHnd);
        
        // Добавяме в масива манипулатора и името на файла
        $fhArr[$rec->fileHnd] = fileman_Files::getVerbal($fRec, 'name');
        
        return $fhArr;
    }
    
    
    /**
     * Показва меню от възможности за създаване на входящи документие
     */
    function act_ShowDocMenu()
    {
        // Манипулатора на файла
        $fh = Request::get('fh');
        
        // Очаква да има такъв манипулатор
        expect($fh);
        
        // Очакваме да има такъв файл
        expect($fRec = fileman_Files::fetchByFh($fh));
        
        // Изискваме да има права за single на файла
        fileman_Files::requireRightFor('single', $fRec);
        
        // Шаблон
        $tpl = new ET();
        
        // Създаваме заглавие
        $tpl->append("\n<h3>" . tr('Създаване на входящ документ') . ":</h3>");
        
        // Създаваме таблица в шаблона
        $tpl->append("\n<table>");
        
        // Вземаме всички класове, които имплементират интерфейса
        $classesArr = core_Classes::getOptionsByInterface('incoming_CreateDocumentIntf');
 
        // Обхождаме всички класове, които имплементират интерфейса
        foreach ($classesArr as $className) {
            
            // Вземаме масива с документите, които може да създаде
            $arrCreate = $className::canCreate($fRec);
            
            // Обхождаме масива
            foreach ((array)$arrCreate as $arr) {
                
                // Ако има полета, създаваме бутона
                if (count($arr)) {
                    $tpl->append("\n<tr><td>");
                    $tpl->append(ht::createBtn($arr['title'], array($arr['class'], $arr['action'], 'fh' => $fh, 'ret_url' => TRUE), NULL, NULL, "ef_icon=" .  $arr['icon'] . ",style=width:100%;text-align:left;"));
                    $tpl->append("</td></tr>");
                }
            }
        }
        
        // Добавяме края на таблицата
        $tpl->append("\n</table>");
        
        return $this->renderWrapping($tpl);
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     *
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
        return array('doc_ContragentDataIntf');
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
        
        return cls::haveInterface('doc_ContragentDataIntf', $coverClass);
    }


    /**
     * Метод на интерфейса incoming_CreateDocumentIntf
     *
     * Връща масив, от който се създава бутона за създаване на входящ документ
     * 
     * @param fileman_Files $rec - Обект са данни от модела
     * 
     * @return array $arr - Масив с данните
     * $arr['class'] - Името на класа
     * $arr['action'] - Екшъна
     * $arr['title'] - Заглавието на бутона
     * $arr['icon'] - Иконата
     */
    public static function canCreate($p1)
    {
        return TRUE;
    }
}