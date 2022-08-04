<?php


/**
 * Потребителски правила за СПАМ рейтинг
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class email_ServiceRules extends embed_Manager
{


    /**
     * @var string
     */
    public $recTitleTpl = ' ';


    /**
     * @var string
     */
    public $driverClassCaption = 'Действие';


    /**
     * @var string
     */
    public $driverInterface = 'email_ServiceRulesIntf';


    /**
     * Инрерфейси
     */
    public $interfaces = 'email_AutomaticIntf';
    
    
    /**
     * @see email_AutomaticIntf
     */
    public $weight = 150;
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_State2, email_Wrapper, plg_RowTools, plg_Clone';
    
    
    /**
     * Заглавие
     */
    public $title = 'Потребителски правила за сервизни имейли';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Правило за обработка на сервизни имейли';
    
    
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
    
    
    public $listFields = 'id, driverClass, email, emailTo, subject, body, note, createdOn, createdBy, state';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'createdOn, createdBy, state, systemId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('systemId', 'varchar(32)', 'caption=Ключ,input=none');
        $this->FLD('email', 'varchar', 'caption=Условие->Изпращач, silent', array('attr' => array('style' => 'width: 350px;')));
        $this->FLD('emailTo', 'varchar', 'caption=Условие->Получател, silent', array('attr' => array('style' => 'width: 350px;')));
        $this->FLD('subject', 'varchar', 'caption=Условие->Относно, silent', array('attr' => array('style' => 'width: 350px;')));
        $this->FLD('body', 'varchar', 'caption=Условие->Текст, silent', array('attr' => array('style' => 'width: 350px;')));
        $this->FLD('note', 'text', 'caption=Забележка', array('attr' => array('style' => 'width: 100%;', 'rows' => 4)));
        
        $this->setDbUnique('systemId');
    }
    
    
    /**
     * Проверява дали в $mime се съдържа спам писмо и ако е
     * така - съхранява го за определено време в този модел
     *
     * @param email_Mime  $mime
     * @param integer $accId
     * @param integer $uid
     *
     * @return string|null
     *
     * @see email_AutomaticIntf
     */
    public function process($mime, $accId, $uid)
    {
        $sDataArr = array();
        $sDataArr['body'] = $mime->textPart;
        $sDataArr['email'] = $mime->getFromEmail();
        $sDataArr['subject'] = $mime->getSubject();
        $sDataArr['emailTo'] = $mime->getToEmail();
        
        static $allFilters = null;
        
        if (!isset($allFilters)) {
            $query = static::getQuery();
            $query->orderBy('createdOn', 'DESC');
            
            $query->where("#state = 'active'");
            
            // Зареждаме всички активни филтри
            $allFilters = $query->fetchAll();
        }

        if (!$allFilters) {
            
            return ;
        }

        $pRes = null;

        $haveMatch = false;

        foreach ($allFilters as $filterRec) {

            $classIdFld = $this->driverClassField;

            if (email_ServiceRules::match($sDataArr, $filterRec)) {

                if (!$filterRec->{$classIdFld}) {
                    
                    $this->logDebug("Липсва {$classIdFld}", null, 3);
                    
                    continue;
                }

                if (!cls::load($filterRec->{$classIdFld}, true)) {

                    continue;
                }

                $sInst = cls::getInterface('email_ServiceRulesIntf', $filterRec->$classIdFld);

                try {
                    $pRes = $sInst->process($mime, $filterRec);
                } catch (core_exception_Expect $e) {
                    reportException($e);
                    continue;
                } catch (Exception $e) {
                    reportException($e);
                    continue;
                } catch (Throwable $e) {
                    reportException($e);
                    continue;
                }

                $haveMatch = true;

                if (isset($pRes)) {
                    if (!is_array($pRes)) {
                        email_ServiceRulesData::add($mime, $accId, $uid, $filterRec->id);
                    }

                    break;
                }
            }
        }

        if ($haveMatch) {
            if (!isset($pRes)) {
                $pRes = array();
            }

            if (is_array($pRes)) {
                $pRes['spam']['checkSpam'] = false;
            }
        }
        
        return $pRes;
    }
    
    
    /**
     *
     *
     * @param object $rec
     */
    public static function getSystemId($rec, $force = false)
    {
        if (!$force) {
            if ($rec->systemId) {
                
                return $rec->systemId;
            }
        }
        
        $str = trim($rec->email) . '|' . trim($rec->subject) . '|' . trim($rec->body) . '|' . trim($rec->emailTo);
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
        $fArr = array('email', 'subject', 'body', 'emailTo');
        
        if ($form->isSubmitted()) {
            $systemId = $mvc->getSystemId($form->rec, true);
            $oRec = $mvc->fetch(array("#systemId = '[#1#]'", $systemId));
            if ($oRec && ($oRec->id != $form->rec->id)) {
                $form->setError($fArr, 'Вече съществува запис със същите данни');
            }
        }
        
        if ($form->isSubmitted()) {
            foreach ($fArr as $fName) {
                if (strlen(trim($form->rec->{$fName}, '*')) && strlen(trim($form->rec->{$fName}))) {
                    $haveVal = true;
                }
            }
            if (!$haveVal) {
                $form->setError($fArr, 'Едно от полетата трябва да има стойност');
            }
        }
    }


    /**
     * След обработка на лист филтъра
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->query->orderBy('createdOn', 'DESC');
        $data->query->orderBy('id', 'DESC');

        $driverClassField = $mvc->driverClassField;

        // Добавяме поле във формата за търсене
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');

        // Показваме само това поле. Иначе и другите полета
        // на модела ще се появят
        $data->listFilter->showFields = $driverClassField;
        $data->listFilter->input(null, 'silent');

        if ($data->listFilter->rec->{$driverClassField}) {
            $data->query->where(array("#{$driverClassField} = '[#1#]'", $data->listFilter->rec->{$driverClassField}));
        }
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $mvc->addInfoToForm($data->form);
    }


    /**
     * Добавя инфо към формата
     *
     * @param core_Form $form
     */
    public static function addInfoToForm(&$form)
    {
        $form->info = tr("Търси се пълно съвпадени в зададените условия.");
        $form->info .= "<br>";
        $form->info .= tr("Със звезда (*) може да се зададат  неограничен брой символи в началото, края или по средата");
        $form->info .= "<br>";
        $form->info .= tr("Пример 1: *Експерта * документ номер *");
        $form->info .= "<br>";
        $form->info .= tr("Пример 2: *@experta.bg");
    }

    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param email_ServiceRules $mvc
     * @param stdClass      $res
     * @param stdClass      $rec
     *
     * @return NULL|float
     */
    public static function on_BeforeSave($mvc, $res, $rec)
    {
        if (!$rec->systemId) {
            $rec->systemId = $mvc->getSystemId($rec);
        }
    }


    /**
     * Провека дали филтриращо правило покрива данните в $subjectData
     *
     * @param array    $subjectData
     * @param stdClass $filterRec   запис на модела
     *
     * @return bool
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

        $pattern = str_ireplace('\\*', '.{0,10000}', $pattern);

        $pattern = '/^\s*' . $pattern . '\s*$/iu';

        $filtersArr[$str] = $pattern;

        return $filtersArr[$str];
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
     * Изпълнява се преди импортирването на данните
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
        if ($rec->action == 'email') {
            $rec->driverClass = email_drivers_RouteByFirstEmail::getClassId();
        } elseif ($rec->action == 'folder') {
            $rec->driverClass = email_drivers_RouteByFolder::getClassId();
        }

        $rec->systemId = $mvc->getSystemId($rec);
    }
}
