<?php

/**
 * bgERP система
 *
 *
 * @category  bgerp
 * @package   embed
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class remote_BgerpDriver extends core_Mvc
{

	/**
     * Поддържа интерфейса за драйвер
     */
    public $interfaces = 'remote_ServiceDriverIntf';


    /**
     * Заглавие на драйвера
     */
    public $title = 'bgERP система';


    /**
     * Плъгини и класове за зареждане
     */
    public  $loadList = 'crm_Wrapper';
    

    /**
     * Таб във wrapper-a
     */
    public $currentTab = 'Профили';


    /**
     * Публичен ключ за обмен на информация между 2 bgERP системи
     */
    const PUBLIC_KEY = 'REMOTE_BGERP';


    /**
     * Шаблон за случаен ключ
     */
    const KEY_PATTERN = '************';


    /**
     * Максимално отклонение между времето на създаване на 2 bgERP системи
     */
    const MAX_TOKEN_DEVIATION_TIME = 7200;
   

    /**
     * Шаблон за случайната част на token
     */
    const TOKEN_RAND_PATTERN = '******';


    /**
     * Дали да се прави обновяване по крон на shutdown
     */
    private $cronUpdate = FALSE;


    /**
	 * Добавя полетата на драйвера към Fieldset
	 * 
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
        if(isset($fieldset->fields['url'])) {
            $fieldset->setField('url', 'hint=Адрес на друга bgERP система');
        }
	}

    
    /**
     * След конвертиране към вербални стойности на записа
     */
    function on_AfterRecToVerbal($driver, $mvc, $row, $rec)
    {
        if(!$rec->data->lKeyCC) {
            
            if ($rec->userId == core_Users::getCurrent()) {
                $authOutArr = array($driver, 'AuthOut', $rec->id);
            } else {
                $authOutArr = array();
            }
            
            $row->auth = ht::createBtn("Получаване", $authOutArr, NULL, 'target=_blank');
        } else {
            
            if ($rec->userId == core_Users::getCurrent()) {
                $row->url = ht::createLink($rec->url, array($driver, 'Autologin', $rec->id, 'url' => $rec->url));
            }
            
            $row->auth = ht::createLink('Получена', NULL, NULL, 'ef_icon=img/16/checked-green.png');
        }
        if($rec->data->rKeyCC) {
            $row->auth .= '&nbsp;' . ht::createLink('Дадена', NULL, NULL, 'ef_icon=img/16/checked-orange.png');
        }
    }


    /**
     * За да не могат да се редактират оторизациите с получен ключ
     */
    public static function on_AfterGetRequiredRoles($driver, $mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{
        if($action == 'edit' && is_object($rec)) {
            if($rec->data->lKeyCC) {
                $res = 'no_one';
            }
        }
    }


    /**
     * Връща ключа за криптираната връзка
     *
     * @param $rec      stdClass    Запис на оторизация
     * @param $type     string      'remote' или 'local'
     * 
     */
    static function getCryptKey($rec, $type = 'local')
    {
        if($type == 'local') {
            $key = $rec->data->lKey . $rec->data->lKeyCC;
        } else {
            $key = $rec->data->rKey . $rec->data->rKeyCC;
        }

        return $key;
    }

	
	/**
	 * Може ли вградения обект да се избере
	 */
	public function canSelectDriver($userId = NULL)
	{
    	return TRUE;
	}


    /**
     * Изходящо оторизиране. Създава изходяща връзка към bgERP
     */
    function act_AuthOut()
    {
        expect($id = Request::get('id', 'int'));

        requireRole('powerUser');

        expect($rec = remote_Authorizations::fetch($id));
        
        expect($rec->userId == core_Users::getCurrent());

        $url = remote_Authorizations::canonizeUrl($rec->url);

        // $data:
        // ->lKey - ключа, с който ще кодираме нашите заявки
        // ->rKey - ключа с който ще са кодирани заявките към нас
        // ->confirmed - оторизацията е потвърдена и ние можем да изпращаме заявки

        if(!is_object($rec->data)) {
            $rec->data = new stdClass();
        }
        if(!isset($rec->data->lKey)) {
            $rec->data->lKey = str::getRand(self::KEY_PATTERN);
        }

        $params = array('url' => str_replace('/xxx/', '', toUrl(array('xxx'), 'absolute')),
                        'nick' => core_Users::getCurrent('nick'),
                        'key' => $rec->data->lKey,
                        'authId' => $rec->id);

        $Fw = urlencode(core_Crypt::encodeVar($params, self::PUBLIC_KEY));

        $url .= '/remote_BgerpDriver/AuthIn/?Fw=' . $Fw;

        remote_Authorizations::save($rec);
        
        remote_Authorizations::logInfo('Изходящ оторизиране', $rec->id);
        
        return new Redirect($url);
    }


    /**
     * Входящ екщън за оторизиране
     */
    function act_AuthIn()
    {
        requireRole('powerUser');
        
        if(!core_Packs::isInstalled('remote')) {
            if(haveRole('admin')) {
                return new Redirect(array('core_Packs', 'search' => 'remote'), "За оторизиране на отдалечена bgERP система, трябва да бъде инсталиран пакета `remote`", 'error');
            } else {
                return new Redirect(array('Portal', 'Show'), "За оторизиране на отдалечена bgERP система, трябва да бъде инсталиран пакета `remote`. Свържете се с вашия IT администратор.", 'error');
            }
        }

        expect($Fw = Request::get('Fw'));

        $params = core_Crypt::decodeVar($Fw, self::PUBLIC_KEY);

        $form = cls::get('core_Form');
        
        expect(strlen($params['key']) == strlen(self::KEY_PATTERN));
        expect(core_Url::isValidUrl($params['url']));
        expect(is_numeric($params['authId']) && $params['authId'] > 0, $params['authId']);

        $form->title = 'Потвърждение на отдалечен потребител';
        $form->info = new ET(tr("Желаете ли да оторизирате потребителя |*<b>[#1#]</b>| от |*<b>[#2#]</b>| да следи вашите съобщения?"), 
                            type_Varchar::escape($params['nick']),
                            type_Varchar::escape($params['url'])    
                            );
        $form->setHidden('Fw', $Fw);
        $form->FNC('captcha', 'captcha_Type', 'caption=Разпознаване,hint=Въведи кода от картинката,input');

        $form->toolbar->addSbBtn('Напред', 'default', array('class'=>'fright'), 'ef_icon = img/16/move.png');
        $form->toolbar->addBtn('Отказ', toUrl(array('Portal')), 'ef_icon = img/16/close-red.png');

        $r = $form->input();
        
        $cu = core_Users::getCurrent();
        $rec = remote_Authorizations::fetch(array("#userId = [#1#] AND #url = '[#2#]'", $cu, $params['url']));
        
        if($rec->data->rKey && $rec->data->rKeyCC) {
            $nick = type_Varchar::escape($params['nick']);
            $url = type_Varchar::escape($params['url']);
            
            remote_Authorizations::logErr('Опит за нова оторизация на отдалечения потребител', $rec->id);
            
            return new Redirect(crm_Profiles::getUrl(core_Users::getCurrent()), 
                "|Опит за нова оторизация на отдалечения потребител|* <b>{$nick}</b> ({$url}) |да следи вашите съобщения", 'error');
        }

        if($form->isSubmitted()) {
            if($rec) {
                expect($rec->driverClass == core_Classes::getId(__CLASS__));
            } else {
                $rec = new stdClass();
                $rec->driverClass = core_Classes::getId(__CLASS__);
                $rec->data = new stdClass();
            }
            $rec->data->rKey = $params['key'];
            $rec->data->rId =  $params['authId'];
            $rec->data->rKeyCC = str::getRand(self::KEY_PATTERN);
            $rec->url = $params['url'];
            $rec->userId = $cu;

            remote_Authorizations::save($rec);

            // Искаме потвърждение
            $sendParams = array('authId' => $rec->id, 'CC' => $rec->data->rKeyCC);

            $url = remote_Authorizations::canonizeUrl($params['url']);

            $Fw = urlencode(core_Crypt::encodeVar($sendParams, $params['key']));

            $url .= "/remote_BgerpDriver/AuthConfirm/?authId={$rec->data->rId}&Fw=" . $Fw;
            
            $confirm = file_get_contents($url);

                
            if($confirm == md5($rec->data->rKey . $rec->rKeyCC)) {
                $rec->data->rConfirmed = $confirm;
                remote_Authorizations::save($rec);
            }
            
            remote_Authorizations::logInfo('Успешна оторизация за следене на съобщения', $rec->id);
            
            return new Redirect(crm_Profiles::getUrl(core_Users::getCurrent()), 
                "|Отдалечения потребител|* " . type_Varchar::escape($params['nick']) . " |е оторизиран да следи вашите съобщения");
        }

        return $this->renderWrapping($form->renderHtml());
    }


    /**
     * Извиква се за потвърждаване на оторизацията
     * Трябва да има в Request:
     * o $authId - ид на оторизацията
     * o $params - кодирани с $lKey на връзката параметри, id, url, nick, $lKeyConfirmCode
     */
    function act_AuthConfirm()
    {
        expect($authId = Request::get('authId'));

        expect($rec = remote_Authorizations::fetch($authId));
        
        expect($Fw = Request::get('Fw'));

        expect($params = core_Crypt::decodeVar($Fw, $rec->data->lKey));

        expect($params['authId'] > 0);
        $rec->data->rId = $params['authId'];

        expect(strlen($params['CC']) == strlen(self::KEY_PATTERN));
        
        $rec->data->lKeyCC = $params['CC'];

        remote_Authorizations::save($rec);
        
        remote_Authorizations::logInfo('Потвърдена ауторизация', $rec->id);
        
        echo md5($rec->data->lKey . $rec->lKeyCC);

        die;
    }


    /**
     * Крон процес за обновяване на известията от оторизираните системи
     */
    function cron_UpdateRemoteNotification()
    {
        $query = remote_Authorizations::getQuery();
        
        $dc = core_Classes::getId(__CLASS__);

        while($rec = $query->fetch("#driverClass = $dc AND #state = 'active'")) {
            if($rec->data->lKeyCC && $rec->data->rId) {
                
                $nCnt = self::sendQuestion($rec, __CLASS__, 'getNotifications');
                
                // Прескачаме, ако липсва отговор на въпроса
                if($nCnt === NULL) continue;;

                $nUrl = array($this, 'Autologin', $rec->id);
                $userId = $rec->userId;

                if($nCnt > 0) {
                    if($nCnt == 1) {
                        $nCnt = '|едно ново известие|*';
                    } else {
                        $nCnt .= ' |нови известия|*';
                    }
                    $url = str_replace(array('http://', 'https://'), array('', ''), $rec->url);
                    $message = "|Имате|* {$nCnt} |в|* {$url}";

                    // Добавя, ако няма нофификация
                    bgerp_Notifications::add($message, $nUrl, $userId, NULL, NULL, TRUE);
                } else {
                    bgerp_Notifications::clear($nUrl, $userId);
                }
            }
        }
    }


    /**
     * Автоматично логване в оталечена система
     */
    function act_Autologin()
    {
        expect($id = Request::get('id', 'int'));
        
        requireRole('user');

        expect($userId = core_Users::getCurrent());

        $url = Request::get('url', 'type_Url');
        
        $arr = array();

        if(!$url) {
            bgerp_Notifications::clear(array($this, 'Autologin', $id), $userId);
        } else {
            $arr['url'] = $url;
        }
        
        expect($auth = remote_Authorizations::fetch($id));

        expect($auth->userId == $userId);
        
        $url = self::prepareQuestionUrl($auth, __CLASS__, 'Autologin', $arr);
        
        remote_Authorizations::logLogin('Автоматично логване', $id);

        $this->cronUpdate = TRUE;
        
        return new Redirect($url);
    }


    /**
     * Извиква се на on_Shutdown и обновява състоянието на нотификлациите
     */
    function on_Shutdown()
    {
        $me = cls::get('remote_BgerpDriver');
        if($me->cronUpdate) {
            core_App::flushAndClose();
            sleep(5);
            Debug::log('Sleep 5 sec. in' . __CLASS__);

            $me->cron_UpdateRemoteNotification();
        }
    }


    /**
     * Генерира редирект за автоматично логване в отдаличена система
     */
    function remote_Autologin($auth, $args)
    {
        expect($auth);

        expect($auth =  self::prepareAuth($auth));
 
        if(!haveRole('user')) {
            core_Users::loginUser($auth->userId);
        }
 
        if($url = $args['url']) {
            redirect($url);
        } else {
            redirect(array('bgerp_Portal', 'Show'));
        }
    }


    /**
     * Връща броя на нотификациите за посочения потребител от оторизацията
     */
    function remote_getNotifications($authId)
    {
        expect($authId);
        
        expect($rec = remote_Authorizations::fetch($authId));

        $cnt = bgerp_Notifications::getNewCntFromLastOpen($rec->userId);

        return $cnt;        
    }


    /**
     * Създава временен код с посочените параметри
     */
    public static function getToken()
    {
        $token = round(time()/60) . '-' . str::getRand(self::TOKEN_RAND_PATTERN);
        
        return $token;
    }


    /**
     * Проверява дали аргумента е валиден token за bgERP комуникация
     */
    public static function getTokenExpiry($token)
    {
        list($t, $r) = explode('-', $token);
        
        // Невалиден $token, ако времевата компонента не е по-голяма от 0
        if(!($t>0)) {

            return FALSE;
        }
        
        /**
         * Невалиден $token, ако случайната част не отговаря на дължината на шаблона
         */
        if(strlen($r) != strlen(self::TOKEN_RAND_PATTERN)) {

            return FALSE;
        }

        // Невалиден $token, ако между времето на издаване и използване има повече от MAX_TOKEN_DEVIATION минути
        if(abs($t*60-time()) > self::MAX_TOKEN_DEVIATION_TIME) {

            return FALSE;
        }

        $expiryDate = date('Y-m-d H:i:s', $t*60 + 2*self::MAX_TOKEN_DEVIATION_TIME);

        return $expiryDate;
    }


    /**
     * Кодира въпроса
     * 
     * @return  string  BASE64 кодиран и криптиран стринг 
     */
    public static function encode($auth, $params, $type = 'question')
    {
        $auth =  self::prepareAuth($auth);
        
        if($type == 'question') {
            $key = $auth->data->lKey . $auth->data->lKeyCC;
        } else {
            expect($type == 'answer');
            $key = $auth->data->rKey . $auth->data->rKeyCC;
        }

        $params['_token'] = self::getToken();

        $encodedParams = core_Crypt::encodeVar($params, $key, 'json');

        return $encodedParams;
    }
    

    /**
     * Декодира отговора
     *
     * @return  array
     */
    public static function decode($auth, $encodedParams, $type = 'answer')
    {
        $auth =  self::prepareAuth($auth);
        
        if($type == 'answer') {
            $key = $auth->data->lKey . $auth->data->lKeyCC;
        } else {
            expect($type == 'question');
            $key = $auth->data->rKey . $auth->data->rKeyCC;
        }
        $params = core_Crypt::decodeVar($encodedParams, $key, 'json');

        if(!is_array($params)) return FALSE;

        $expiryDate = self::getTokenExpiry($params['_token']);

        if(!$expiryDate) return FALSE;

        if(remote_Tokens::storeToken($auth->id, $params['_token'], $expiryDate)) {
            unset($params['_token']);

            return $params;
        }
    }

    /**
     * Подготвя URL за извикване на отдалечена функция
     */
    public static function prepareQuestionUrl($auth, $ctr, $act, $args = NULL)
    {
        $auth =  self::prepareAuth($auth);
        
        $params = array('Ctr' => $ctr, 'Act' => $act, 'args' => $args);

        $encodedParams = urlencode(self::encode($auth, $params, 'question'));

        $url = $auth->url;
 
        $url .= "/remote_BgerpDriver/Answer/?authId={$auth->data->rId}&E=" . $encodedParams;
        
        return $url;
    }


    /**
     * Задава въпрос и връща отговора по криптиран канал
     */
    public static function sendQuestion($auth, $ctr, $act, $args = NULL)
    {
        $url = self::prepareQuestionUrl($auth, $ctr, $act, $args);

        $res = @file_get_contents($url);

        if($res) {
            $params = self::decode($auth, $res, 'answer');
     
            return $params['result'];
        }
    }
    

    /**
     * Екшън, който се вика от отдалечена машина
     */
    public static function act_Answer()
    {
        expect($authId = Request::get('authId', 'int'));
        expect($encodedParams = Request::get('E'));

        $auth =  self::prepareAuth($authId);

        $params = self::decode($auth, $encodedParams, 'question');
 
        expect($ctr = $params['Ctr']);
        expect($act = $params['Act']);

        $act = "remote_" . $act;
        
        $inst = cls::get($ctr);
 
        $res = array('result' => $inst->{$act}($auth->id, $params['args']));

        $encodedRes = self::encode($auth, $res, 'answer');

        echo $encodedRes;
        
        remote_Authorizations::logInfo('Вземане на данни', $authId);
        
        shutdown();
    }
    

    /**
     * Извлича записа на оторизацията с посочения номер
     */
    private static function prepareAuth($auth)
    {
        if(!is_object($auth)) {
            expect($auth = (int) $auth);
            $auth = remote_Authorizations::fetch($auth);
        } else {
            expect($auth->id > 0);
        }

        return $auth;
    }

/*
    function remote_Test($authId, $args)
    {
        return $args . " - OK - $authId";
    }


    function act_Test()
    {
        $args = 'test';
        $ctr = 'remote_BgerpDriver';
        $act = 'test';

        $auth = remote_Authorizations::fetch('1=1');

        $res = self::sendQuestion($auth, $ctr, $act, $args);

        return $res;
    }
    */

}