<?php
/**
 * Ръчно задаване на правила за рутиране на имейли
 * 
 * Всеки имейл филтър дефинира шаблон за входящо писмо и действие, което да бъде изпълнено ако
 * когато входящо писмо отговаря на шаблона. Това филтриране на входящата поща се изпълнява
 * точно след опита за рутиране на писмото по номер на нишка (зададен в събджекта на писмото)
 * 
 * Шаблоните се задават като MySQL LIKE изрази, за полетата:
 *  
 *   o изпращач, 
 *   o събджект и 
 *   o текст на писмото
 *   
 * (`%` - произволна последователност от нула или повече символи, `_` - произволен символ.
 * Шаблоните не зависят от големи и малки букви (case insensitive).
 * 
 * Действието, при разпознаване на шаблон може да е едно от:
 * 
 *  o по имейл - това действие подменя реалния изпращач на писмото с имейл адрес намерен някъде 
 *               вътре в събджекта или текста (@see email_Filters::prerouteByEmail())
 *  о в папка  - писмата, отговарящи на шаблона попадат директно в папката зададена в правилото
 *  o спам     - писмата, отговарящи на шаблона се маркират като спам 
 * 
 *
 * @category  bgerp
 * @package   email
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @see       https://github.com/bgerp/bgerp/issues/253
 */
class email_Filters extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_State2, email_Wrapper, email_router_Wrapper, plg_RowTools';
    
    
    /**
     * Заглавие
     */
    var $title    = "Потребителски правила за рутиране";
    
    
    /**
     * 
     */
    var $canList = 'admin, email';
    
    
    /**
     * 
     */
    var $canEdit = 'admin, email';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead   = 'admin';
    
    
    /**
     * Кой има право да пише?
     */
    var $canWrite  = 'admin';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'admin';
    
    
    /**  
     * Кой има право да променя системните данни?  
     */  
    var $canEditsysdata = 'admin';

    
    
    var $listFields =  'id, email, subject, body, action, folderId, note,state';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	//$this->FLD('systemId' ,  'varchar(32)', 'caption=Ключ');
        $this->FLD('email' , 'varchar', 'caption=Условие->Изпращач', array('attr'=>array('style'=>'width: 350px;')));
        $this->FLD('subject' , 'varchar', 'caption=Условие->Относно', array('attr'=>array('style'=>'width: 350px;')));
        $this->FLD('body' , 'varchar', 'caption=Условие->Текст', array('attr'=>array('style'=>'width: 350px;')));
        $this->FLD('action' , 'enum(email=Рутиране по първи външен имейл,folder=Преместване в папка,spam=Маркиране като спам)', 'value=email,caption=Действие->Действие,maxRadio=4,columns=1,notNull');
        $this->FLD('folderId' , 'key(mvc=doc_Folders, select=title, allowEmpty, where=#state !\\= \\\'rejected\\\')', 'caption=Действие->Папка');
        $this->FLD('note' , 'text', 'caption=@Забележка', array('attr'=>array('style'=>'width: 100%;', 'rows'=>4)));

       // $this->setDbUnique('systemId');
    }
    
    
	/**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
 		
		$res .= self::loadData();    
    }
    
    /**
     * Проверява дали входящото писмо се прихваща от един от записаните в този модел филтри.
     * 
     * В случай, че някой от филтрите сработи, се прави описаното в него действие. Действието 
     * може да е едно от:
     * 
     *     o По имейл : имейла на изпращача се подменя с други мейл, намерен в писмото
     *                  (@see email_Filterss::prerouteByEmail()) 
     *     o В папка  : писмото се рутира директно в зададената папка.
     *     o Спам     : писмото се маркира като спам
     *     
     *     
     * Забележка: Този метод е част от процедурата за рутиране на входяща поща. Той се изпълнява
     *            веднага след (неуспешен) опит за рутиране директно в нишка 
     *            (@see email_Router::doByThread()).
     * 
     * @param stdClass $rec запис от модела email_Incomings
     */
    public static function preroute($rec)
    {
        // Търсим услугата, чрез която е изпратено писмото
        $serviceRec = static::detect($rec);
        
        if (!$serviceRec) {
            // не бе разпозната услуга
            return;
        }
        
        switch ($serviceRec->action) {
            case 'email':
                static::prerouteByEmail($rec, $serviceRec);
                break;
            case 'folder':
                $rec->folderId = $serviceRec->folderId;
                break;
            case 'spam':
                $rec->isSpam = TRUE;
                break;
        }

        return $rec->folderId;
    }
    
    
    /**
     * Опит за определяне на имейла на реалния изпращач
     * 
     * Това става като от събджекта ($rec->subject) и текста ($rec->textPart) писмото първо се 
     * извлекат всички имейл адреси, а след това от този списък се премахнат:
     * 
     *  o нашите имейл адреси (имейл адресите на вътрешните кутии в email_Inboxes)
     *  o имейл адресите, които е много вероятно да са на доставчика на публичната услуга
     *  
     * @param stdClass $rec запис от модела email_Incomings
     * @param stdClass $serviceRec запис от модела email_Filters
     */
    protected static function prerouteByEmail($rec, $serviceRec)
    {
        // Приговяме "супа" от избрани полета на входящото писмо
        $soup = implode(' ',
            array(
                $rec->subject, 
                $rec->textPart,
            )
        );
        
        // Извличаме всичко имейл адреси от супата ...
        $emails = type_Email::extractEmails($soup);

        // ... махаме нашите имейли
        $emails = static::filterOurEmails($emails);
        
        // ... и махаме имейлите присъщи на услугата
        $emails = static::filterServiceEmails($emails, $serviceRec);
        
        // Ако нещо е останало ...
        if (count($emails) > 0) {
            
            // Ако първия имейл не се съдържа в изпращача
            if (strpos($rec->fromEml, $emails[0]) === FALSE) {
                
                // Задаваме първия имейл
                $rec->fromName = trim($emails[0]);
            } else {
                
                // Тримваме
                $rec->fromName = trim($rec->fromName);
            }
            
            // Добавяме текста
            $rec->fromName .= ' ' . tr('чрез') . ' ' . $rec->fromEml;
            
            // Задаваме първия имейл
            $rec->fromEml = $emails[0];
        }
    }
    
    
    /**
     * Определя дали писмото отговаря на някой от зададените шаблони
     * 
     * @param stdClass $emailRec запис от модела email_Incomings
     * @return stdClass запис от модела email_Filters или FALSE ако не е разпозната услуга
     */
    protected static function detect($emailRec)
    {
        static $allFilters = NULL;
        
        if (!isset($allFilters)) {
            /* @var $query core_Query */
            $query = static::getQuery();
            
            // Зареждаме всички активни филтри
            $allFilters = $query->fetchAll("#state = 'active'");
        }
        
        if (!$allFilters) {
            // Няма активни филтри
            return FALSE;
        }
        
        $fieldsMap = array(
            'fromEml'  => 'email',
            'subject'  => 'subject',
            'textPart' => 'body',
        );
        
        // Данните, които ще сравняваме с всяко от правилата 
        $subjectData = array();
        
        foreach ($fieldsMap as $emailField => $filterField) {
            $subjectData[$filterField] = $emailRec->{$emailField};
        } 
        
        foreach ($allFilters as $filterRec) {
            if (self::match($subjectData, $filterRec)) {
                return $filterRec;
            }
        }

        // Не е открито съвпадение с никое правило
        return FALSE;
    }
    
    
    /**
     * Провека дали филтриращо правило покрива данните в $subjectData
     * 
     * @param array $subjectData
     * @param stdClass $filterRec запис на модела email_Filters
     * @return boolean
     */
    protected static function match($subjectData, $filterRec)
    {
        foreach ($subjectData as $filterField=>$haystack) {
            if (empty($filterRec->{$filterField})) {
                continue;
            }
            if (mb_stripos($haystack, $filterRec->{$filterField}) === FALSE) {
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    
    /**
     * Премахва нашите имейли от зададен списък с имейли.
     * 
     * "Нашите" имейли са адресите на вътрешните кутии от модела email_Inboxes.
     * 
     * @param array $emails
     * @return array
     */
    protected static function filterOurEmails($emails)
    {
        $emails = array_filter($emails, function ($email) {
            $allInboxes = email_Inboxes::getAllInboxes();
            return !$allInboxes[strtolower(trim($email))];
        });
        
        return array_values($emails);
    }
    
    
    protected static function filterServiceEmails($emails, $serviceRec)
    {
        $self = get_called_class();
        
        return array_filter($emails, function ($email) use ($serviceRec, $self) {
            return !$self::isServiceEmail($email, $serviceRec);
        });
    }
    
    
    public static function isServiceEmail($email, $serviceRec)
    {
        return FALSE; // @TODO
    }
    
    /**
     * Зареждане на потребителски правила за
     * рутиране на имейли според събджект или тяло
     */
    static public function loadData()
    {
       $csvFile = __DIR__ . "/data/Filters.csv";
 	   $ins = 0;
 	   
        if (($handle = @fopen($csvFile, "r")) !== FALSE) {
         
            while (($csvRow = fgetcsv($handle, 2000, ",", '"', '\\')) !== FALSE) {
               
                $rec = new stdClass();
                $rec->email = $csvRow[0]; 
                $rec->subject = $csvRow[1];
                $rec->body = $csvRow[2];
                $rec->action = $csvRow[3];
             	$rec->folderId = $csvRow[4]; 
                $rec->note = $csvRow[5];
                $rec->state = $csvRow[6];
                $rec->createdBy = -1;
                
                $rec->id = self::fetchField("#email = '{$rec->email}' AND #createdBy = '{$rec->createdBy}'");
              
                self::save($rec, NULL, "IGNORE");

                $ins++;
            }
            
            fclose($handle);

            $res .= "<li style='color:green;'>Създадени са записи за {$ins} потребителски правила за рутиране</li>";
        } else {
            $res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }
        
        return $res;
    }

}
