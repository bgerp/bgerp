<?php 


/**
 * Абониране за бюлетина
 *
 * @category  bgerp
 * @package   marketing
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class marketing_Bulletin extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Абонати за бюлетина";
    
    
    /**
     * Кой има право да го чете?
     */
    var $canRead = 'ceo, marketing';
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'ceo, marketing';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'ceo, marketing';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo, marketing';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'ceo, marketing';
    

    /**
     * Плъгини за зареждане
     */
    var $loadList = 'marketing_Wrapper,  plg_RowTools, plg_Created';
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'bgerp_PersonalizationSourceIntf';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('email', 'email', 'caption=Имейл, mandatory');
        $this->FLD('name', 'varchar(128)', 'caption=Имена, oldFieldName=names');
        $this->FLD('company', 'varchar(128)', 'caption=Фирма');
        $this->FLD('ip', 'ip', 'caption=IP, input=none');
        $this->FLD('brid', 'varchar(8)', 'caption=Браузър, input=none');
        
        $this->setDbUnique('email');
    }
    
    
    /**
     * Записва подадените данни и показва .png файл
     */
    function act_getImg()
    {
        vislog_History::add('Нов абонамент за бюлетина');
        
        $email = trim(Request::get('email'));
        $name = trim(Request::get('name'));
        $company = trim(Request::get('company'));
        
        // Проверява дали имейла е валиден, за да може да се запише
        if (!$email || !type_Email::isValidEmail($email)) {
            $haveError = TRUE;
        }
        
        if (!$haveError) {
            
            // Добавяме данните към `brid` в модела
            $userData = array('email' => $email);
            if ($company) {
                $userData['company'] = $company;
            }
            if ($name) {
                $userData['name'] = $name;
            }
            core_Browser::setVars($userData);
            
            if (!self::fetch(array("#email='[#1#]'", $email))) {
                $rec = new stdClass();
                $rec->email = $email;
                $rec->name = $name;
                $rec->company = $company;
                $rec->ip = core_Users::getRealIpAddr();
                $rec->brid = core_Browser::getBrid();
                
                self::save($rec);
            }
        }
        
        
        
        $conf = core_Packs::getConfig('marketing');
        
        if ($conf->MARKETING_BULLETIN_IMG) {
            
            $fRec = fileman_Files::fetchByFh($conf->MARKETING_BULLETIN_IMG);
        	$ext = fileman_Files::getExt($fRec->name);
        	
        	$path = fileman::extract($conf->MARKETING_BULLETIN_IMG);
        	
        	switch ($ext) {
        		case 'jpg':
        		case 'jpeg':
        				$imgSource = imagecreatefromjpeg($path);
        				$contentType = 'image/jpg';
        			break;
        		case 'gif':
        				$imgSource = imagecreatefromgif($path);
        				$contentType = 'image/gif';
        			break;
        		case 'png':
        				$imgSource = imagecreatefrompng($path);
        				$contentType = 'image/png';
        			break;
        	}
        } else {
            $imgSource = imagecreatefrompng(getFullPath('img/thanks.png'));
			$contentType = 'image/png';
        }
        
        header('Content-Type: ' . $contentType);
        
        // Запазваме прозрачността
        imagealphablending($imgSource, false);
        imagesavealpha($imgSource, true);
        
        imagepng($imgSource);
        imagedestroy($imgSource);
        
        shutdown();
    }
    
    
    /**
     * Подготвя и принтира съдържанието на .js файла
     */
    public function act_getJs()
    {
        $js = file_get_contents(getFullPath('/marketing/js/Bulletin.js'));
        
        $jsTpl = new ET($js);
        
        $conf = core_Packs::getConfig('marketing');
        
        // След колко време да се покаже повторно
        $showAgainAfter = $conf->MARKETING_SHOW_AGAIN_AFTER;
        $jsTpl->replace($showAgainAfter, 'showAgainAfter');
        
        // След колко време на бездействие да се покаже
        $idleTimeForShow = $conf->MARKETING_IDLE_TIME_FOR_SHOW;

        $jsTpl->replace($idleTimeForShow, 'idleTimeForShow');
        
        // След колко секунди да може да се стартира
        $waitBeforeStart = $conf->MARKETING_WAIT_BEFORE_START;

        $jsTpl->replace($waitBeforeStart, 'waitBeforeStart');
        
        // Заглавие на формата
        $formTitle = tr($conf->MARKETING_BULLETIN_FORM_TITLE);


        $formTitle = addslashes($formTitle);
        $jsTpl->replace($formTitle, 'formTitle');
        
        
        // Съобщение при абониране
        $successText = tr($conf->MARKETING_BULLETIN_FORM_SUCCESS);

        $successText = addslashes($successText);
        $jsTpl->replace($successText, 'successText');
        
        // Дали да се показва цялата форма или само имейла
        $showAllForm = $conf->MARKETING_SHOW_ALL_FORM;
    
        $jsTpl->replace($showAllForm, 'showAllForm');
        
        $wrongMail = tr('Невалиден имейл!');
        $jsTpl->replace($wrongMail, 'wrongMailText');
        
        $emailName = tr('Имейл');
        $emailName = addslashes($emailName);
        $jsTpl->replace($emailName, 'emailName');
        
        $namesName = tr('Имена');
        $namesName = addslashes($namesName);
        $jsTpl->replace($namesName, 'namesName');
        
        $companyName = tr('Фирма');
        $companyName = addslashes($companyName);
        $jsTpl->replace($companyName, 'companyName');
        
        $submitBtnVal = tr('Абонирам се за информация');
        $submitBtnVal = addslashes($submitBtnVal);
        $jsTpl->replace($submitBtnVal, 'submitBtnVal');
        
        $cancelBtnVal = tr('Не, благодаря');
        $cancelBtnVal = addslashes($cancelBtnVal);
        $jsTpl->replace($cancelBtnVal, 'cancelBtnVal');
        
        $formActionUrl = toUrl(array('marketing_Bulletin', 'getImg'), TRUE);
        $formActionUrl = addslashes($formActionUrl);
        $jsTpl->replace($formActionUrl, 'formAction');
        
        $bulletinUrl = $conf->MARKETING_BULLETIN_URL;
        
        if (!$bulletinUrl) {
            $bulletinUrl = toUrl(array('marketing_Bulletin', 'getJs'), TRUE);
        }
        $urlArr = parse_url($bulletinUrl);
        $cookieKey = substr(md5($urlArr['host']), 0, 6);
        $jsTpl->replace($cookieKey, 'cookieKey');
        
        
        $js = $jsTpl->getContent();
        
        $js = minify_Js::process($js);
        
        header('Content-Type: text/javascript');
        
        echo $js;
        
        shutdown();
    }
    
    
    /**
     * Връща URL към JS файла за показване на бюлетина
     * 
     * @return string|boolean
     */
    public static function getJsLink()
    {
        $conf = core_Packs::getConfig('marketing');
        
        if ($conf->MARKETING_USE_BULLETIN != 'yes') return FALSE;
        
        $url = $conf->MARKETING_BULLETIN_URL;
        
        if (!$url) {
            $url = toUrl(array('marketing_Bulletin', 'getJs'), true);
        }
                
        return $url;
    }
    
    
    /**
     * 
     * 
     * @param core_LoginLog $mvc
     * @param object $row
     * @param object $rec
     * @param array $fields
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	// Оцветяваме BRID
    	$row->brid = core_Browser::getLink($rec->brid);
    	
        if ($rec->ip) {
        	// Декорираме IP-то
            $row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn, TRUE);
    	}
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     * 
     * @param marketing_Bulletin $mvc
     * @param object $res
     * @param object $data
     */
    function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if (blast_Emails::haveRightFor('add') && $mvc->fetch("1=1")) {
            $data->toolbar->addBtn('Циркулярен имейл', array('blast_Emails', 'add', 'perSrcClassId' => core_Classes::getId($mvc)),
            'id=btnEmails','ef_icon = img/16/emails.png,title=Създаване на циркулярен имейл');
        }
    }
    
    
    /**
     * Връща масив за SELECT с всички възможни източници за персонализация от даден клас,
     * за съответния запис,
     * които са достъпни за посочения потребител
     * @see bgerp_PersonalizationSourceIntf
     * 
     * @param integer $id
     * 
     * @return array
     */
    public function getPersonalizationOptionsForId($id)
    {
        $resArr = $this->getPersonalizationOptions();
        
        return $resArr;
    }
    
    
    /**
     * Връща масив за SELECT с всички възможни източници за персонализация от даден клас, които са достъпни за посочения потребител
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param integer $userId
     * @param integer $folderId
     *
     * @return array
     */
    public function getPersonalizationOptions($userId = NULL)
    {
        $resArr = array($this->title);
        
        return $resArr;
    }

    
    /**
     * Връща масив с ключове имената на плейсхолдърите и съдържание - типовете им
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param string $id
     *
     * @return array
     */
    public function getPersonalizationDescr($id)
    {
        $resArr = array();
        $resArr['email'] = cls::get('type_Email');
        $resArr['person'] = cls::get('type_Varchar');
        $resArr['company'] = cls::get('type_Varchar');
        
        return $resArr;
    }
    
    
    /**
     * Дали потребителя може да използва дадения източник на персонализация
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param string $id
     * @param integer $userId
     *
     * @return boolean
     */
    public function canUsePersonalization($id, $userId = NULL)
    {
        // Всеки който има права до листване на модела
        if ($this->haveRightFor('list', $id, $userId)) {
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Връща вербално представяне на заглавието на дадения източник за персонализирани данни
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param string|object $id
     * @param boolean $verbal
     *
     * @return string
     */
    public function getPersonalizationTitle($id, $verbal = TRUE)
    {
        
        return $this->title;
    }
    
    
    /**
     * Връща масив с ключове - уникални id-та и ключове - масиви с данни от типа place => value
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param string $id
     * @param integer $limit
     *
     * @return array
     */
    public function getPresonalizationArr($id, $limit = 0)
    {
        $query = $this->getQuery();
        
        if ($limit) {
            $query->limit($limit);
        }
        
        $resArr = array();
        
        while ($rec = $query->fetch()) {
            $resArr[$rec->id] = array('email' => $rec->email, 'person' => $rec->name, 'company' => $rec->company);
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща линк, който сочи към източника за персонализация
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param string $id
     *
     * @return core_ET
     */
    public function getPersonalizationSrcLink($id)
    {
        // Създаваме линк към сингъла листа
        $title = $this->getPersonalizationTitle($id, TRUE);
        $link = ht::createLink($title, array($this, 'list'));
        
        return $link;
    }
}
