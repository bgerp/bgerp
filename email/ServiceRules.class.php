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
class email_ServiceRules extends core_Manager
{
    
    /**
     * Инрерфейси
     */
    public $interfaces = 'email_AutomaticIntf';
    
    
    /**
     * @see email_AutomaticIntf
     */
    public $weight = -1;
    
    
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
    
    
    public $listFields = 'id, email, subject, body, classId, note, createdOn, createdBy, state';
    
    
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
        $this->FLD('email', 'varchar', 'caption=Условие->Изпращач', array('attr' => array('style' => 'width: 350px;')));
        $this->FLD('emailTo', 'varchar', 'caption=Условие->Получател', array('attr' => array('style' => 'width: 350px;')));
        $this->FLD('subject', 'varchar', 'caption=Условие->Относно', array('attr' => array('style' => 'width: 350px;')));
        $this->FLD('body', 'varchar', 'caption=Условие->Текст', array('attr' => array('style' => 'width: 350px;')));
        $this->FLD('classId', 'class(interface=email_ServiceRulesIntf, select=title)', 'caption=Обработвач, mandatory', array('attr' => array('style' => 'width: 350px;')));
        $this->FLD('note', 'text', 'caption=@Забележка', array('attr' => array('style' => 'width: 100%;', 'rows' => 4)));
        
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
            
            $query->where("#state = 'active'");
            
            // Зареждаме всички активни филтри
            $allFilters = $query->fetchAll();
        }
        
        if (!$allFilters) {
            
            return ;
        }
        
        $pRes = null;
        
        foreach ($allFilters as $filterRec) {
            
            if (email_Filters::match($sDataArr, $filterRec)) {
                
                if (!$filterRec->classId) {
                    
                    $this->logDebug('Липсва classId', null, 3);
                    
                    continue;
                }
                
                $sInst = cls::getInterface('email_ServiceRulesIntf', $filterRec->classId);
                
                $pRes = $sInst->process($mime);
                
                if (isset($pRes)) {
                    
                    email_ServiceRulesData::add($mime, $accId, $uid, $filterRec->id);
                    
                    break;
                }
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
        
        $str = trim($rec->email) . '|' . trim($rec->subject) . '|' . trim($rec->body);
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
        $fArr = array('email', 'subject', 'body');
        
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
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param email_Filters $mvc
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
}
