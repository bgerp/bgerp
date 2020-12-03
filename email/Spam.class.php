<?php 

/**
 * Клас 'email_Spam' - регистър на квалифицираните като твърд спам писма
 *
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Milen Georgiev <milen2experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class email_Spam extends email_ServiceEmails
{
    /**
     * Плъгини за работа
     */
    public $loadList = 'email_Wrapper, plg_Sorting, plg_Search';
    
    public $searchFields = 'spamScore, accountId, uid, createdOn, data';
    
    public $fillSearchKeywordsOnSetup = false;
    
    
    /**
     * Инрерфейси
     */
    public $interfaces = 'email_AutomaticIntf';
    
    
    /**
     * @see email_AutomaticIntf
     */
    public $weight = 100;
    
    
    /**
     * Заглавие на таблицата
     */
    public $title = 'Твърд спам';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'id,msg=Имейл,spamScore';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->addFields();
        $this->FLD('spamScore', 'double(maxDecimals=1)', 'caption=Спам рейтинг');
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
        if ($this->detectSpam($mime, $accId, $uid)) {
            $rec = new stdClass();
            
            // Само първите 100К от писмото
            $rec->data = substr($mime->getData(), 0, 100000);
            $rec->accountId = $accId;
            $rec->uid = $uid;
            $rec->createdOn = dt::verbal2mysql();
            $rec->spamScore = $this->getSpamScore($mime->parts[1]->headersArr, null, $mime);
            
            $this->save($rec);
            
            $this->logNotice('Маркиран имейл като спам', $rec->id);
            
            return $rec->id ? 'spam' : null;
        }
    }
    
    
    /**
     * Дали писмото е SPAM?
     */
    public static function detectSpam($mime, $accId, $uid)
    {
        $isSpam = false;
        
        // Ако е отговор на наш имейл да не се приема като спам
        $subject = $mime->getHeader('subject');
        if ($subject && email_ThreadHandles::extractThreadFromSubject($subject)) {
            
            return $isSpam;
        }
        
        $inReplyTo = $mime->getHeader('In-Reply-To');
        if ($inReplyTo) {
            if ($mid = email_Router::extractMidFromMessageId($inReplyTo)) {
                if (doclog_Documents::fetchByMid($mid)) {
                    
                    return $isSpam;
                }
            }
        }
        
        // Ако няма адрес на изпращача, писмото го обявяваме за спам
        if (!($fromEmail = $mime->getFromEmail())) {
            $isSpam = true;
            
            return $isSpam;
        }
        
        // Ако изпращането е станало, през някои от регистрираните имейл акаунти
        // и в настройките на тази сметка е указано, че изходящи писма чрез нея ще се пращат
        // само през bgERP, то проверява се дали изходящото писмо има валиден mid
        
        // TODO
        
        // Гледаме спам рейтинга
        $score = self::getSpamScore($mime->parts[1]->headersArr, true, $mime);
        if (isset($score) && ($score >= email_Setup::get('HARD_SPAM_SCORE'))) {
            $isSpam = true;
        }
        
        return $isSpam;
    }
    
    
    /**
     * Връща спам рейтинга от хедърите
     *
     * @param array           $headerArr
     * @param bool            $notNull
     * @param NULL|email_Mime $mime
     * @param NULL|stdClass   $rec
     */
    public static function getSpamScore($headerArr, $notNull = true, $mime = null, $rec = null)
    {
        $headersNames = email_Setup::get('CHECK_SPAM_SCORE_HEADERS');
        
        $headersNamesArr = type_Set::toArray($headersNames);
        
        static $scoreArr = array();
        
        $hash = md5(serialize($headerArr));
        
        if (!$scoreArr[$hash]) {
            $score = null;
            
            // Проверяваме рейтинга във всички зададени хедъри
            if ($headersNamesArr) {
                foreach ($headersNamesArr as $header) {
                    $header = trim($header);
                    
                    if (!$header) {
                        continue;
                    }
                    
                    $score = email_Mime::getHeadersFromArr($headerArr, $header);
                    
                    if (!is_numeric($score)) {
                        if (preg_match('/score\s*=\s*([0-9\.]+)(\s|$|[^0-9])/i', $score, $matches)) {
                            $score = $matches[1];
                        }
                    }
                    
                    if (isset($score) && is_numeric($score)) {
                        break;
                    }
                }
            }
            
            if (!is_numeric($score)) {
                $score = null;
            }
            
            $scoreArr[$hash]['score'] = $score;
        } else {
            $score = $scoreArr[$hash]['score'];
        }
        
        if (!isset($score) && $notNull) {
            $score = 0;
        }
        
        if (isset($mime) || isset($rec)) {
            $aScore = email_SpamRules::getSpamScore($mime, $rec);
            
            if ($aScore) {
                $score += $aScore;
            }
        }
        
        return $score;
    }
    
    
    /**
     * Подготовка на филтър формата
     *
     * @param bgerp_Bookmark $mvc
     * @param object         $data
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->showFields = 'search';
    }
    
    
    /**
     * Функция, която се вика по крон по разписание
     * Добавя spamScore към записа
     */
    public static function callback_RepairSpamScore()
    {
        $clsName = get_called_class();
        
        $pKey = $clsName . '|RepairSpamScore';
        
        $clsInst = cls::get($clsName);
        
        $maxTime = dt::addSecs(40);
        
        $kVal = core_Permanent::get($pKey);
        
        $query = $clsInst->getQuery();
        
        if (isset($kVal)) {
            $query->where(array("#id > '[#1#]'", $kVal));
        }
        
        if (!$query->count()) {
            core_Permanent::remove($pKey);
            
            $clsInst->logDebug('Приключи поправката на СПАМ точките');
            
            return ;
        }
        
        $callOn = dt::addSecs(120);
        core_CallOnTime::setCall('email_Spam', 'RepairSpamScore', null, $callOn);
        
        $query->orderBy('id', 'ASC');
        
        $headersNames = email_Setup::get('CHECK_SPAM_SCORE_HEADERS');
        $headersNamesArr = type_Set::toArray($headersNames);
        
        $nHeadersArr = array();
        foreach ($headersNamesArr as $key => $header) {
            $header = trim($header);
            
            if (!$header) {
                continue;
            }
            
            $header = preg_quote($header, '/');
            
            $nHeadersArr[$key] = $header;
        }
        
        while ($rec = $query->fetch()) {
            if (dt::now() >= $maxTime) {
                break;
            }
            
            $maxId = $rec->id;
            
            try {
                $data = $rec->data;
                
                $score = null;
                
                foreach ($nHeadersArr as $header) {
                    
                    if (preg_match("/{$header}\s*:\s*([0-9\.]+)/i", $data, $matches)) {
                        $score = $matches[1];
                    } else {
                        if (preg_match("/{$header}\s*:\s*[\w|\W]*score\s*=\s*([0-9\.]+)(\s|\n|[^0-9])/i", $data, $matches)) {
                            $score = $matches[1];
                        }
                    }
                    
                    if (isset($score) && is_numeric($score)) {
                        break;
                    }
                }
                
                $rec->spamScore = $score;
                
                $clsInst->save($rec, 'spamScore');
            } catch (core_exception_Expect $e) {
                reportException($e);
            }
        }
        
        $clsInst->logDebug('Поправка на СПАМ точки до id=' . $maxId);
        
        core_Permanent::set($pKey, $maxId, 100000);
    }
}
