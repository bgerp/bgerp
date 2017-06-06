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
     * Заглавие на единичния обект
     */
    public $singleTitle = 'Бюлетин';
    
    
    /**
     * 
     */
    public $recTitleTpl = '[#domain#]';
    
    
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
    var $loadList = 'marketing_Wrapper,  plg_RowTools2, plg_Created, plg_State2, plg_Sorting';
    
    
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
     * "Лепило" за слепване на език и домейн
     */
    protected static $domainLgGlue = '/lang/';
    
    
    /**
     * Полета на модела
     */
    public function description()
    {
        $this->FLD('domain', 'varchar', 'caption=Бюлетин, mandatory');
        
        $this->FLD('lg', 'varchar(2)', 'caption=Език,notNull');
        
        $this->FLD('formTitle', 'richtext(rows=3,bucket=InquiryBucket)', 'caption=Съдържание на формата->Покана за абонамент');
        $this->FLD('logo', 'fileman_FileType(bucket=pictures)', 'caption=Съдържание на формата->Лого');
        $this->FLD('formSuccessText', 'varchar(128)', 'caption=Съдържание на формата->Благодарност при абониране');
        $this->FLD('img', 'fileman_FileType(bucket=pictures)', 'caption=Съдържание на формата->Картинка при абониране');
        
        $this->FLD('showFormBtn', 'varchar(128)', 'caption=Текстове на бутони->За показване');
        $this->FLD('submitBtnVal', 'varchar(128)', 'caption=Текстове на бутони->За абониране');
        $this->FLD('cancelBtnVal', 'varchar(128)', 'caption=Текстове на бутони->За отказ');
        
        $this->FLD('delayBeforeOpenInHit', 'time(suggestions=3 сек.|5 сек.|10 сек.)', 'caption=Времена за изчакване->Преди показване в хита, notNull');
        $this->FLD('delayBeforeOpen', 'time(suggestions=1 мин.|2 мин.|5 мин.)', 'caption=Времена за изчакване->Преди показване, oldFieldName=waitBeforeStart, notNull');
        $this->FLD('delayAfterClose', 'time(suggestions=30 мин.|1 часа|3 часа)', 'caption=Времена за изчакване->След затваряне, oldFieldName=showAgainAfter, notNull');
        
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
        $domain = $domain . self::$domainLgGlue  . $lg;
        
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
        
        $domain = preg_replace("/^https?\:\/\//", "//", $domain, 1);
        
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
        
        if ($bRec->lg) {
            core_Lg::push($bRec->lg);
        }
        
        $js = file_get_contents(getFullPath('/marketing/tpl/BulletinJsTpl.txt'));
        
        $jsTpl = new ET($js);
        
        $jsTpl->replace($bRec->delayAfterClose, 'delayAfterClose');        
        $jsTpl->replace($bRec->delayBeforeOpen, 'delayBeforeOpen');        
        $jsTpl->replace($bRec->delayBeforeOpenInHit, 'delayBeforeOpenInHit');        
        
        // Заглавие на формата
        // Пушваме `xhtml` за да направим линковете абсолютни
        Mode::push('text', 'xhtml');
        $formTitle = self::getVerbal($bRec, 'formTitle');
        Mode::pop('text');

        // Вкарваме стиловете, за да може да се стилнат текствете от ричтекста, когато са извън `bgERP`
        $formTitle = self::addInlineCSS($formTitle);

        $formTitle = str_replace(array("\r\n", "\n", "\r"), ' ', $formTitle);
        $formTitle = addslashes($formTitle);

        if ($bRec->logo) {
            
            $thmb = new thumb_Img(array($bRec->logo, 400, 400, 'isAbsolute' => TRUE));
            
            $logoUrl = $thmb->getUrl();
            
            list($logoWidth, $logoHeight) = $thmb->getSize();
            
            if ($logoHeight > $logoWidth) {
                $jsTpl->replace($logoUrl, 'logoLeft');
                $jsTpl->replace($formTitle, 'formTitleRight');
            } else {
                $jsTpl->replace($logoUrl, 'logoUp');
                $jsTpl->replace($formTitle, 'formTitle');
            }
        } else {
            $jsTpl->replace($formTitle, 'formTitle');
        }
        
        // Съобщение при абониране
        $successText = addslashes($bRec->formSuccessText);
        $jsTpl->replace($successText, 'successText');
        
        // Съобщение на бутона за показване на формата за абониране
        $showFormBtn = addslashes($bRec->showFormBtn);
        $jsTpl->replace($showFormBtn, 'showFormBtn');

        // Текст на бутона за субмитване
        $submitBtnVal = addslashes($bRec->submitBtnVal);
        $jsTpl->replace($submitBtnVal, 'submitBtnVal');
        
        // Текст на бутона за отказ
        $cancelBtnVal = addslashes($bRec->cancelBtnVal);
        $jsTpl->replace($cancelBtnVal, 'cancelBtnVal');
        
        // Съобщение за невалиден имейл
        $wrongMail = addslashes(tr('Невалиден имейл!'));
        $jsTpl->replace($wrongMail, 'wrongMailText');
        
        // Име на полето за имейл
        $emailName = addslashes(tr('Имейл'));
        $jsTpl->replace($emailName, 'emailName');
        
        // Име на полето за имейл
        $emailName = addslashes(tr('ще го пазим поверително'));
        $jsTpl->replace($emailName, 'weSaveIt');
        
        // Линк за показване на формата
        $showFormUrl = self::getLinkForShowForm($id);
        $showFormUrl = addslashes($showFormUrl);
        $jsTpl->replace($showFormUrl, 'showFormUrl');
        
        // Линк за img за регистрация
        $formActionUrl = self::getLinkForShowImg($id);
        $formActionUrl = addslashes($formActionUrl);
        $jsTpl->replace($formActionUrl, 'formAction');
        
        $cookieKey = self::getCookieName($id);
        $cookieKey = addslashes($cookieKey);
        $jsTpl->replace($cookieKey, 'cookieKey');
        
        $jsTpl->replace(self::getCssLink($id), 'CSS_URL');
        
        $js = $jsTpl->getContent();
        
        if ($bRec->lg) {
            core_Lg::pop();
        }
        
        $js = minify_Js::process($js);
        
        return $js;
    }
    
    
    /**
     * 
     * 
     * @param integer $id
     * 
     * @return string
     */
    protected static function getCookieName($id)
    {
        
        return self::prepareCookieKey('nlst', $id);
    }
    
    
    /**
     * 
     * 
     * @param string $name
     * @param integer $id
     */
    protected static function prepareCookieKey($name, $id)
    {
        $hash = substr(md5(EF_APP_TITLE . '|' . $id), 0, 6);
        
        $cookieName = $name . '_' . $hash;
        
        return $cookieName;
    }
    
    
    /**
     * Вкарва CSS-a, като инлай в подадения стринг
     * 
     * @param string $content
     * 
     * @return string
     */
    protected static function addInlineCSS($str)
    {
        $css = file_get_contents(sbf('css/common.css', "", TRUE)) .
        	"\n" . file_get_contents(sbf('css/Application.css', "", TRUE));
        
        $str = '<div id="begin">' . $str . '<div id="end">';
        
        // Вземаме пакета
        $conf = core_Packs::getConfig('csstoinline');
        
        // Класа
        $CssToInline = $conf->CSSTOINLINE_CONVERTER_CLASS;
        
        if (!$CssToInline) return $str;
        
        // Инстанция на класа
        $inst = cls::get($CssToInline);
        
        // Стартираме процеса
        $str =  $inst->convert($str, $css);
        
        $str = str::cut($str, '<div id="begin">', '<div id="end">');
        
        return $str;
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
        $colorsArr = self::prepareColors($id);
        
        $css = file_get_contents(getFullPath('/marketing/tpl/BulletinCssTpl.txt'));
        
        $cssTpl = new ET($css);
        
        $cssTpl->replace($colorsArr['bgColor'], 'bulletinRegBg');
        $cssTpl->replace($colorsArr['textColor'], 'textColor');
        $cssTpl->replace($colorsArr['buttonColor'], 'btnColor');
        $cssTpl->replace($colorsArr['darkBtnColor'], 'darkBtnColor');
        $cssTpl->replace($colorsArr['shadowBtnColor'], 'shadowBtnColor');
        $cssTpl->replace($colorsArr['btnColorShadow'], 'btnColorShadow');
        
        $css = $cssTpl->getContent();
        
        $css = minify_Css::process($css);
        
        return $css;
    }
    
    
    /**
     * Разделя езика и домейна
     * 
     * @param string $domain
     * 
     * @return array
     */
    protected static function parseDomain($domain)
    {
        $resArr = array();
        
        list($resArr['domain'], $resArr['lang']) = explode(self::$domainLgGlue, $domain);
        
        return $resArr;
    }
    
    
    /**
     * Връща масив с всички цветове, които ще се използват в CSS за формата, текста и бутоните
     * 
     * @param integer $id
     * 
     * @return array
     */
    protected static function prepareColors($id)
    {
        $resArr = array();
        
        $bRec = self::fetch($id);
        
        $resArr['bgColor'] = $bRec->bgColor;
        $resArr['textColor'] = $bRec->textColor;
        $resArr['buttonColor'] = $bRec->buttonColor;
        
        if (!$resArr['bgColor'] && !$resArr['textColor'] && !$resArr['buttonColor']) {
            $dArr = self::parseDomain($bRec->domain);
            $dRec = cms_Domains::fetch(array("#domain = '[#1#]' AND #lang = '[#2#]'", $dArr['domain'], $dArr['lang']));
            
            if ($dRec) {
                $resArr['bgColor'] = $dRec->form->bgColor;
        
                $resArr['textColor'] = $dRec->form->activeColor;
            
                $resArr['buttonColor'] = $dRec->form->baseColor;
            }
        }
        
        if (!$resArr['bgColor']) {
            $resArr['bgColor'] = '#F5F5F5';
        }
        
        if (!$resArr['textColor']) {
            $resArr['textColor'] = '#333333';
        }
        
        if (!$resArr['buttonColor']) {
            $resArr['buttonColor'] = '#3EACBA';
        }
        
        $btnColor = ltrim($resArr['buttonColor'], '#');
        
        $darkBtnColor = phpcolor_Adapter::changeColor($btnColor, 'lighten', 15);
        $resArr['shadowBtnColor'] = '#' . phpcolor_Adapter::changeColor($darkBtnColor, 'mix', 1, '#444');
        
        $resArr['darkBtnColor'] = '#' . $darkBtnColor;
        
        if(phpcolor_Adapter::checkColor($btnColor, 'light'))  {
            $resArr['btnColorShadow'] = ' ';
        }
        
        return $resArr;
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
        
        $form->setDefault('formTitle', tr('Искате ли да научавате всички новости за нас?'));
        $form->setDefault('formSuccessText', tr('Благодарим за абонамента за нашите новости'));
        $form->setDefault('showFormBtn', tr('Абонамент за новости'));
        $form->setDefault('submitBtnVal', tr('Абонирам се за бюлетина'));
        $form->setDefault('cancelBtnVal', tr('Не, благодаря'));
        $form->setDefault('delayBeforeOpenInHit', '5'); // 5 секунди
        $form->setDefault('delayAfterClose', '3600'); // 1 часа
        $form->setDefault('delayBeforeOpen', '60'); // 1 мин
        
        $langQuery = drdata_Languages::getQuery();
        $langOpt = array();
        while($lRec = $langQuery->fetch()) {
            $langOpt[$lRec->code] = $lRec->languageName;
        }
        $data->form->setOptions('lg', $langOpt);
        
        $form->setDefault('lg', core_Lg::getCurrent());
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
        
        header('Content-Type: application/javascript');
        
        // Да не се кешира
        header('Cache-Control: no-cache, must-revalidate'); // HTTP 1.1.
		header('Pragma: no-cache'); // HTTP 1.0.
		header('Expires: 0'); // Proxies.
        
        // Ако има имейл регистриран от този браузър
        // Ако име абонамент за бюлетина
        // Или ако има логване от този браузър
        if (($haveEmail = log_Browsers::getVars(array('email')))
            || ($haveRec = marketing_BulletinSubscribers::haveRecForIp($id))
            || ($isLogged = core_LoginLog::isLoggedBefore())) {
            
            if ($haveEmail) {
                vislog_History::add('Не показана форма за бюлетина (има имейл за brid)');
            }
            
            if ($haveRec) {
                vislog_History::add('Не показана форма за бюлетина (абониране от това IP)');
            }
            
            if ($isLogged) {
                vislog_History::add('Не показана форма за бюлетина (има логване)');
            }
            
            self::setCookieToNo($id);
            
            shutdown();
        }
        
        $cookieName = self::getCookieName($id);
        if ($_COOKIE[$cookieName] == 'no') {
            vislog_History::add('Не показана форма за бюлетина (nlst=no)');
            shutdown();
        }
        
        $cnt = (int) vislog_History::add('Автоматично показване на формата за бюлетина', TRUE);
        
        $js = $bRec->data['showWindowJS'] . " var showedTime = {$cnt};";
        
        echo $js;
        
        shutdown();
    }
    
    
    /**
     * Екшън който експортира данните
     */
    public function act_Export()
    {
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
   
    	// Проверка за права
    	$this->requireRightFor('export', $rec);
    
    	$detail = cls::get('marketing_BulletinSubscribers');
    	
    	// Масива с избраните полета за export
    	$exportFields = $detail->selectFields("#export");
    	
    	// Ако има избрани полета за export
    	if (count($exportFields)) {
    		foreach($exportFields as $name => $field) {
    			$listFields[$name] = tr($field->caption);
    		}
    	}
    	
    	// взимаме от базата целия списък отговарящ на този бюлетин
    	$queryDetail = marketing_BulletinSubscribers::getQuery();
    	$queryDetail->where("#bulletinId = '{$id}'");
    	
    	while ($recs = $queryDetail->fetch()) {
    		$detailRecs[] = $recs; 
    	}

    	$csv = csv_Lib::createCsv($detailRecs, $detail, $listFields);
    	
    	$listTitle = $this->title. " за домейн ". self::fetchField("#id = '{$rec->id}'", 'domain');
    	
    	$fileName = str_replace(' ', '_', Str::utf2ascii($listTitle));
    	
    	// правим CSV-то
    	header("Content-type: application/csv");
    	header("Content-Disposition: attachment; filename={$fileName}.csv");
    	header("Pragma: no-cache");
    	header("Expires: 0");
    	 
    	echo $csv;
    
    	shutdown();
    }
    
    
    /**
     * Задава стойност на кукито да е `no`
     * 
     * @param integer $id
     */
    protected static function setCookieToNo($id)
    {
        // 10 години от сега
        setcookie(self::getCookieName($id), 'no', time() + (315360000), '/');
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
            
            try {
                marketing_BulletinSubscribers::addData($id, $email);
                self::setCookieToNo($id);
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
    protected static function on_AfterUpdateDetail(core_Master $mvc, $id, core_Manager $detailMvc)
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
        
        if ($rec && ($action == 'export')) {
        	if (!haveRole('ceo, marketing', $userId)) {
        		if ($rec->createdBy != $userId) {
        			$requiredRoles = 'no_one';
        		}
        	}
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        if (blast_Emails::haveRightFor('add') && $data->rec->subscribersCnt) {
            
            Request::setProtected(array('perSrcObjectId', 'perSrcClassId'));
            
            $data->toolbar->addBtn('Циркулярен имейл', array('blast_Emails', 'add', 'perSrcClassId' => core_Classes::getId($mvc), 'perSrcObjectId' => $data->rec->id),
            'id=btnEmails','ef_icon = img/16/emails.png,title=Създаване на циркулярен имейл');
        }
        
        if($mvc->haveRightFor('export', $data->rec)){
        	$data->toolbar->addBtn('Експорт в CSV', array($mvc, 'export', $data->rec->id), NULL, 'ef_icon = img/16/file_extension_xls.png, title = Сваляне на записите в CSV формат,row=2');
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
     *
     * @return array
     */
    public function getPersonalizationOptions($userId = NULL)
    {
        $resArr = array();
        $query = $this->getQuery();
        $query->where("#state='active'");
        $query->where("#subscribersCnt > 0");
        
        while ($rec = $query->fetch()) {
            $resArr[$rec->id] = $rec->domain;
        }
        
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
        if ($this->haveRightFor('single', $id, $userId)) {
            
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
        $rec = $this->fetch((int) $id);
        
        return $rec->domain;
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
        $link = ht::createLink($title, array($this, 'single', $id));
        
        return $link;
    }
    
    
    /**
     * Връща езика за източника на персонализация
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param integer $id
     *
     * @return string
     */
    public function getPersonalizationLg($id)
    {
        $rec = $this->fetch($id);
        
        return $rec->lg;
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
        $query = marketing_BulletinSubscribers::getQuery();
        
        $query->where("#bulletinId = $id");
        
        if ($limit) {
            $query->limit($limit);
        }
        
        $resArr = array();
        
        while ($rec = $query->fetch()) {
            $resArr[$rec->id] = array('email' => $rec->email, 'person' => $rec->name, 'company' => $rec->company);
        }
        
        return $resArr;
    }
}
