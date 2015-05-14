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
class marketing_Bulletins extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Бюлетини";
    
    
    /**
     * Детайли
     */
    public $details = 'marketing_BulletinSubscribers';
    
    
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
    var $canAdd = 'ceo, marketing';
    
    
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
     * Кой има право да разглежда сингъла?
     */
    var $canSingle = 'ceo, marketing';
    

    /**
     * Плъгини за зареждане
     */
    var $loadList = 'marketing_Wrapper,  plg_RowTools, plg_Created, plg_State2, plg_Sorting';
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'bgerp_PersonalizationSourceIntf';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, domain, state, subscribersCnt, subscribersLast';


    /**
     * Файл с шаблон за единичен изглед на бюлетин
     */
    public $singleLayoutFile = 'marketing/tpl/SingleLayoutBulletin.shtml';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsSingleField = 'domain';


    /**
     * Полета на модела
     */
    public function description()
    {
        $this->FLD('domain', 'varchar', 'caption=Бюлетин, mandatory');
        $this->FLD('showAllForm', 'enum(yes=Да, no=Не)', 'caption=Показване на цялата форма, title=Дали да се показва цялата форма или само имейла');
        $this->FLD('formTitle', 'varchar(128)', 'caption=Съдържание на формата->Покана за абонамент');
        $this->FLD('formSuccessText', 'varchar(128)', 'caption=Съдържание на формата->Благодарност при абониране');
        $this->FLD('img', 'fileman_FileType(bucket=pictures)', 'caption=Съдържание на формата->Картинка при абониране');
        $this->FLD('showFormBtn', 'varchar(128)', 'caption=Текст на бутона за показване на формата, title=Тест на бутона за форсирано показване на формата');
        
//        $this->FLD('showFormBtn', 'varchar(128)', 'caption=Текстове на бутони->За показване');//Абонамент за новости
//        $this->FLD('showFormBtn', 'varchar(128)', 'caption=Текстове на бутони->За абониране');//Абонирам се за бюлетина
//        $this->FLD('showFormBtn', 'varchar(128)', 'caption=Текстове на бутони->За отказ');//Не, благодаря
        
        
        $this->FLD('showAgainAfter', 'time(suggestions=3 часа|12 часа|1 ден)', 'caption=Изчакване преди ново отваряне');
        $this->FLD('idleTimeForShow', 'time(suggestions=5 секунди|20 секунди|1 мин)', 'caption=Период за бездействие преди активиране->Време');
        $this->FLD('waitBeforeStart', 'time(suggestions=3 секунди|5 секунди|10 секунди)', 'caption=След колко време да може да стартира бюлетина->Време');
        $this->FLD('bgColor', 'color_Type', 'caption=Цветове за бюлетина->Цвят на фона');
        $this->FLD('textColor', 'color_Type', 'caption=Цветове за бюлетина->Цвят на текста');
        $this->FLD('buttonColor', 'color_Type', 'caption=Цветове за бюлетина->Цвят на бутона');
        $this->FLD('subscribersCnt', 'int', 'caption=Абонаменти->Общо, input=none, notNull');
        $this->FLD('subscribersLast', 'datetime(format=smartTime)', 'caption=Абонаменти->Последен, input=none, notNull');
        
        $this->FLD('data', 'blob(serialize,compress)', 'Данни, input=none');
        
        $this->FNC('scriptTag', 'varchar', 'caption=Скрипт таг');
        
        $this->setDbUnique('domain');
    }
    
    
    /**
     * Връща запис за съответния домейн, ако е активен
     * 
     * @param string $domain
     * 
     * @return FALSE|object
     */
    public static function getRecForDomain($domain)
    {
        $rec = self::fetch(array("#domain = '[#1#]' AND #state = 'active'", $domain));
        
        return $rec;
    }
    
    
    /**
     * Връща линк към екшъна за показване на съдържанието на JS файла
     * 
     * @param integer $id
     * 
     * @return string
     */
    public static function getJsLink($id)
    {
        $data = self::fetchField($id, 'data');
        
        $hash = md5(serialize($data));
        
        $hash = substr($hash, 0, 6);
        
        return self::prepareLinkFor($id, 'getJS', $hash);
    }
    
    
    /**
     * Връща домейна с езика
     * 
     * @param string $domain
     * @param string $lg
     * 
     * @return string
     */
    public static function getDomain($domain, $lg)
    {
        $domain = $domain . '/lang/'  . $lg;
        
        return $domain;
    }
    

    /**
     * Връща хешираната стойност за id
     * 
     * @param integer $id
     * 
     * @return string
     */
    protected static function getHashId($id)
    {
        
        return str::addHash($id);
    }
    
    
    /**
     * Проверява стойността на id-то
     * 
     * @param string $str
     * 
     * @return boolean|integer
     */
    protected static function checkHashId($str)
    {
        
        return str::checkHash($str);
    }
    
    
    /**
     * Подготвя подадения линк за екшъна
     * 
     * @param integer $id
     * @param string $act
     * @param boolean|string $rand
     * 
     * @return string
     */
    protected static function prepareLinkFor($id, $act, $rand = TRUE)
    {
        if ($rand === FALSE) {
            $domain = toUrl(array('marketing_Bulletins', $act, self::getHashId($id)), TRUE);
        } else {
            if ($rand === TRUE) {
                $randStr = rand();
            } else {
                $randStr = $rand;
            }
            
            $domain = toUrl(array('marketing_Bulletins', $act, self::getHashId($id), 'r' => $randStr), TRUE, TRUE, array('r'));
        }
        
        return $domain;
    }
    
    
    /**
     * Връща линк към екшъна за показване на съдържанието на CSS файла
     * 
     * @param integer $id
     * 
     * @return string
     */
    protected static function getCssLink($id)
    {
        
        return self::prepareLinkFor($id, 'getCSS');
    }
    
    
    /**
     * Връща линк към екшъна за показване формата за регистрация
     * 
     * @param integer $id
     * 
     * @return string
     */
    protected static function getLinkForShowForm($id)
    {
        
        return self::prepareLinkFor($id, 'ShowWindowJS', FALSE);
    }
    
    
    
    /**
     * Връща линк към екшъна за показване на img файла
     * 
     * @param integer $id
     * 
     * @return string
     */
    protected static function getLinkForShowImg($id)
    {
        
        return self::prepareLinkFor($id, 'getImg');
    }
    
    
    /**
     * Подготвя JS файла, който следи за показване на формата
     * 
     * @param integer $id
     * 
     * @return string
     */
    protected static function prepareJS($id)
    {
        $bRec = self::fetch($id);
        
        $js = file_get_contents(getFullPath('/marketing/tpl/BulletinJsTpl.txt'));
        
        $jsTpl = new ET($js);
        
        // След колко време да се покаже повторно
        $jsTpl->replace($bRec->showAgainAfter, 'showAgainAfter');
        
        // След колко време на бездействие да се покаже
        $jsTpl->replace($bRec->idleTimeForShow , 'idleTimeForShow');
        
        // След колко секунди да може да се стартира
        $jsTpl->replace($bRec->waitBeforeStart, 'waitBeforeStart');
        
        // Съобщение при абониране
        $successText = $bRec->formSuccessText;
        $successText = addslashes($successText);
        $jsTpl->replace($successText, 'successText');
        
        // Съобщение на бутона за показване на формата за абониране
        $showFormBtn = $bRec->showFormBtn;
        $successText = addslashes($showFormBtn);
        $jsTpl->replace($showFormBtn, 'showFormBtn');
        
        // Линк за показване на формата
        $showFormUrl = self::getLinkForShowForm($id);
        $showFormUrl = addslashes($showFormUrl);
        $jsTpl->replace($showFormUrl, 'showFormUrl');
        
        $formTitle = $bRec->formTitle;
        $formTitle = addslashes($formTitle);
        $jsTpl->replace($formTitle, 'formTitle');
        
        $wrongMail = tr('Невалиден имейл!');
        $jsTpl->replace($wrongMail, 'wrongMailText');
        
        $emailName = tr('Имейл');
        $emailName = addslashes($emailName);
        $jsTpl->replace($emailName, 'emailName');
        
        $submitBtnVal = tr('Абонирам се за информация');
        $submitBtnVal = addslashes($submitBtnVal);
        $jsTpl->replace($submitBtnVal, 'submitBtnVal');
        
        $cancelBtnVal = tr('Не, благодаря');
        $cancelBtnVal = addslashes($cancelBtnVal);
        $jsTpl->replace($cancelBtnVal, 'cancelBtnVal');
        
        $formActionUrl = self::getLinkForShowImg($id);
        $formActionUrl = addslashes($formActionUrl);
        $jsTpl->replace($formActionUrl, 'formAction');
        
        $jsTpl->replace($bRec->showAllForm, 'showAllForm');
        
        if ($bRec->showAllForm == 'yes') {
            
            $namesName = tr('Имена');
            $namesName = addslashes($namesName);
            $jsTpl->replace($namesName, 'namesName');
            
            $companyName = tr('Фирма');
            $companyName = addslashes($companyName);
            $jsTpl->replace($companyName, 'companyName');
            
            $jsTpl->replace(' ', 'namesAndCompanyFields');
        }
        
        $cookieKey = substr(md5($_SERVER['SERVER_NAME']), 0, 6);
        $jsTpl->replace($cookieKey, 'cookieKey');
        
        $jsTpl->replace(self::getCssLink($id), 'CSS_URL');
        
        $js = $jsTpl->getContent();
        
        $js = minify_Js::process($js);
        
        return $js;
    }
    
    
    /**
     * Подготвя CSS файла, който рендира стиловете
     * 
     * @param integer $id
     * 
     * @return string
     */
    protected static function prepareCSS($id)
    {
        $bRec = self::fetch($id);
        
        $css = file_get_contents(getFullPath('/marketing/tpl/BulletinCssTpl.txt'));
        
        $cssTpl = new ET($css);
        
        if ($bRec->bgColor) {
            $cssTpl->replace($bRec->bgColor, 'bulletinRegBg');
        }
        
        if($bRec->textColor) {
            $cssTpl->replace($bRec->textColor, 'textColor');
        }
        
        $btnColor =  ltrim($bRec->buttonColor, "#");
        
        if ($btnColor) {
            $darkBtnColor = phpcolor_Adapter::changeColor($btnColor, 'lighten', 15);
            $shadowBtnColor = phpcolor_Adapter::changeColor($darkBtnColor, 'mix', 1, '#444');
            
            if(phpcolor_Adapter::checkColor($btnColor, 'light'))  {
                $btnColorShadow = ' ';
                $cssTpl->replace($btnColorShadow, 'btnColorShadow');
            }
        }
        
        $cssTpl->replace($btnColor, 'btnColor');
        $cssTpl->replace($darkBtnColor, 'darkBtnColor');
        $cssTpl->replace($shadowBtnColor, 'shadowBtnColor');
        
        $css = $cssTpl->getContent();
        
        $css = minify_Css::process($css);
        
        return $css;
    }
    
    
    /**
     * Подготвя JS функцията за показване на формата
     * 
     * @return string
     */
    protected static function prepareShowWindowJS()
    {
        $js = 'bulletinFormOpen();';
        
        return $js;
    }
    
    
    /**
     * 
     * 
     * @param marketing_Bulletins $mvc
     * @param object $rec
     */
    protected function on_CalcScriptTag($mvc, $rec)
    {
        if (!$rec->domain || !$rec->id) return ;
        
        // За локалхост няма нужда да се показва
        if (strpos($rec->domain, 'localhost/') === 0) return ;
        
        $rec->scriptTag = '<script src="' . self::getJsLink($rec->id) . '"></script>';
    }
    
    
    /**
     * След подготовка на формата за добавяне/редакция
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        $domainsArr = array();
        
        $dQuery = cms_Domains::getQuery();
        while ($dRec = $dQuery->fetch()) {
            if ($dRec->lang) {
                $domain = self::getDomain($dRec->domain, $dRec->lang);
            }
            
            $domainsArr[$domain] = $domain;
        }
        
        if ($domainsArr) {
            $domainsArr = array('' => '') + $domainsArr;
        }
        
        $form->setSuggestions('domain', $domainsArr);
        
        $form->setDefault('formTitle', 'Искате ли да научавате всички новости за нас?');
        $form->setDefault('formSuccessText', 'Благодарим за абонамента за нашите новости');
        $form->setDefault('showFormBtn', 'Абонирай се за информация');
        $form->setDefault('showAgainAfter', '10800'); //3 часа
        $form->setDefault('idleTimeForShow', '20');
        $form->setDefault('waitBeforeStart', '5');
        $form->setDefault('showAllForm', 'no');
    }
    
    
    /**
     * Подготвя и принтира съдържанието на JS файла
     */
    public function act_getJS()
    {
        $bid = Request::get('id');
        
        if (!($id = self::checkHashId($bid))) shutdown();
        
        $bRec = self::fetch((int) $id);
        
        if (!$bRec || ($bRec->state != 'active')) shutdown();
        
        $js = $bRec->data['js'];
        
        header('Content-Type: application/javascript');
        
        // Хедъри за управлението на кеша в браузъра
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + 31536000) . " GMT");
        header("Cache-Control: public, max-age=31536000");
        
        // Поддържа ли се gzip компресиране на съдържанието?
        $isGzipSupported = in_array('gzip', array_map('trim', explode(',', @$_SERVER['HTTP_ACCEPT_ENCODING'])));

        if ($isGzipSupported) {
            // Компресираме в движение и подаваме правилния хедър
            $js = gzencode($js);
            header("Content-Encoding: gzip");
        } 
        
        // Отпечатваме съдържанието и го изпращаме към браузъра
        header("Content-Length: " . strlen($js));
        
        header_remove("Pragma");
        
        echo $js;
        
        shutdown();
    }
    
    
    /**
     * Подготвя и принтира съдържанието на .css файла
     */
    public function act_getCSS()
    {
        $bid = Request::get('id');
        
        if (!($id = self::checkHashId($bid))) shutdown();
        
        $bRec = self::fetch((int) $id);
        
        if (!$bRec || ($bRec->state != 'active')) shutdown();
        
        header('Content-Type: text/css');
        
        // Хедъри за управлението на кеша в браузъра
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + 31536000) . " GMT");
        header("Cache-Control: public, max-age=31536000");
        
        // Поддържа ли се gzip компресиране на съдържанието?
        $isGzipSupported = in_array('gzip', array_map('trim', explode(',', @$_SERVER['HTTP_ACCEPT_ENCODING'])));
        
        $css = $bRec->data['css'];
        
        if ($isGzipSupported) {
            // Компресираме в движение и подаваме правилния хедър
            $css = gzencode($css);
            header("Content-Encoding: gzip");
        } 
        
        // Отпечатваме съдържанието и го изпращаме към браузъра
        header("Content-Length: " . strlen($css));
        
        header_remove("Pragma");
        
        echo $css;
        
        shutdown();
    }
    
    
    /**
     * Подготвя и принтира формата за регистрация
     */
    function act_ShowWindowJS()
    {
        $bid = Request::get('id');
        
        if (!($id = self::checkHashId($bid))) shutdown();
        
        $bRec = self::fetch((int) $id);
        
        if (!$bRec || ($bRec->state != 'active')) shutdown();
        
        header('Content-Type: text/javascript');
        
        // Да не се кешира
        header('Cache-Control: no-cache, must-revalidate'); // HTTP 1.1.
		header('Pragma: no-cache'); // HTTP 1.0.
		header('Expires: 0'); // Proxies.
        
        // Ако има имейл регистриран от този браузър
        // Ако име абонамент за бюлетина
        // Или ако има логване от този браузър
        if (core_Browser::getVars(array('email'))
            || marketing_BulletinSubscribers::haveRecForIp($id)
            || core_LoginLog::isLoggedBefore()) {
            
            shutdown();
        }
        
        $js = $bRec->data['showWindowJS'];
        
        echo $js;
        
        shutdown();
    }
    
    
    /**
     * Записва подадените данни и показва .png файл
     */
    function act_getImg()
    {
        $bid = Request::get('id');
        
        if (!($id = self::checkHashId($bid))) shutdown();
        
        $bRec = self::fetch((int) $id);
        
        if ($bRec && ($bRec->state == 'active')) {
        
            $email = trim(Request::get('email'));
            $name = trim(Request::get('name'));
            $company = trim(Request::get('company'));
            
            try {
                marketing_BulletinSubscribers::addData($id, $email, $name, $company);
            } catch (core_exception_Expect $e) {
                // Да не се прави нищо
            }
            
            if ($bRec->img) {
                
                $fRec = fileman_Files::fetchByFh($bRec->img);
            	$ext = fileman_Files::getExt($fRec->name);
            	
            	$path = fileman::extract($bRec->img);
            	
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
        } else {
            $imgSource = imagecreatefromgif(getFullPath('img/error.gif'));
            $contentType = 'image/gif';
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
     * Извиква се след успешен запис в модела
     *
     * @param marketing_Bulletins $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     * @param $fields array
     */
    public static function on_AfterSave($mvc, &$id, $rec, $fields=array())
    {
        // При обновяване на всички полета или само на посочните да се променя `data`
        if (!$fields || isset($fields['js']) || isset($fields['showWindowJS']) || isset($fields['css'])) {
            $rec->data['js'] = self::prepareJS($id);
            $rec->data['showWindowJS'] = self::prepareShowWindowJS();
            $rec->data['css'] = self::prepareCSS($id);
            
            $mvc->save_($rec, 'data');
        }
    }
    
    
    /**
     * След промяна в детайлите на обект от този клас
     * 
     * @param marketing_Bulletins $mvc
     * @param integer $id
     * @param marketing_BulletinSubcribers $detailMvc
     */
    public static function on_AfterUpdateDetail($mvc, $id, $detailMvc)
    {
        $query = $detailMvc->getQuery();
        $query->where("#bulletinId = $id");
        $cnt = $query->count();
        $query->orderBy('createdOn', 'DESC');
        $lastRec = $query->fetch();
        
        $rec = new stdClass();
        $rec->id = $id;
        $rec->subscribersCnt = $cnt;
        $rec->subscribersLast = $lastRec->createdOn;
        
        $mvc->save($rec, 'subscribersCnt, subscribersLast');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($rec && $action == 'delete') {
            if ($rec->subscribersCnt > 0) {
                $requiredRoles = 'no_one';
            }
        }
    }
}
