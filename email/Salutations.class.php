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
    var $title = "Обръщение в имейлите";
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, debug, email';
    
    
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
    var $listFields = 'id, folderId, threadId, salutation, lg, userId';
    
    
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
        $this->FLD('lg', 'varchar(2)', 'caption=Език');
    }
    
    
    /**
     * Връща последното обръщение в нишката или в папката
     * 
     * @param doc_Folders $folderId - id на папка
     * @param doc_Threads $threadId - id на нишка
     * @param core_Users $userId - id на потребител
     * @param boolean $depend - Дали да зависи от съдържанието на lg
     * 
     * @return string $salutation - Поздрава
     */
    public static function get($folderId, $threadId = NULL, $userId = NULL, $depend = TRUE)
    {
        // Ако не трябва да извлечем запис
        if (!static::isGoodRec($folderId, $threadId)) return ;

        return static::getRecForField('salutation', $folderId, $threadId, $userId);
    }
    
    
    /**
     * Връща последното обръщение в нишката или в папката
     * 
     * @param doc_Folders $folderId - id на папка
     * @param doc_Threads $threadId - id на нишка
     * @param core_Users $userId - id на потребител
     * @param boolean $depend - Дали да зависи от съдържанието на salutation
     * 
     * @param string $lg - Двубуквения код на езика
     */
    public static function getLg($folderId = NULL, $threadId = NULL, $userId = NULL, $depend = TRUE)
    {
        // Ако не трябва да извлечем запис
        if (!static::isGoodRec($folderId, $threadId)) return ;
        
        return static::getRecForField('lg', $folderId, $threadId, $userId, $depend);
    }
    
    
    /**
     * Проверяваме дали от дадената папка или нишка можем да извлечем съответните данни
     * 
     * @param doc_Folders $folderId - id на папка
     * @param doc_Threads $threadId - id на нишка+
     * 
     * @return boolean - Дали можем да извлечем запис
     */
    static function isGoodRec($folderId = NULL, $threadId = NULL) 
    {
        // Ако няма папка и нишка
        if (!$folderId && !$threadId) return FALSE;
        
        // Опитваме се да вземема папката от нишката
        if (!$folderId) {
            
            // Папката
            $folderId = doc_Threads::fetchField($threadId, 'folderId');
        }

        // Ако не може да се наемери папката
        if (!$folderId) return FALSE;
        
        // Ако няма нишка
        if (!$threadId) {
            
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
                
                // Ако има потребителски профил, връщаме
                if (crm_Profiles::getProfile($coverId)) return FALSE;      
            }
        }
        
        return TRUE;
    }
    
    
    /**
     * Връща най - добрия запис за полето
     * 
     * @param string $field - Името на полето
     * @param doc_Folders $folderId - id на папка
     * @param doc_Threads $threadId - id на нишка
     * @param core_Users $userId - id на потребител
     * @param boolean $depend - Дали да зависи от съдържанието на salutation (за $field=lg) или lg (за $field=salutation)
     * 
     * @param string $fieldRec - Резултата
     */
    protected static function getRecForField($field, $folderId=NULL, $threadId = NULL, $userId = NULL, $depend = TRUE)
    {
        // Вземаме всички обръщения от папката
        $query = static::getQuery();
        
        // Ако е зададена папката
        if ($folderId) {
            $query->where(array("#folderId = '[#1#]'", $folderId));    
        }
        
        // Ако е зададено да се взема от нишката
        if ($threadId) {
            $query->where(array("#threadId = [#1#]", $threadId));    
        }
        
        // Ако е зададено потребител
        if ($userId) {
            $query->where(array("#userId = [#1#]", $userId));
        }
        
        $query->where("#{$field} IS NOT NULL");

        // Само последния запис
        $query->orderBy('createdOn', 'DESC');
        
        // Вземаме записа
        while ($rec = $query->fetch()) {
            
            // Ако зависи от съдържанието
            if ($depend) {
                
                // Ако не е празен стринг lg и salutation
                if (trim($rec->lg) && trim($rec->salutation)) break;    
            } else {
                
                // Ако не е празен стринг {$field}
                if (trim($rec->{$field})) break;
            }
        }

        // Ако има id на нишка, но не сме открили обръщение
        if (!($fieldRec = $rec->{$field}) && $threadId && static::isGoodRec($folderId)) {
            
            // Вземаме последното обръщение в паката
            $fieldRec = static::getRecForField($field, $folderId, FALSE, $userId);
        }
        
        return $fieldRec;
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
        if (!trim($salutation)) $salutation = NULL;
        
        // Създаваме запис в модела
        $nRec = new stdClass();
        $nRec->folderId = $eRec->folderId;
        $nRec->threadId = $eRec->threadId;
        $nRec->containerId = $eRec->containerId;
        $nRec->userId = core_Users::getCurrent();
        $nRec->salutation = $salutation;
        
        //Масив с всички предполагаеми езици
        $lgRates = lang_Encoding::getLgRates($eRec->body);
        
        $nRec->lg = arr::getMaxValueKey($lgRates);

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
        
        // Намираме обръщенито
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
     * Подготвя вербалните стойности за показване
     * Изпълнява се след основния метод за вербализиране
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Документа
        $doc = doc_Containers::getDocument($rec->containerId);
        
        try {
            // Полетата на документа във вербален вид
            $docRow = $doc->getDocumentRow();
            
            // Документа да е линк към single' а на документа
            $row->threadId = ht::createLink(str::limitLen($docRow->title, 35), array($doc, 'single', $doc->that), NULL, $attr);
        } catch(Exception $e) {
            $row->threadId = "<span style='color:red'>" . tr('Проблем с показването') . "</span>";
        }

        try {
            // Записите за папката
            $folderRec = doc_Folders::fetch($rec->folderId);
            
            // Вземаме линка към папката
            $row->folderId = doc_Folders::recToVerbal($folderRec)->title;
        } catch(Exception $e) {
            $row->folderId = "<span style='color:red'>" . tr('Проблем с показването') . "</span>";
        }
    }
}
