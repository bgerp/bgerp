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
    public $loadList = 'plg_Created, plg_State2, email_Wrapper, plg_RowTools, plg_Clone';
    
    
    /**
     * Заглавие
     */
    public $title = 'Потребителски правила за рутиране';

     
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Правило за рутиране';
    
    
    
    public $canList = 'admin, email';
    
    
    
    public $canEdit = 'admin, email';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin';
    
    
    /**
     * Кой има право да пише?
     */
    public $canWrite = 'admin';
    
    
    /**
     * Кой може да го отхвърли?
     */
    public $canReject = 'admin';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'admin';

    
    
    public $listFields = 'id, email, subject, body, action, folderId, note,state';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('systemId', 'varchar(32)', 'caption=Ключ');
        $this->FLD('email', 'varchar', 'caption=Условие->Изпращач', array('attr' => array('style' => 'width: 350px;')));
        $this->FLD('subject', 'varchar', 'caption=Условие->Относно', array('attr' => array('style' => 'width: 350px;')));
        $this->FLD('body', 'varchar', 'caption=Условие->Текст', array('attr' => array('style' => 'width: 350px;')));
        $this->FLD('action', 'enum(email=Рутиране по първи външен имейл,folder=Преместване в папка)', 'value=email,caption=Действие->Действие,maxRadio=4,columns=1,notNull');
        $this->FLD('folderId', 'key(mvc=doc_Folders, select=title, allowEmpty, where=#state !\\= \\\'rejected\\\')', 'caption=Действие->Папка');
        $this->FLD('note', 'text', 'caption=@Забележка', array('attr' => array('style' => 'width: 100%;', 'rows' => 4)));
        
        $this->setDbUnique('systemId');
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
     * @param stdClass $rec        запис от модела email_Incomings
     * @param stdClass $serviceRec запис от модела email_Filters
     */
    protected static function prerouteByEmail($rec, $serviceRec)
    {
        // Приговяме "супа" от избрани полета на входящото писмо
        $soup = implode(
            ' ',
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
            if (strpos($rec->fromEml, $emails[0]) === false) {
                
                // Задаваме първия имейл
                $rec->fromName = trim($emails[0]);
            } else {
                
                // Тримваме
                $rec->fromName = trim($rec->fromName);
            }
            
            // Добавяме текста
            $rec->fromName .= ' чрез ' . $rec->fromEml;
            
            // Задаваме първия имейл
            $rec->fromEml = $emails[0];
        }
    }
    
    
    /**
     * Определя дали писмото отговаря на някой от зададените шаблони
     *
     * @param  stdClass $emailRec запис от модела email_Incomings
     * @return stdClass запис от модела email_Filters или FALSE ако не е разпозната услуга
     */
    protected static function detect($emailRec)
    {
        static $allFilters = null;
        
        if (!isset($allFilters)) {
            /* @var $query core_Query */
            $query = static::getQuery();
            
            // Зареждаме всички активни филтри
            $allFilters = $query->fetchAll("#state = 'active'");
        }
        
        if (!$allFilters) {
            // Няма активни филтри
            return false;
        }
        
        $fieldsMap = array(
            'fromEml' => 'email',
            'subject' => 'subject',
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
        return false;
    }
    
    
    /**
     * Провека дали филтриращо правило покрива данните в $subjectData
     *
     * @param  array    $subjectData
     * @param  stdClass $filterRec   запис на модела email_Filters
     * @return boolean
     */
    public static function match($subjectData, $filterRec)
    {
        foreach ($subjectData as $filterField => $haystack) {
            // Ако няма въведена стойност или са само * или интервали
            if (!strlen(trim($filterRec->{$filterField}, '*')) || !strlen(trim($filterRec->{$filterField}))) {
                continue ;
            }
            
            $pattern = self::getPatternForFilter($filterRec->{$filterField});
            
            // Трябва всички зададени филтри да съвпадат - &
            if (!preg_match($pattern, $haystack)) {
                
                return false;
            }
        }
        
        return true;
    }
    
    
    /**
     * Връща шаблона за търсене с preg
     *
     * @param string $str
     *
     * @return string
     */
    protected static function getPatternForFilter($str)
    {
        static $filtersArr = array();
        
        if ($filtersArr[$str]) {
            
            return $filtersArr[$str];
        }
        
        $pattern = $str;
        
        $pattern = preg_quote($pattern, '/');
        
        $pattern = str_ireplace('\\*', '.{0,1000}', $pattern);
        
        $pattern = '/' . $pattern . '/iu';
        
        $filtersArr[$str] = $pattern;
        
        return $filtersArr[$str];
    }
    
    
    /**
     * Премахва нашите имейли от зададен списък с имейли.
     *
     * "Нашите" имейли са адресите на вътрешните кутии от модела email_Inboxes.
     *
     * @param  array $emails
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
        return false; // @TODO
    }
    
    
    /**
     *
     *
     * @param object $rec
     */
    public static function getSystemId($rec)
    {
        if ($rec->systemId) {
            
            return $rec->systemId;
        }
        
        $str = trim($rec->email) . '|' . trim($rec->subject) . '|' . trim($rec->body) . '|' . trim($rec->action) . '|' . trim((int) $rec->folderId);
        $systemId = md5($str);
        
        return $systemId;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $systemId = $mvc->getSystemId($form->rec);
            
            if ($mvc->fetch(array("#systemId = '[#1#]'", $systemId))) {
                $form->setError('email, subject, body', 'Вече съществува запис със същите данни');
            }
        }
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param email_Filters $mvc
     * @param stdClass      $res
     * @param stdClass      $rec
     */
    public static function on_BeforeSave($mvc, $res, $rec)
    {
        if (!$rec->systemId) {
            $rec->systemId = $mvc->getSystemId($rec);
        }
    }
    
    
    /**
     * Изпълнява се преди импортирването на данните
     */
    public static function on_BeforeImportRec($mvc, $rec)
    {
        if (!$rec->systemId) {
            $rec->systemId = $mvc->getSystemId($rec);
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     *
     * Зареждане на потребителски правила за
     * рутиране на имейли според събджект или тяло
     */
    public function loadSetupData()
    {
        // Подготвяме пътя до файла с данните
        $file = 'email/data/Filters.csv';
        
        // Кои колонки ще вкарваме
        $fields = array(
            0 => 'email',
            1 => 'subject',
            2 => 'body',
            3 => 'action',
            4 => 'folderId',
            5 => 'note',
            6 => 'state',
        );
        
        // Импортираме данните от CSV файла.
        // Ако той не е променян - няма да се импортират повторно
        $cntObj = csv_Lib::importOnce($this, $file, $fields, null, null);
         
        // Записваме в лога вербалното представяне на резултата от импортирането
        return $cntObj->html;
    }
    
    
    /**
     * Преди записване на клонирания запис
     *
     * @param core_Mvc $mvc
     * @param object   $rec
     * @param object   $nRec
     *
     * @see plg_Clone
     */
    public function on_BeforeSaveCloneRec($mvc, $rec, $nRec)
    {
        // Премахваме ненужните полета
        unset($nRec->createdOn);
        unset($nRec->createdBy);
        unset($nRec->state);
    }
}
