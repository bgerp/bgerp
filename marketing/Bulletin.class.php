<?php 


/**
 * Абониране за бюлетини
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
    var $title = "Абонамент за бюлетина";
    
    
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
     * Описание на модела
     */
    function description()
    {
        $this->FLD('email', 'email', 'caption=Имейл, mandatory');
        $this->FLD('names', 'varchar(128)', 'caption=Имена');
        $this->FLD('company', 'varchar(128)', 'caption=Фирма');
        $this->FLD('ip', 'ip', 'caption=IP, input=none');
        $this->FLD('brid', 'varchar(8)', 'caption=BRID, input=none');
        
        $this->setDbUnique('email');
    }
    
    
    /**
     * Записва подадените данни и показва .png файл
     */
    function act_getImg()
    {
        $email = trim(Request::get('email'));
        $names = trim(Request::get('names'));
        $company = trim(Request::get('company'));
        
        // Проверява дали имейла е валиден, за да може да се запише
        if (!$email || !type_Email::isValidEmail($email)) {
            $haveError = TRUE;
        }
        
        $img = 'img/32/info.png';
        
        if (!$haveError) {
            
            if (!self::fetch(array("#email='[#1#]'", $email))) {
                $rec = new stdClass();
                $rec->email = $email;
                $rec->names = $names;
                $rec->company = $company;
                $rec->ip = core_Users::getRealIpAddr();
                $rec->brid = core_Browser::getBrid();
                
                self::save($rec);
            }
        }
        
        $im = imagecreatefrompng(getFullPath($img));
        
        header('Content-Type: image/png');
        
        // Запазваме прозрачността
        imagealphablending($im, false);
        imagesavealpha($im, true);
        
        imagepng($im);
        imagedestroy($im);
        
        shutdown();
    }
    
    
    /**
     * Подготвя и принтира съдържанието на .js файла
     */
    public function act_getJs()
    {
        $lg = Request::get('lg');
        
        if ($lg) {
            core_Lg::push($lg);
        }
        
        $js = file_get_contents(getFullPath('/marketing/js/Bulletin.js'));
        
        $jsTpl = new ET($js);
        
        $conf = core_Packs::getConfig('marketing');
        
        // След колко време да се покаже повторно
        $showAgainAfter = Request::get('showAgainAfter', 'int');
        if (!$showAgainAfter) {
            $showAgainAfter = $conf->MARKETING_SHOW_AGAIN_AFTER;
        }
        $jsTpl->replace($showAgainAfter, 'showAgainAfter');
        
        // След колко време на бездействие да се покаже
        $idleTimeForShow = Request::get('idleTimeForShow', 'int');
        if (!$idleTimeForShow) {
            $idleTimeForShow = $conf->MARKETING_IDLE_TIME_FOR_SHOW;
        }
        $jsTpl->replace($idleTimeForShow, 'idleTimeForShow');
        
        // След колко секунди да може да се стартира
        $waitBeforeStart = Request::get('waitBeforeStart', 'int');
        if (!$waitBeforeStart) {
            $waitBeforeStart = $conf->MARKETING_WAIT_BEFORE_START;
        }
        $jsTpl->replace($waitBeforeStart, 'waitBeforeStart');
        
        // Заглавие на формата
        $formTitle = Request::get('formTitle');
        if (!$formTitle) {
            $formTitle = tr($conf->MARKETING_BULLETIN_FORM_TITLE);
        }
        $formTitle = addslashes($formTitle);
        $jsTpl->replace($formTitle, 'formTitle');
        
        
        // Съобщение при абониране
        $successText = Request::get('successText');
        if (!$successText) {
            $successText = tr($conf->MARKETING_BULLETIN_FORM_SUCCESS);
        }
        $successText = addslashes($successText);
        $jsTpl->replace($successText, 'successText');
        
        // Дали да се показва цялата форма или само имейла
        $showAllForm = Request::get('showAllForm');
        if (!$showAllForm) {
            $showAllForm = $conf->MARKETING_SHOW_ALL_FORM;
        }
        $jsTpl->replace($showAllForm, 'showAllForm');
        
        $wrongMail = tr('Грешен имейл!');
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
        
        if ($lg) {
            core_Lg::pop();
        }
        
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
        
        $url = rtrim($url, '/') . '/?';
        $url .= 'showAgainAfter=' . urlencode($conf->MARKETING_SHOW_AGAIN_AFTER) . 
                '&idleTimeForShow=' . urlencode($conf->MARKETING_IDLE_TIME_FOR_SHOW) . 
                '&waitBeforeStart=' . urlencode($conf->MARKETING_WAIT_BEFORE_START) . 
                '&formTitle=' . urlencode($conf->MARKETING_BULLETIN_FORM_TITLE) . 
                '&successText=' . urlencode($conf->MARKETING_BULLETIN_FORM_SUCCESS) . 
                '&showAllForm=' . urlencode($conf->MARKETING_SHOW_ALL_FORM);
        
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
    	$row->brid = str::coloring($row->brid);
    	
        if ($rec->ip) {
        	// Декорираме IP-то
            $row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn, TRUE);
    	}
    }
}
