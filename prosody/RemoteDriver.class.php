<?php

/**
 * Драйвер за отдалечен чат сървър
 *
 *
 * @category  bgerp
 * @package   prosody
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class prosody_RemoteDriver extends core_Mvc
{

    /**
     * Поддържа интерфейса за драйвер
     */
    public $interfaces = 'remote_ServiceDriverIntf,remote_SendMessageIntf';


    /**
     * Заглавие на драйвера
     */
    public $title = 'Prosody XMPP чат';


    /**
     * Плъгини и класове за зареждане
     */
    public $loadList = 'crm_Wrapper';
    

    /**
     * Таб във wrapper-a
     */
    public $currentTab = 'Профили';


 


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        if (isset($fieldset->fields['url'])) {
            $fieldset->setField('url', 'hint=Адрес на Prosody XMPP чат');
        }
        
        $fieldset->FLD('xmppUser', 'nick', 'caption=Чат ник,hint=XMPP потребител,mandatory');
        $fieldset->FLD('xmppPass', 'password', 'caption=Парола,hint=XMPP парола');
    }


    /**
     * Създава потребителя, ако трябва
     */
    public function on_AfterInputEditForm($driver, $embedder, $form)
    {
        $setNewPass = false;

        $rec = $form->rec;
        if ($rec->id) {
            $exRec = $embedder->fetch($rec->id);
        }
        $nick = $rec->xmppUser;
        if ($form->isSubmitted()) {
            if (!$rec->id) {
                // Възможни грешки при създаване на нов потребител:

                // 1. Имаме такъв потребител в локалната система, и той не е за който се задава
                $exUser = core_Users::fetch(array("LOWER(#nick) = LOWER('[#1#]')", $rec->xmppUser));
                if ($exUser && $exUser->id != $rec->userId) {
                    $form->setError('xmppUser', 'Това име е запазено за друг потребител от тази система.');
                }
                if ($form->isSubmitted()) {
                    
                    // 2. Имаме друг Prosody XMPP потребител със същото име
                    $aQuery = remote_Authorizations::getFiltredQuery('prosody_RemoteDriver');
                    while ($aRec = $aQuery->fetch()) {
                        if ($aRec->xmppUser == $rec->xmppUser) {
                            $form->setError('xmppUser', 'Този чат ник вече е използван.');
                        }
                    }
                    if ($form->isSubmitted()) {
                        $res = prosody_RestApi::addUser($nick, $rec->xmppPass);
                        if ($res['status'] == 409) {
                            $form->setWarning('xmppUser', 'Този ник вече се използва в чата. Няма да бъде създаден нов, но ще му бъде променена паролата.');
                            $setNewPass = true;
                        }
                    }
                }
            } elseif ($exRec) {
                if (strlen($rec->xmppPass) && ($rec->xmppPass != $exRec->xmppPass)) {
                    $setNewPass = true;
                }
            }
        }
        
        if ($rec->xmppPass) {
            if ($setNewPass) {
                $res = prosody_RestApi::changePassword($nick, $rec->xmppPass);

                if (substr($res['status'], 0, 1) != 2) {
                    core_Statuses::newStatus('|Неуспешна смяна на паролата|*!', 'error');
                } else {
                    core_Statuses::newStatus('|Успешна смяна на паролата|*!');
                }
            }
        } elseif ($exRec && $exRec->xmppPass) {
            $rec->xmppPass = $exRec->xmppPass;
        }


        // Обикаляме по всички съществуващи потребители и им задаваме Roaster
        if ($form->isSubmitted()) {
            $aQuery = remote_Authorizations::getFiltredQuery('prosody_RemoteDriver');
            while ($aRec = $aQuery->fetch()) {
                if ($rec->userId == $aRec->userId) {
                    continue;
                }
                $res1 = prosody_RestApi::addRoster($nick, $aRec->xmppUser);
                $res2 = prosody_RestApi::addRoster($aRec->xmppUser, $nick);
            }
        }
    }


    /**
     * След подготовка на формата за добавяне/редакция
     */
    protected static function on_AfterPrepareEditForm($driver, $mvc, $data)
    {
        $form = $data->form;
        $rec = $form->rec;

        $form->setDefault('url', prosody_Setup::get('DOMAIN'));
        $form->setReadonly('url');


        $form->setDefault('xmppUser', core_Users::fetchField($rec->userId, 'nick'));
        if (!haveRole('admin')) {
            $form->setReadonly('xmppUser');
        }

        if (!$rec->id) {
            $form->setDefault('blockNecessitous', 'night');
            $form->setDefault('blockNormal', 'night,nonworking');
            $form->setField('xmppPass', 'mandatory');
        } else {
            $rec->xmppPass = '';
        }
    }


    
    /**
     * След конвертиране към вербални стойности на записа
     */
    public function on_AfterRecToVerbal($driver, $mvc, $row, $rec)
    {
        $icon = sbf('prosody/img/16/prosody.png', '');

        $row->url = "<span class = 'linkWithIcon' style = 'background-image:url({$icon})'>" . $driver->title . '</span>';
    }


    

    /**
     * За да не могат да се редактират оторизациите с получен ключ
     */
    public static function on_AfterGetRequiredRoles($driver, $mvc, &$res, $action, $rec = null, $userId = null)
    {
    }


    
    /**
     * Може ли вградения обект да се избере
     */
    public function canSelectDriver($userId = null)
    {
        // Ако вече има зададена услуга за дадения потребител
        // и потребителя не е администратор не може да се избира този драйвер
        if ($userId === null) {
            $userId = core_Users::getCurrent();
        }

        $aQuery = remote_Authorizations::getFiltredQuery('prosody_RemoteDriver', $userId);

        if ($aQuery->fetch()) {
            
            return haveRole('admin');
        }

        return true;
    }


    /*
    *************************************************************************************
    *
    *  Интерфейс
    *
    **************************************************************************************
    */

    /**
     * Връща информация за логин на потребителя
     */
    public function getXmppCredentials($rec)
    {
        $rec = remote_Authorizations::fetchRec($rec);

        $res = new stdClass();
        $res->xmppUser = $rec->xmppUser . '@' . prosody_Setup::get('DOMAIN');
        $res->xmppPass = $rec->xmppPass;

        return $res;
    }


    /**
     * Изпраща съобщение до потребителя
     */
    public function sendMessage($rec, $msg)
    {
        $rec = remote_Authorizations::fetchRec($rec);
 
        $res = prosody_RestApi::sendMessage($rec->xmppUser, $msg);
 
        return (substr($res['status'], 0, 1) == 2);
    }
}
