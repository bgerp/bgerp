<?php 


/**
 * Обръщения, които ще се търсят
 * @type type_Set
 */
//defIfNot('EMAIL_SALUTATIONS_BEGIN', 'Здравейте,Здравей,Привет,Скъпи,Скъпа,Скъпо,Уважаеми,Уважаема,Уважаемо,Dear,Gentlemen,Ladies,Hi');
defIfNot('EMAIL_SALUTATIONS_BEGIN', 'Здравей,Привет,Скъп,Уважаем,Dear,Gentlemen,Ladies,Hi');


/**
 * Начални обръщения в изходящите писма, които правим
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Salutations extends core_Manager
{
    
    
    /**
     * Шаблона за обръщение
     */
    static $salutationsPattern;

    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'email_Wrapper, plg_Sorting, plg_Created, plg_RowTools';

    
    /**
     * Заглавие
     */
    var $title = "Обърщение в имейлите";
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, email';
    
    
    /**
     * Кой може да добавя
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой може да го редактира
     */
    var $canEdit = 'admin, email';
    
    
    /**
     * Полетата, които ще се показват
     */
    var $listFields = 'id, folderId, threadId, salutation, userId';
    
    
    /**
     * 
     */
    function description()
    {
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Контейнер,notNull,value=0,input=none');
        $this->FLD('folderId', 'key(mvc=doc_Folders)', 'caption=Папка,notNull,value=0,input=none');
        $this->FLD('threadId', 'key(mvc=doc_Threads)', 'caption=Нишка,notNull,value=0,input=none');
        $this->FLD('userId', 'user', 'caption=Потребител,input=none');
        $this->FLD('salutation', 'varchar', 'caption=Обръщение');
    }
    
    
    /**
     * Връща последното обръщение в нишката или в папката
     * 
     * @param doc_Folders $folderId - id на папка
     * @param doc_Threads $threadId - id на нишка
     * @param core_Users $userId - id на потребител
     */
    public static function get($folderId, $threadId = NULL, $userId = NULL)
    {
        // Ако няма папка
        if (!$folderId) return ;
        
        // Ако няма нишка
        if (!$threadId) {
            
            // Вземаме корицата на папката
            $coverClass = strtolower(doc_Folders::fetchCoverClassName($folderId));

            // Ако корицата не е контрагент, връщаме
            if (($coverClass != 'crm_persons') && ($coverClass != 'crm_companies')) {
                
                return ;
            }
            
            // Ако е потребител
            if ($coverClass == 'crm_persons') {
                
                // id' на корицата
                $coverId = doc_Folders::fetchCoverId($folderId);  
                
                // Ако има потребителски профил, връщаме
                if (crm_Profiles::getProfile($coverId)) return ;      
            }
        }
        
        // Вземаме всички обръщения от папката
        $query = static::getQuery();
        $query->where(array("#folderId = '[#1#]'", $folderId));
        
        // Ако е зададено да се взема от нишката
        if ($threadId) {
            $query->where(array("#threadId = [#1#]", $threadId));    
        }
        
        // Ако е зададено потребител
        if ($userId) {
            $query->where(array("#userId = [#1#]", $userId));
        }
        
        // Само последния запис
        $query->orderBy('createdOn', 'DESC');
        $query->limit(1);
        
        // Вземаме записа
        $rec = $query->fetch();
        
        // Ако има id на нишка, но не сме открили обръщение
        if (!($salutation = $rec->salutation) && $threadId) {
            
            // Вземаме последното обръщение в паката
            $salutation = static::get($folderId, FALSE, $userId);
        }
        
        return $salutation;
    }
    

    /**
     * Създава обръщение
     * 
     * @param email_Outgoings $eRec - Изходящия имейл
     */
    public static function create($eRec)
    {
        // Ако липсва
        if (!$eRec->threadId || !$eRec->folderId || !$eRec->containerId) return ;
        
        // Вземаме обръщенито в текстовата част
        $salutation = static::getSalutations($eRec->body);
        
        // Ако няма връщаме
        if (!$salutation) return ;
        
        // Създаваме запис в модела
        $nRec = new stdClass();
        $nRec->folderId = $eRec->folderId;
        $nRec->threadId = $eRec->threadId;
        $nRec->containerId = $eRec->containerId;
        $nRec->userId = core_Users::getCurrent();
        $nRec->salutation = $salutation;
        
        static::save($nRec);
    }
    
    
    /**
     * Парсира стринга и връща обръщението
     * 
     * @param string $text - Текста, в който ще се търси
     * 
     * @return string  - Обръщението
     */
    static function getSalutations($text)
    {
        // Шаблона за намиране на обръщение в текст
        $pattern = static::getSalutaionsPattern();
        
        // Намираме обърщенито
        preg_match($pattern, $text, $matche);

        // Тримваме и връщаме текста
        return trim($matche['allText']);
    }
    
    
	/**
	 * Връща шаблона за обръщенията
     */
    static function getSalutaionsPattern()
    {
        // Ако не е сетнат шаблона
        if (!isset(static::$salutationsPattern)) {
            
            // Разбиваме текстовете на масив
            $salutationsArr = type_Set::toArray(EMAIL_SALUTATIONS_BEGIN);
            
            // Обхождаме масива
            foreach ($salutationsArr as $salutation) {
                
                // Ако е празен стринг прескачаме
                if (!($salutation = trim($salutation))) continue;
                
                // Ескейпваме текста
                $salutation = preg_quote($salutation, '/');
                
                // Добавяме към шаблона
                $salutationPatter .= ($salutationPatter) ? '|' . $salutation : $salutation;
            }
            
            // Ако има текст за шаблона
            if ($salutationPatter) {
                
                // Добавяме текста в шаблона
                static::$salutationsPattern = "/^(?'space'\s*)(?'allText'(?'salutation'{$salutationPatter})(?'text'.*))/ui";    
            } else {
                
                // Добавяме FALSE, за да не се опитваме да го определим пак
                static::$salutationsPattern = FALSE;
            }
        }
        
        // Връщаме резултата
        return static::$salutationsPattern;
    }
	
	
	/**
     * Променяме folderId на папката
     * 
     * @param doc_Containers $cRec - Запис от doc_Containers
     */
    static function updateRec($cRec)
    {
        // Ако няма containerId не се прави ништо
        if (!$cRec->containerId) return ;

        // Вземаем всички записи от модела от съответния контейнер
        $query = static::getQuery();
        $query->where("#containerId = '{$cRec->containerId}'");
        
        // Обхождаме всички записи
        while($rec = $query->fetch()) {
            
            // Ако се е променило id' то на папката
            if ($rec->folderId != $cRec->folderId) {
                
                // Обновяваме id' то
                $nRec = new stdClass();
                $nRec->id = $rec->id;
                $nRec->folderId = $cRec->folderId;
                
                static::save($nRec);
            }
        }
    }
    
    
    /**
     * 
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Документа
        $doc = doc_Containers::getDocument($rec->containerId);
        
        // Полетата на документа във вербален вид
        $docRow = $doc->getDocumentRow();
        
        // Документа да е линк към single' а на документа
        $row->threadId = ht::createLink(str::limitLen($docRow->title,35), array($doc, 'single', $doc->that), NULL, $attr);
        
        // Записите за папката
        $folderRec = doc_Folders::fetch($rec->folderId);
        
        // Вземаме линка към папката
        $row->folderId = doc_Folders::recToVerbal($folderRec)->title;
    }
}
