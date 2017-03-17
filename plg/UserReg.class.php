<?php


/**
 * Типа на записите в кеша
 */
defIfNot('USERREG_CACHE_TYPE', 'UserReg');


/**
 * Съобщение, което получава потребителя след регистрация
 */
defIfNot('USERREG_THANK_FOR_REG_MSG',
    "Благодарим Ви за регистрациятa|*!" .
    "<br><br>|На посочения от вас адрес беше изпратено писмо със заглавие \"Активация на потребител\"|*." .
    "<br>|В него се съдържа линк, чрез който трябва да зададете вашата парола за|* [#EF_APP_TITLE#] ." .
    "<br><br>|Поздрави от екипа!");


/**
 * Съобщение, което получава потребителя след заявка за смяна на паролата
 */
defIfNot('USERREG_THANK_FOR_RESET_PASS_MSG',
    "Заявката за смяната на паролата е приета|*!" .
    "<br><br>|На посочения от вас адрес беше изпратено писмо със заглавие \"Промяна на парола\"|*." .
    "<br>|В него се съдържа линк, чрез който трябва да зададете вашата нова парола за|* [#EF_APP_TITLE#] ." .
    "<br><br>|Поздрави от екипа!");


/**
 * Писмо до потребителя за активация
 */
defIfNot('USERREG_ACTIVATION_EMAIL',
    "\n|Уважаеми|* [#names#]|," .
    "\n" .
    "\n|Благодарим за регистрацията Ви|*." .
    "\n" .
    "\n|За да активирате акаунта си, моля следвайте следния линк|*:" .
    "\n" .
    "\n[#url#]" .
    "\n" .
    "\n|Линка ще изтече в|* [#regLifetime#]." .
    "\n" .
    "\n|Поздрави|*," .
    "\n[#senderName#]");


/**
 * Писмо до потребителя за смяна на паролата
 */
defIfNot('USERREG_RESET_PASS_EMAIL',
    "\n|Уважаеми|* [#names#]," .
    "\n" .
    "\n|Получихме заявка за промяна на паролата Ви|*." .
    "\n" .
    "\n|За да промените паролата, моля използвайте следния линк|*:" .
    "\n" .
    "\n[#url#]" .
    "\n" .
    "\n|Линка ще изтече в|* [#regLifetime#]." .
    "\n" .
    "\n|Поздрави|*," .
    "\n[#senderName#]");


/**
 * Клас 'plg_UserReg' - Самостоятелна регистрация на потребителите
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_UserReg extends core_Plugin
{
    
    
    /**
     * Извиква се след изпълняването на екшън
     */
    function on_AfterAction(&$invoker, &$tpl, $act)
    {
        if (strtolower($act) == 'login' && !Request::get('popup')) {
            
            $className = "class=login-links";
            
            if ($invoker->haveRightFor('resetpassout')) {
                
                $tpl->append("<p>&nbsp;<A HREF='" .
                    toUrl(array($invoker, 'resetPassForm')) .
                    "' {$className} rel='nofollow'>»&nbsp;" . tr('Забравена парола||Forgot Password') . "?</A>", 'FORM');
            }
            
            if ($invoker->haveRightFor('registernewuserout')) {
                $tpl->append("<p>&nbsp;<A HREF='" .
                toUrl(array($invoker, 'registerNewUser')) .
                "'  {$className} rel='nofollow'>»&nbsp;" . tr('Нова регистрация||Create account') . "</A>", 'FORM');
            }
        }
    }
    
    
    /**
     * Изпълнява се след получаването на необходимите роли
     */
    public static function on_AfterGetRequiredRoles(&$invoker, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        $conf = core_Packs::getConfig('core');
        
        if ($action == 'resetpassout') {
            if ($conf->CORE_RESET_PASSWORD_FROM_LOGIN_FORM == 'yes') {
                $requiredRoles = 'every_one';
            } else {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'registernewuserout') {
            if ($conf->CORE_REGISTER_NEW_USER_FROM_LOGIN_FORM == 'yes') {
                $requiredRoles = 'every_one';
            } else {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     */
    function on_BeforeAction($mvc, &$content, &$act)
    {
    	$conf = core_Packs::getConfig('core');
    	
    	if ($act == 'activate' || $act == 'changepass' || ($act == 'unblock')) {
    	    expect($id = Request::get('id', 'identifier'));
    	    
    	    $userId = (int) core_Cache::get(USERREG_CACHE_TYPE, $id);
    	    
    	    if (!$userId || (!$rec = $mvc->fetch($userId))) {
    	        redirect(array('Index'), FALSE, '|Този линк е невалиден. Вероятно е използван или е изтекъл.', 'error');
    	    }
    	    
    	    // Проверка дали състоянието съответства на действието
    	    if ($rec->state != 'draft' && $act == 'activate') {
    	        redirect(array('Index'), FALSE, '|Този акаунт е вече активиран.', 'error');
    	    }
    	    
    	    if ($rec->state == 'draft' && $act == 'changePass') {
    	        redirect(array('Index'), FALSE, '|Този акаунт все още не е активиран.', 'error');
    	    }
    	    
    	    if ($rec->state != 'blocked' && $act == 'unblock') {
                redirect(array('Index'), FALSE, '|Този акаунт вече е бил отблокиран.', 'error');
    	    }
    	}
    	
        if ($act == 'registernewuser') {
            
            $mvc->requireRightFor('registernewuserout');
            
            $form = $mvc->getForm();
            
            $form->setField('email', "valid=drdata_Emails->validate");
            
            if (EF_USSERS_EMAIL_AS_NICK) {
                $rec = $form->input("email,names");
            } else {
                $rec = $form->input("nick,email,names");
            }
            
            if ( $form->isSubmitted() && $rec) {
                // Ако е конфигурирано да се използва имейлът за ник,
                // То имейлът се записва като Nick
                if (EF_USSERS_EMAIL_AS_NICK) {
                    $rec->nick = $rec->email;
                }
                
                // Проверка дали никът не се повтаря
                if ($eRec = $mvc->fetch("#nick = '{$rec->nick}'")) {
                    if (EF_USSERS_EMAIL_AS_NICK) {
                        if ($eRec->state == 'active') {
                            $form->setError('email', "Вече има регистриран потребител с този имейл. Ако сте забравили паролата си, можете да я възстановите тук");
                        } else {
                            $form->setError('email', "Вече има регистриран потребител с този имейл. " .
                                "Моля проверете всички папки, в т.ч. ако имате и папката за СПАМ, за имейл със заглавие 'Activation'. " .
                                "В него се съдържат инструкции за активиране на вашата сметка. Ако не откриете писмото опитайте да се " .
                                "регистрирате чрез друг ваш имейл адрес или направете опит с този след няколко дни.");
                        }
                    } else {
                        $nicks = $this->nickGenerator($mvc, $rec->email, $rec->names);
                        
                        foreach ($nicks as $n) {
                            $htmlNicks .= ($htmlNicks ? ", " : "") . "<B>{$n}</B>";
                        }
                        $form->setError('nick', "Вече има регистриран потребител с този ник. Изберете друг, например: " . $htmlNicks);
                    }
                } else {
                    // проверка дали имейлът не се повтаря
                    if ($mvc->fetch("#email = '{$rec->email}'")) {
                        $form->setError('email', "Вече има регистриран потребител с този имейл.");
                    }
                }
                
                if (!$form->gotErrors()) {
                    
                    // Ако всичко е точно, записваме данните, генерираме съобщение и го изпращаме
                    $rec->state = 'draft';
                    $mvc->save($rec);
                    
                    core_LoginLog::add('user_reg', $rec->id);
                    $mvc->logLogin('Регистриране на потребител', $rec->id);
                    
                    // Тук трябва да изпратим имейл на потребителя за активиране
                    if ($mvc->sendActivationLetter($rec, USERREG_ACTIVATION_EMAIL, 'Активация на потребител', 'activate') == FALSE) {
                        redirect(array('Index'), FALSE, "За съжеление възникна грешка и не успяхме да изпратим имейла за активиране. Опитайте по-късно или се свържете със системния администратор", 'error');
                    }
                    
                    // Редиректваме към страницата, която благодари за регистрацията
                    $msg = new ET(USERREG_THANK_FOR_REG_MSG);
                    $msg->placeObject($rec);
                    
                    $conf = core_Packs::getConfig('core');
                    $msg->replace($conf->EF_APP_TITLE, 'EF_APP_TITLE');

                    redirect(array('Index'), FALSE, '|' . $msg->getContent());
                }
            }
            
            // Показваме формата. Първо леко променяме стила. TODO в CSS
            $form->styles = array(
                '.formInfo' => 'max-width:440px;padding:8px;border:solid 1px #999;background-color:#FFC;font-family:Times New Roman;font-size:0.9em;',
                '.formError' => 'max-width:440px;padding:8px;border:solid 1px #f99;background-color:#FF9;font-family:Times New Roman;font-size:0.9em;'
            );
            
            $form->toolbar->addSbBtn('Регистрирай');
            
            $form->title = "Регистриране на нов потребител в|* \"" . $conf->EF_APP_TITLE . "\"";
            
            if (!$form->gotErrors()) {
                $form->info = tr("След като попълните полетата по-долу натиснете бутона \"Регистрирай\".|*<br>|" .
                    "На посочения от Вас имейл ще получите линк за избор на паролата за достъп.");
            }
            
            
            if (EF_USSERS_EMAIL_AS_NICK) {
                $content = $form->renderHtml("email,names", $rec);
                $form->addAttr("email,names", array('style' => 'width:300px'));
            } else {
                $content = $form->renderHtml("nick,email,names", $rec);
                $form->addAttr("email,names,nick", array('style' => 'width:300px'));
            }
            
            return FALSE;
        } elseif ($act == 'activate' || $act == 'changepass') {
            
            $form = cls::get('core_Form');
            
            $form->setAction(array('core_Users', 'changePass'));
                        
            //Ако е активирано да се използват имейлите, като никове тогава полето имейл го правим от тип имейл, в противен случай от тип ник
            if (EF_USSERS_EMAIL_AS_NICK) {
                //Ако използваме имейлите вместо никове, скриваме полето ник
                $form->FLD('email', 'email(link=no)', 'caption=Имейл,mandatory,width=100%');
                $nickField = 'email';
            } else {
                //Ако не използвам никовете, тогава полето трябва да е задължително
                $form->FLD('nick', 'nick(64)', 'caption=Ник,mandatory,width=100%');
                $nickField = 'nick';
            }
            
            $form->setDefault($nickField, $rec->{$nickField});
            $form->setReadOnly($nickField);

            if ($act == 'activate') {
                // Нова парола и нейния производен ключ
                $minLenHint = 'Паролата трябва да е минимум|* ' . EF_USERS_PASS_MIN_LEN . ' |символа';
                $form->FNC('passNew', 'password(allowEmpty,autocomplete=off)', "caption=Вашата парола,input,hint={$minLenHint},width=15em");
                $form->FNC('passNewHash', 'varchar', 'caption=Хеш на паролата,input=hidden'); 
                
                // Повторение на новата парола
                $passReHint = 'Въведете отново паролата за потвърждение, че сте я написали правилно';
                $form->FNC('passRe', 'password(allowEmpty,autocomplete=off)', "caption=Нова парола (пак),input,hint={$passReHint},width=15em");

                $form->title = "Активиране на вашия достъп до системата";
                $form->info = tr("За да си активирате достъпа до системата, моля въведете избраната " .
                "от вас парола в полетата по-долу. " . "Паролата трябва да е поне|* " .
                EF_USERS_PASS_MIN_LEN . " |символа и да съдържа букви, цифри и други символи.");

            } else {
                // Нова парола и нейния производен ключ
                $minLenHint = 'Паролата трябва да е минимум|* ' . EF_USERS_PASS_MIN_LEN . ' |символа';
                $form->FNC('passNew', 'password(allowEmpty,autocomplete=off)', "caption=Новата парола,input,hint={$minLenHint},width=15em");
                $form->FNC('passNewHash', 'varchar', 'caption=Хеш на новата парола  ч,input=hidden'); 
                
                // Повторение на новата парола
                $passReHint = 'Въведете отново паролата за потвърждение, че сте я написали правилно';
                $form->FNC('passRe', 'password(allowEmpty,autocomplete=off)', "caption=Нова парола (пак),input,hint={$passReHint},width=15em");

                $form->title = "Задаване на нова парола";
                $form->info = tr("За да смените паролата си за достъп до системата, моля въведете новата " .
                "парола в полетата по-долу. " . "Паролата трябва да е поне|* " .
                EF_USERS_PASS_MIN_LEN . " |символа и да съдържа букви, цифри и други символи.");
            }

            core_Users::setUserFormJS($form);
             
            $form->FNC('id', 'identifier', 'input=hidden');
            $form->FLD('ret_url', 'varchar', 'input=hidden,silent');

            $form->toolbar->addSbBtn('Изпрати');
            
            
            $pRec = $form->input();
            
            if($form->isSubmitted()) {
                core_Users::calcUserForm($form);
               
                if($pRec->isLenOK == -1) {
                    $form->setError('passNew', 'Паролата трябва да е минимум |* ' . EF_USERS_PASS_MIN_LEN . ' |символа');
                } elseif(!$pRec->passNewHash) {
                    $form->setError('passNew,passRe', 'Моля, въведете (и повторете) паролата');
                } elseif($pRec->passNew != $pRec->passRe) {
                    $form->setError('passNew,passRe', 'Двете пароли не съвпадат');
                }  
                
                if (!$form->gotErrors()) {
                    $rec->ps5Enc = $pRec->passNewHash;
                    $rec->state = 'active';
                    $mvc->save($rec, 'state,ps5Enc');
                    
                    if ($act == 'activate') {
                        core_LoginLog::add('user_activate', $rec->id);
                        $mvc->logLogin('Активиране на потребител', $rec->id);
                    } else {
                        core_LoginLog::add('pass_change', $rec->id);
                        $mvc->logLogin('Промяна на парола', $rec->id);
                    }
                    
                    core_Cache::remove(USERREG_CACHE_TYPE, $id);
                    
                    // Добавяме права на потребителя - user, partner
                    if(!haveRole('user', $userId)) {
                        core_Users::addRole($userId, 'user');
                    }
                    if(!haveRole('executive', $userId)) {
                        core_Users::addRole($userId, 'partner');
                    }

                    redirect(array('core_Users','login', 'ret_url' => toUrl(array('Portal', 'Show'), 'local')));
                }
            }
            
            $pRec->id = $id;
            
            $form->styles = array(
                '.formInfo' => 'max-width:440px;padding:8px;border:solid 1px #999;background-color:#FFC;font-family:Times New Roman;font-size:0.9em;',
                '' => 'margin-top:20px;margin-left:20px;'
            );
             
            $content = $form->renderHtml(NULL, $pRec);
            
            return FALSE;

        } elseif ($act == 'resetpassform') {
            
            $mvc->requireRightFor('resetpassout');
            
            $form = $mvc->getForm();
            
            $form->FNC('captcha', 'captcha_Type', 'caption=Разпознаване,input,mandatory');
            
            $form->styles = array(
                '.formInfo' => 'max-max-width:440px;padding:8px;border:solid 1px #999;background-color:#FFC;font-family:Times New Roman;font-size:0.9em;',
                '.formError' => 'max-width:440px;padding:8px;border:solid 1px #f99;background-color:#FF9;font-family:Times New Roman;font-size:0.9em;'
            );
            
            $rec = $form->input('email,captcha');
            
            if ($form->isSubmitted() && $rec) {
                $id = $mvc->fetchField(array("#email = '[#1#]'", $rec->email), 'id');
                
                if (!$id) {
                    sleep(2);
                    Debug::log('Sleep 2 sec. in' . __CLASS__);

                    $form->setError('email', 'Няма регистриран потребител с този имейл');
                } else {
                    
                    $rec = $mvc->fetch($id);
                    
                    core_LoginLog::add('pass_reset', $rec->id);
                    $mvc->logLogin('Ресетване на парола', $rec->id);
                    
                    // Тук трябва да изпратим имейл на потребителя за активиране
                    if ($mvc->sendActivationLetter($rec, USERREG_RESET_PASS_EMAIL, 'Промяна на парола', 'changePass') == FALSE) {
                        redirect(array('Index'), FALSE, "За съжеление възникна грешка и не успяхме да изпратим имейла за възстановяване на паролата. Опитайте по-късно или се свържете със системния администратор", 'error');
                    }
                    
                    // Редиректваме към страницата, която благодари за регистрацията
                    $msg = new ET(USERREG_THANK_FOR_RESET_PASS_MSG);
                    $msg->placeObject($rec);
                    
                    $conf = core_Packs::getConfig('core');
                    $msg->replace($conf->EF_APP_TITLE, 'EF_APP_TITLE');
                    
                    // Редиректване с показване на съобщение
                    redirect(array('Index'), TRUE, '|' . $msg->getContent());
                }
            }
            
            $form->toolbar->addSbBtn('Изпрати заявка');
            
            $form->title = "Възстановяване на забравена парола за|*" . " \"" . $conf->EF_APP_TITLE . "\"";
            
            if (!$form->gotErrors())
            $form->info = tr("Попълнете полетата и натиснете бутона за изпращане.|*<br>|" .
                "Имейл адресът трябва да бъде този, с който сте се регистрирали.|* <br>" .
                "На този имейл ще получите линк за избор на нова парола за достъп.");
            
            $form->addAttr("email", array('style' => 'width:300px'));
            
            $content = $form->renderHtml("email,captcha", $rec);
            
            return FALSE;
        } elseif ($act == 'unblock') {
            
            $rec->state = isset($rec->exState) ? $rec->exState : 'active';
                
            $mvc->save($rec);
            
            $mvc->logLogin('Отблокиран потребител', $rec->id);
            core_LoginLog::add('unblock', $rec->id);
            
            core_Cache::remove(USERREG_CACHE_TYPE, $id);

            redirect(array('crm_Profiles', 'changePassword', 'ret_url' => toUrl(array('Portal', 'Show'), 'local')), TRUE, '|Успешно отблокирахте потребителя. Моля, логнете се и сменете паролата си.');
        } elseif($act == 'unblocklocal' && type_Ip::isLocal()) {
            Request::setProtected('userId');
            $userId = Request::get('userId', 'int');
            if($rec = $mvc->fetch($userId)) {
                $rec->state = isset($rec->exState) ? $rec->exState : 'active';
                $mvc->save($rec);
                
                $mvc->logLogin('Отблокиран локален потребител', $rec->id);
                core_LoginLog::add('unblock', $rec->id);

                redirect(array('crm_Profiles', 'changePassword', 'ret_url' => toUrl(array('Portal', 'Show'), 'local')), TRUE, '|Успешно отблокирахте потребителя. Моля, логнете се и сменете паролата си.');
            }
        }
    }
    
    
    /**
     * Тази функция връща няколко предложения за свободни никове
     */
    function nickGenerator($mvc, $email, $names = '')
    {
        $email = explode('@', $email);
        $res[] = $email[0];
        $names = explode(' ', str::utf2ascii(mb_strtolower(trim($names))));
        
        $res[] = $names[0];
        
        if ($names[2]) {
            $last = $names[2];
        } else {
            $last = $names[1];
        }
        
        if ($last) {
            $res[] = $names[0]{0} . "." . $last;
            $res[] = $names[0] . "." . $last{0};
            $res[] = $names[0] . "." . $last;
        }
        
        if ($names[2]) {
            $res[] = $names[0] . "." . $names[1]{0} . "." . $last;
        }
        
        $n = '';
        
        while (empty($nicks)) {
            foreach ($res as $nick) {
                $nick = preg_replace('/[^a-zа-я0-9\.]+/', '_', $nick) . $n;
                
                if (!$mvc->fetch("#nick = '{$nick}'")) {
                    $nicks[] = $nick;
                }
            }
            
            if ($n == '') {
                $n = 2;
            } else {
                $n++;
            }
        }
        
        return $nicks;
    }
    
    
    /**
     * Изпращане на писмо за активиране на сметкатa
     * 
     * @param core_Users $mvc
     * @param NULL|boolean $res
     * @param stdObject $rec
     * @param string $tpl
     * @param string $subject
     * @param string $act
     */
    public static function on_AfterSendActivationLetter($mvc, &$res, $rec, $tpl = USERS_UNBLOCK_EMAIL, $subject = 'Отблокиране на потребител', $act = 'unblock')
    {
        $subject = tr($subject);
        
        cls::load('core_Users');
        
        $h = core_Cache::set(USERREG_CACHE_TYPE, str::getRand(), $rec->id, USERS_DRAFT_MAX_DAYS * 60 * 24);
        
        // Търсим корпоративна сметка, ако има такава
        $corpAcc = email_Accounts::getCorporateAcc();
        
        if ($corpAcc) {
            $PML = email_Accounts::getPML($corpAcc->email);
        } else {
            $PML = cls::get('phpmailer_Instance', array('emailTo' =>$rec->email));
        }
        
        $rec1 = clone ($rec);
        
        setIfNot($rec1->url, toUrl(array('core_Users', $act, $h), 'absolute'));
        
        setIfNot($rec1->senderName, phpmailer_Setup::get('PML_FROM_NAME', TRUE));
        
        $expireDate = dt::addDays(USERS_DRAFT_MAX_DAYS);
        $dt = cls::get('type_Datetime');
        $expireDate = $dt->toVerbal($expireDate);
        
        setIfNot($rec1->regLifetime, $expireDate);
        
        setIfNot($rec1->EF_APP_TITLE, EF_APP_TITLE);
        
        $tpl = new ET($tpl);
        
        $tpl->translate();
        
        $tpl->placeObject($rec1);
        
        $PML->Body = $tpl->getContent();
        
        $PML->Subject = $subject;
        
        $PML->AddAddress($rec->email);
        
        $res = $PML->Send();
    }
}