<?php


$conf = core_Packs::getConfig('core');
/**
 * Каква е минималната дължина за паролата?
 */
defIfNot('USERREG_MIN_PASS', 5);


/**
 * Типа на записите в кеша
 */
defIfNot('USERREG_CACHE_TYPE', 'UserReg');


/**
 * Съобщение, което получава потребителя след регистрация
 */
defIfNot('USERREG_THANK_FOR_REG_MSG',
    "Благодарим ви за регистрациятa|*!" .
    "<br><br>|На посочения от вас адрес беше изпратено писмо със заглавие \"Access activation\"|*." .
    "<br>|В него се съдържа линк, чрез който трябва да зададете вашата парола за|* " .
    $conf->EF_APP_TITLE . " ." . "<br><br>|Поздрави от екипа!");


/**
 * Съобщение, което получава потребителя след заявка за смяна на паролата
 */
defIfNot('USERREG_THANK_FOR_RESET_PASS_MSG',
    "Заявката за смяната на паролата е приета|*!" .
    "<br><br>|На посочения от вас адрес беше изпратено писмо със заглавие \"Reset Your password\"|*." .
    "<br>|В него се съдържа линк, чрез който трябва да зададете вашата нова парола за|* " .
    $conf->EF_APP_TITLE . " ." . "<br><br>|Поздрави от екипа!");


/**
 * Писмо до потребителя за активация
 */
defIfNot('USERREG_ACTIVATION_EMAIL',
    "|\nDear |*[#names#]|," .
    "\n" .
    "\nThank you for your registration." .
    "\n" .
    "\nTo setup your password, please use following URL:" .
    "\n" .
    "\n|*[#url#]|" .
    "\n" .
    "\nThe above URL will expired after [#regLifetime#] days." .
    "\n" .
    "\nRegards," .
    "\n\*[#senderName#]|");


/**
 * Писмо до потребителя за смяна на паролата
 */
defIfNot('USERREG_RESET_PASS_EMAIL',
    "\nDear [#names#]," .
    "\n" .
    "\nWe have received request about reseting your password." .
    "\n" .
    "\nTo setup new password, please use following URL:" .
    "\n" .
    "\n[#url#]" .
    "\n" .
    "\nThe above URL will expired after [#regLifetime#] days." .
    "\n" .
    "\nRegards," .
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
            
            // TODO: стила да отиде в CSS
            $style = "style='font-weight:bold;border-bottom:solid 1px #ccc;padding:2px;'";
            
            $tpl->append("<p>&nbsp;<A HREF='" .
                toUrl(array($mvc, 'resetPassForm')) .
                "' {$style}>»&nbsp;" . tr('Забравена парола') . "?</A>", 'FORM');
            
            $tpl->append("<p>&nbsp;<A HREF='" .
                toUrl(array($mvc, 'registerNewUser')) .
                "'  {$style}>»&nbsp;" . tr('Нова регистрация') . "</A>", 'FORM');
        }
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     */
    function on_BeforeAction($mvc, &$content, &$act)
    {
    	$conf = core_Packs::getConfig('core');
    	
        if ($act == 'registernewuser') {
            $form = $mvc->getForm();
            
            $form->setField('email', "valid=drdata_Emails->validate");
            
            if (EF_USSERS_EMAIL_AS_NICK) {
                $rec = $form->input("email,names");
            } else {
                $rec = $form->input("nick,email,names");
            }
            
            if ($rec) {
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
                    
                    // Тук трябва да изпратим имейл на потребителя за активиране
                    $this->sendActivationLetter($rec);
                    
                    // Редиректваме към страницата, която благодари за регистрацията
                    $msg = new ET(USERREG_THANK_FOR_REG_MSG);
                    $msg->placeObject($rec);
                    
                    core_Message::redirect($msg->getContent(), 'page_Info', NULL, array('Index'));
                }
            }
            
            // Показваме формата. Първо леко променяме стила. TODO в CSS
            $form->styles = array(
                '.formInfo' => 'width:440px;padding:8px;border:solid 1px #999;background-color:#FFC;font-family:Times New Roman;font-size:0.9em;',
                '.formError' => 'width:440px;padding:8px;border:solid 1px #f99;background-color:#FF9;font-family:Times New Roman;font-size:0.9em;'
            );
            
            $form->toolbar->addSbBtn('Регистрирай');
            
            $form->title = "Регистриране на нов потребител в|* \"" . $conf->EF_APP_TITLE . "\"";
            
            if (!$form->gotErrors()) {
                $form->info = tr("След като попълните полетата по-долу натиснете бутона \"Регистрирай\".|*<br>|" .
                    "На посочения от Вас имейл ще получите линк за избор на паролата за достъп.");
            }
            
            $form->addAttr("email,names,nick,password", array('style' => 'width:300px'));
            
            if (EF_USSERS_EMAIL_AS_NICK) {
                $content = $form->renderHtml("email,names", $rec);
            } else {
                $content = $form->renderHtml("nick,email,names", $rec);
            }
            
            return FALSE;
        } elseif ($act == 'activate' || $act == 'changePass') {
            
            $id = Request::get('id', 'identifier');
            
            if (!$id)
            error('Грешка 0', $id);
            
            $userId = (int) core_Cache::get(USERREG_CACHE_TYPE, $id);
            
            if (!$rec = $mvc->fetch($userId))
            error("Този линк е невалиден. Вероятно е използван или е изтекъл.");
            
            // Проверка дали състоянието съответства на действието
            if ($rec->state != 'draft' && $act == 'activate')
            error('This account was activated yet!');
            
            if ($rec->state == 'draft' && $act == 'changePass')
            error('This account is not activated yet!');
            
            $form = cls::get('core_Form');
            $form->FLD('nick', 'varchar(32)', 'caption=Ник');
            $form->setOptions('nick', array($rec->nick => $rec->nick));
            
            if ($act == 'activate') {
                $form->FLD('pass', 'password(32)', 'caption=Вашата парола');
                $form->title = "Активиране на потребител";
            } else {
                $form->FLD('pass', 'password(32)', 'caption=Новата парола');
                $form->title = "Задаване на нова парола";
            }
            
            $form->info = tr("За да си активирате достъпа до системата, моля въведете избраната" .
                "от вас парола в полетата по-долу. " . "Паролата трябва да е поне|* " .
                USERREG_MIN_PASS . " |символа и да съдържа латински букви и цифри.");
            
            $form->FLD('pass2', 'password(32)', 'caption=Пак паролата');
            
            $form->addAttr('nick,pass,pass2', array(
                    'style' => 'width:240px;'
                ));
            
            $form->FNC('passEnc', 'varchar', 'input=hidden');
            $form->FNC('pass2Enc', 'varchar', 'input=hidden');
            
            $form->FNC('passLen', 'int', 'input=hidden');
            
            $form->FNC('id', 'identifier', 'input=hidden');
            
            $form->toolbar->addSbBtn('Изпрати');
            
            $form->styles = array(
                '.formInfo' => 'width:440px;padding:8px;border:solid 1px #999;background-color:#FFC;font-family:Times New Roman;font-size:0.9em;'
            );
            
            $pRec = $form->input('pass,pass2,passEnc,pass2Enc,passLen,id');
            
            if ($pRec) {
                if ($pRec->pass) {
                    // В случай, че няма JavaScript, паролите идват чисти
                    // В такъв случай ги криптираме
                    // TODO: да се направи javascript криптиране
                    $pRec->passEnc = Users::encodePwd($pRec->pass);
                    $pRec->pass2Enc = Users::encodePwd($pRec->pass2);
                    $pRec->passLen = strlen($pRec->pass);
                }
                
                if (!$pRec->passEnc || $pRec->passLen == 0) {
                    $form->setError('pass', 'Не е попълнена паролата');
                }
                
                if ($pRec->passEnc != $pRec->pass2Enc) {
                    $form->setError('pass', 'Двете пароли се различават');
                }
                
                if ($pRec->passLen < USERREG_MIN_PASS) {
                    $form->setError('pass', 'Паролата трябва да бъде минимум|* ' . USERREG_MIN_PASS . ' |символа');
                }
                
                if (!$form->gotErrors()) {
                    $rec->ps5Enc = $pRec->passEnc;
                    $rec->state = 'active';
                    $mvc->save($rec, 'state,ps5Enc');
                    core_Cache::remove(USERREG_CACHE_TYPE, $id);
                    
                    return redirect(array('Index'));
                }
            }
            
            $pRec->id = $id;
            
            $tpl = $form->renderHtml(NULL, $pRec);
            
            $tpl->push('js/login.js', 'JS');
            $tpl->replace('this.passEnc.value = encodePwd(this.pass.value, \'' .
                EF_USERS_PASS_SALT . '\');' .
                'this.pass2Enc.value = encodePwd(this.pass2.value, \'' .
                EF_USERS_PASS_SALT . '\');' . 'this.passLen.value = this.pass.value.length;' .
                'this.pass.value = this.pass2.value = \'\'', 'ON_SUBMIT');
            
            $content = $tpl;
            
            return FALSE;
        } elseif ($act == 'resetPassForm') {
            
            $form = $mvc->getForm();
            
            $form->FNC('captcha', 'captcha_Type', 'caption=Разпознаване,input,mandatory');
            
            $form->styles = array(
                '.formInfo' => 'width:440px;padding:8px;border:solid 1px #999;background-color:#FFC;font-family:Times New Roman;font-size:0.9em;',
                '.formError' => 'width:440px;padding:8px;border:solid 1px #f99;background-color:#FF9;font-family:Times New Roman;font-size:0.9em;'
            );
            
            $rec = $form->input('email,captcha');
            
            if ($rec) {
                $id = $mvc->fetchField(array("#email = '[#1#]'", $rec->email), 'id');
                
                if (!$id) {
                    sleep(5);
                    $form->setError('email', 'Няма регистриран потребител с този имейл');
                } else {
                    
                    $rec = $mvc->fetch($id);
                    
                    // Тук трябва да изпратим имейл на потребителя за активиране
                    $this->sendActivationLetter($rec, USERREG_RESET_PASS_EMAIL, 'Reset your password', 'changePass');
                    
                    // Редиректваме към страницата, която благодари за регистрацията
                    $msg = new ET(USERREG_THANK_FOR_RESET_PASS_MSG);
                    $msg->placeObject($rec);
                    
                    core_Message::redirect($msg->getContent(), 'page_Info', NULL, array('Index'));
                    
                    // Редиректване с показване на съобщение
                    return redirect(array('Index'));
                }
            }
            
            $form->toolbar->addSbBtn('Изпрати заявка');
            
            $form->title = "Възстановяване на забравена парола за|*" . " \"" . $conf->EF_APP_TITLE . "\"";
            
            if (!$form->gotErrors())
            $form->info = tr("Попълнете полетата и натиснете бутона за изпращане.|*<br>|" .
                "Имейл адресът трябва да бъде този, с който сте се регистрирали.|* <br>" .
                "На този имейл ще получите линк за избор на нова паролата за достъп.");
            
            $form->addAttr("email", array('style' => 'width:300px'));
            
            $content = $form->renderHtml("email,captcha", $rec);
            
            return FALSE;
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
     */
    function sendActivationLetter_($rec, $tpl = USERREG_ACTIVATION_EMAIL, $subject = 'Account activation', $act = 'activate')
    {
        $h = core_Cache::set('UserReg', str::getRand(), $rec->id, USERS_DRAFT_MAX_DAYS * 60 * 24);
        
        $PML = cls::get('phpmailer_Instance');
        
        $rec1 = clone ($rec);
        
        setIfNot($rec1->url, toUrl(array('core_Users', $act, $h), 'absolute'));
        
        setIfNot($rec1->senderName, PML_FROM_NAME);
        
        setIfNot($rec1->regLifetime, USERS_DRAFT_MAX_DAYS);
        
        $tpl = new ET($tpl);
        
        $tpl->translate();
        
        $tpl->placeObject($rec1);
        
        $PML->Body = $tpl->getContent();
        
        $PML->Subject = $subject;
        
        $PML->AddAddress($rec->email);
        
        $PML->Send();
    }
}