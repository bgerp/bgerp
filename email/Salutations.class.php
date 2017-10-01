<?php 


/**
 * Обръщения, които ще се търсят
 * Включва 'Здравейте,Здравей,Привет,Скъпи,Скъпа,Скъпо,Уважаеми,Уважаема,Уважаемо,Dear,Gentlemen,Ladies,Hi;
 */
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
    protected static $salutationsPattern;

    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'email_Wrapper, plg_Sorting, plg_Created, plg_RowTools2, plg_Search, plg_State';

    
    /**
     * Заглавие
     */
    public $title = "Обръщение в имейлите";
    
    
    /**
     * Кой може да го разглежда?
     */
    protected $canList = 'admin, debug, email';
    
    
    /**
     * Кой може да добавя
     */
    protected $canAdd = 'no_one';
	
    
    /**
     * Кой може да го редактира
     */
    protected $canEdit = 'admin, email';
    
    
    /**
     * Полетата, които ще се показват
     */
    public $listFields = 'id, folderId, threadId, salutation, toEmail, userId';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене
     * @see plg_Search
     */
    public $searchFields = 'salutation, toEmail';
    
    
    /**
     * 
     */
    public function description()
    {
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Контейнер,input=none');
        $this->FLD('folderId', 'key(mvc=doc_Folders)', 'caption=Папка,input=none');
        $this->FLD('threadId', 'key(mvc=doc_Threads)', 'caption=Нишка,input=none');
        $this->FLD('userId', 'user', 'caption=Потребител,input=none');
        $this->FLD('salutation', 'varchar', 'caption=Обръщение');
        $this->FLD('toEmail', 'emails', 'caption=Имейл');
        
        $this->setDbUnique('containerId');
        $this->setDbIndex('createdBy');
    }
    

    /**
     * Добавя обръщение
     * 
     * @param object $eRec - Изходящия имейл
     */
    public static function add($eRec)
    {
        // Ако липсва
        if (!$eRec->threadId || !$eRec->folderId || !$eRec->containerId) return ;
        
        // За чернови документи да не се записва
//         if ($eRec->state == 'draft') return ;
        
        // Вземаме обръщенито в текстовата част
        $salutation = self::getSalutations($eRec->body);
        
        // Ако няма обръщение да не се записва празен запис
        if (!trim($salutation)) return ;
        
        // Ако няма такъв запис
        if (!($nRec = self::fetch("#containerId = '{$eRec->containerId}'"))) {
            // Създаваме запис в модела
            $nRec = new stdClass();
        }
        
        $nRec->folderId = $eRec->folderId;
        $nRec->threadId = $eRec->threadId;
        $nRec->containerId = $eRec->containerId;
        $nRec->userId = core_Users::getCurrent();
        $nRec->salutation = $salutation;
        $nRec->toEmail = $eRec->email;
        $nRec->state = $eRec->state;
        
        self::save($nRec, NULL, 'UPDATE');
    }
    
    
    /**
     * Връща потребителя, за обръщението в контейнера
     * 
     * @param integer $cId
     * 
     * @return FALSE|int
     */
    public static function getUserId($cId)
    {
        if (!$cId) return FALSE;
        
        $rec = self::fetch(array("#containerId = '[#1#]'", $cId));
        
        if (!$rec) return FALSE;
        
        return $rec->userId;
    }
    
    
    /**
     * Връща последното обръщение в нишката или в папката
     * 
     * @param doc_Folders $folderId - id на папка
     * @param doc_Threads $threadId - id на нишка
     * @param stribg $email - Имейл
     * @param core_Users $userId - id на потребител
     * 
     * @return NULL|string - Поздрава
     */
    public static function get($folderId, $threadId=NULL, $email=NULL, $userId=NULL)
    {
        // Ако не трябва да извлечем запис
        if (!self::isGoodRec($folderId, $threadId, $email)) return ;
        
        // Вземаме всички обръщения от папката
        $query = self::getQuery();
        
        // Ако е зададена папката
        $query->where(array("#folderId = '[#1#]'", $folderId));
        
        // Да не се връщат оттеглените
        $query->where("#state != 'rejected'");
        
        // Ако е зададено потребител
        if ($userId) {
            $query->where(array("#userId = '[#1#]'", $userId));
        }
        
        // Подреждаме по създаване
        $query->orderBy('createdOn', 'DESC');
        
        // Ако е подадене нишка, опитваме да извлечен поздрава от нишката
        if ($threadId) {
            $thClone = clone $query;
            $thClone->where(array("#threadId = '[#1#]'", $threadId));
            
            $salutation = self::getSalutationFromQuery($thClone, $email);
            
            if ($salutation) return $salutation;
        }
        
        // Ако е подадена нишка, но не сме открили обръщението в нишката
        if ($threadId) {
            // Проверяваме дали може да се използва записа за папката
            if (!self::isGoodRec($folderId, NULL, $email)) return ;
        }
        
        // Намираме последното обръщение в поздрава
        $salutation = self::getSalutationFromQuery($query, $email);
        
        return $salutation;
    }
    
    
    /**
     * Връща обръщениет от заявката
     * 
     * @param core_Query $query
     * @param string $email
     * 
     * @return boolean|string
     */
    protected static function getSalutationFromQuery($query, $email=NULL)
    {
        if (!$query) return FALSE;
        
        $salutation = '';
        
        // Вземаме записа
        while ($rec = $query->fetch()) {
            
            if (!trim($rec->salutation)) continue;
            
            // Ако има обръщение да не се променя
            if (!$salutation) {
                $salutation = $rec->salutation;
            }
            
            // Ако е подаден имейл, с по-голям приоритет е, ако отговаря на имейла
            if ($email) {
                if ($rec->toEmail == $email) {
                    $salutation = $rec->salutation;
                    break;
                }
            } else {
                break;
            }
        }
        
        return $salutation;
    }
    
    /**
     * Проверяваме дали от дадената папка или нишка можем да извлечем съответните данни
     * От всички нишки може. Ако не е подадена нишка, само от папки с корица контрагент
     * 
     * @param doc_Folders $folderId - id на папка
     * @param doc_Threads $threadId - id на нишка
     * @param string $email - имейл
     * 
     * @return boolean - Дали можем да извлечем запис
     */
    protected static function isGoodRec($folderId=NULL, $threadId=NULL, $email=NULL) 
    {
        // Ако няма папка и нишка
        if (!$folderId && !$threadId) return FALSE;
        
        // Опитваме се да вземема папката от нишката
        if (!$folderId) {
            
            // Папката
            $folderId = doc_Threads::fetchField($threadId, 'folderId');
        }

        // Ако не може да се намери папката
        if (!$folderId) return FALSE;
        
        // Ако няма нишка
        if (!$threadId) {
            
            // Ако има имейл
            if ($email) {
                
                // Намираме последното обръщение от папката към съответния имейл от текущия потребител
                $userId = core_Users::getCurrent();
                $query = self::getQuery();
                $query->where("#folderId = '{$folderId}'");
                $query->where(array("#toEmail = '[#1#]'", $email));
                $query->where("#createdBy = '{$userId}'");
                $query->orderBy('createdOn', 'DESC');
                $query->limit(1);
                
                // Ако има обръщение, проверяваме дали не е много старо
                if ($rec = $query->fetch()) {
                    $conf = core_Packs::getConfig('email');
                    $now = dt::now();
                    $secsDiff = dt::secsBetween($now, $rec->createdOn);
                    if ($conf->EMAIL_SALUTATION_EMAIL_TIME_LIMIT > $secsDiff) return TRUE;
                }
            }
            
            // Вземаме корицата на папката
            $coverClass = strtolower(doc_Folders::fetchCoverClassName($folderId));
            
            // Ако корицата не е контрагент, връщаме
            if (($coverClass != 'crm_persons') && ($coverClass != 'crm_companies')) {
                
                return FALSE;
            }
            
            // Ако е потребител
            if ($coverClass == 'crm_persons') {
                
                // id' на корицата
                $coverId = doc_Folders::fetchCoverId($folderId);  
                
                // Ако има потребителски профил
                if (crm_Profiles::fetch("#personId = '{$coverId}'")) return FALSE;      
            }
        }
        
        return TRUE;
    }
    
    
    /**
     * Парсира стринга и връща обръщението
     * 
     * @param string $text - Текста, в който ще се търси
     * 
     * @return string  - Обръщението
     */
    protected static function getSalutations($text)
    {
        // Шаблона за намиране на обръщение в текст
        $pattern = self::getSalutaionsPattern();
        
        // Намираме обръщенито
        preg_match($pattern, $text, $matche);
 
        // Тримваме и връщаме текста
        return trim($matche['allText']);
    }
    
    
	/**
	 * Връща шаблона за обръщенията
     */
    protected static function getSalutaionsPattern()
    {
        // Ако не е сетнат шаблона
        if (!isset(self::$salutationsPattern)) {
            
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
                self::$salutationsPattern = "/^(?'space'\s*)(?'allText'(?'salutation'{$salutationPatter})(?'text'.*))/ui";    
            } else {
                
                // Добавяме FALSE, за да не се опитваме да го определим пак
                self::$salutationsPattern = FALSE;
            }
        }
      
        // Връщаме резултата
        return self::$salutationsPattern;
    }
    
    
    /**
     * Подготвя вербалните стойности за показване
     * Изпълнява се след основния метод за вербализиране
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        try {
            // Документа
            $doc = doc_Containers::getDocument($rec->containerId);
            
            // Полетата на документа във вербален вид
            $docRow = $doc->getDocumentRow();
            
            // Документа да е линк към single' а на документа
            $row->threadId = ht::createLink(str::limitLen($docRow->title, 35), $doc->instance->getSingleUrlArray($doc->that), NULL);
        } catch(core_exception_Expect $e) {
            $row->threadId = "<span style='color:red'>" . tr('Проблем с показването') . ' #' . $rec->containerId . "</span>";
        }

        try {
            // Записите за папката
            $folderRec = doc_Folders::fetch($rec->folderId);
            
            // Вземаме линка към папката
            $row->folderId = doc_Folders::recToVerbal($folderRec)->title;
        } catch(core_exception_Expect $e) {
            $row->folderId = "<span style='color:red'>" . tr('Проблем с показването') . ' #' . $rec->folderId . "</span>";
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     * 
     * @param email_Salutations $mvc
     * @param object $data
     */
	static function on_AfterPrepareListFilter($mvc, &$data)
	{
        $data->listFilter->view = 'horizontal';
    	$data->listFilter->showFields = 'search';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        
	    $data->query->orderBy('createdOn', 'DESC');
	}
}
