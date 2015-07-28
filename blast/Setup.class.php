<?php


/**
 * Текст за отписване от информационните съобщение
 */
defIfNot('BGERP_BLAST_UNSUBSCRIBE', '|Желаете ли да блокирате изпращането на информационни имейли към адрес|* [#email#]?');


/**
 * Текст, който се показва, ако не може да се намери имейл адреса в системата
 */
defIfNot('BGERP_BLAST_NO_MAIL', '|Не може да се намери имейл адресът Ви|*.');


/**
 * Teкст, който се показва когато премахнем имейл-а от блокираните
 */
defIfNot('BGERP_BLAST_SUCCESS_ADD', '|Имейлът|* [#email#] |е добавен в списъка за информационни съобщения|*. |Искате ли да го премахнете|*?');


/**
 * Текст, който се показва когато добавим имейл-а в списъка на блокираните имейли
 */
defIfNot('BGERP_BLAST_SUCCESS_REMOVED', '|Имейлът|* [#email#] |е премахнат от списъка за информационни съобщения|*. |Искате ли да го добавите|*?');


/**
 * Текст за отписване във футъра
 */
defIfNot('BLAST_UNSUBSCRIBE_TEXT_FOOTER', '|Ако не желаете да получавате повече информация от нас, моля натиснете|* [unsubscribe]|тук|*[/unsubscribe]');


/**
 * Период на изпращането на информационни съобщения по крон
 */
defIfNot('BLAST_EMAILS_CRON_PERIOD', '60');


/**
 * Ограничение на времето при изпращане по крон
 */
defIfNot('BLAST_EMAILS_CRON_TIME_LIMIT', '50');


/**
 * class blast_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с 'blast'
 *
 *
 * @category  bgerp
 * @package   blast
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class blast_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'blast_Lists';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Разпращане на циркулярни имейл-и, sms-и, писма, ...";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        
        // Текст за потвърждаване на отписването
        'BGERP_BLAST_UNSUBSCRIBE' => array ('text(rows=5)', 'caption=Потвърждаване на отписването от списъка за изпращане->Съобщение'),
        
        // Текст, който се показва, ако не може да се намери имейл адреса в системата
        'BGERP_BLAST_NO_MAIL'   => array ('text(rows=5)', 'caption=Липсващ имейл за отписване от списъка за изпращане->Съобщение'),
        
        // Teкст, който се показва когато премахнем имейл-а от блокираните
        'BGERP_BLAST_SUCCESS_ADD'   => array ('text(rows=5)', 'caption=Успешно премахване от списъка с блокираните->Съобщение'),
        
        // Текст, който се показва когато добавим имейл-а в списъка на блокираните имейли
        'BGERP_BLAST_SUCCESS_REMOVED'   => array ('text(rows=5)', 'caption=Успешно добавяне в списъка с блокираните->Съобщение'),
        
        'BLAST_UNSUBSCRIBE_TEXT_FOOTER'   => array ('text(rows=3)', 'caption=Текст за отписване във футъра->Текст'),
        
        'BLAST_EMAILS_CRON_PERIOD'   => array ('time(suggestions=1 мин.|2 мин.|5 мин.|10 мин.)', 'caption=Период на изпращане на информационни съобщения по крон->Време'),
        'BLAST_EMAILS_CRON_TIME_LIMIT'   => array ('time(suggestions=30 сек.|50 сек.|1 мин.|2 мин.|3 мин.)', 'caption=Ограничение на времето при изпращане по крон->Време')
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
        'blast_Lists',
        'blast_ListDetails',
        'blast_Emails',
        'blast_BlockedEmails',
        'blast_Letters',
        'blast_LetterDetails',
        'blast_EmailSend',
        'migrate::fixListId',
        'migrate::fixEmails',
        'migrate::addEmailSendHash',
        'migrate::updateListLg'
    );
    
    
    /**
     * Роли за достъп до модула
     */
    var $roles = 'blast';
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('csvContacts', 'CSV контактни данни', 'csv,txt,text,', '10MB', 'user', 'ceo');
        
        return $html;
    }
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
        array(1.36, 'Указател', 'Разпращане', 'blast_Lists', 'default', "ceo, blast"),
    );
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    /**
     * Миграция за blast_EmailSend таблицата
     */
    static function fixListId()
    {
        $cls = cls::get('blast_EmailSend');
        
        $cls->db->connect();
        
        $listDetailId = str::phpToMysqlName('listDetailId');
        
        if (!$cls->db->isFieldExists($cls->dbTableName, $listDetailId)) return ;
        
        $cls->FLD('listDetailId', 'key(mvc=blast_ListDetails, select=key)', 'caption=Имейл');
        
        // Всички записи, които имат listDetailId
        $query = $cls->getQuery();
        $query->where("#listDetailId IS NOT NULL");
        
        while ($rec = $query->fetch()) {
            
            // Ако няма listDetailId
            if (!($rec->listDetailId > 0)) continue;
            
            // Ако е обработен записа
            if (blast_EmailSend::fetch(array("#emailId = '[#1#]' AND #dataId = '[#2#]'", $rec->emailId, $rec->listDetailId))) continue;
            
            // Данните за детайла
            $detRec = blast_ListDetails::fetch($rec->listDetailId);
            
            // Добавяме данните, за новия запис
            $nRec = new stdClass();
            
            if ($detRec->data) {
                $nRec->data = unserialize($detRec->data);
            } else {
                $nRec->data = array();
            }
            $nRec->id = $rec->id;
            $nRec->dataId = $detRec->id;
            $nRec->emailId = $rec->emailId;
            $nRec->sentOn = $rec->sentOn;
            
            if ($rec->sentOn) {
                $nRec->state = 'sended';
            } else {
                $nRec->state = 'pending';
            }
            
            $emailStr = '';
            
            foreach ((array)$nRec->data as $name => $val) {
                if ($name != 'email') continue;
                $emailsArr = type_Emails::toArray($val);
                $emailStr = $emailsArr[0];
            }
            $nRec->email = $emailStr;
            
            // След успешен запис
            if (blast_EmailSend::save($nRec, NULL, 'UPDATE')) {
                
                // Обновяваме стойността за детайла в лога
                $masterRec = blast_Emails::fetch($nRec->emailId);
                $lQuery = doclog_Documents::getQuery();
                $lQuery->where("#containerId = '{$masterRec->containerId}'");
                
                while ($lRec = $lQuery->fetch()) {
                    if ($lRec->data->detId != $rec->listDetailId) continue;
                    $lRec->data->detId = $nRec->id;
                    doclog_Documents::save($lRec, 'dataBlob', 'UPDATE');
                }
            }
        }
    }
    
    
    /**
     * Миграция за blast_Emails таблицата
     */
    static function fixEmails()
    {
        $blsInst = cls::get('blast_Emails');
        
        $blsInst->db->connect();
        
        $listId = str::phpToMysqlName('listId');
        
        if (!$blsInst->db->isFieldExists($blsInst->dbTableName, $listId)) return ;
        
        $blsInst->FLD('listId', 'key(mvc=blast_Lists, select=title)', 'caption=Лист, mandatory');
        
        // Всички записи, които нямат клас и обект
        $query = $blsInst->getQuery();
        $query->where("#perSrcClassId IS NULL");
        $query->where("#perSrcObjectId IS NULL");
        
        $listClassId = blast_Lists::getClassId();
        
        while ($rec = $query->fetch()) {
            $nRec = new stdClass();
            $nRec->id = $rec->id;
            $nRec->perSrcClassId = $listClassId;
            $nRec->perSrcObjectId = $rec->listId;
            
            $blsInst->save($nRec, 'perSrcClassId, perSrcObjectId', 'UPDATE');
        }
    }
    
    
    /**
     * Добавя хеш на имейлите
     */
    static function addEmailSendHash()
    {
        $query = blast_EmailSend::getQuery();
        $query->where("#hash IS NULL");
        while ($rec = $query->fetch()) {
            if (is_null($rec->email)) continue;
            $emailH = $rec->email;
            $hash = NULL;
            
            do {
                $hash = blast_EmailSend::getHash($emailH);
                $emailH = $hash;
            } while (blast_EmailSend::fetch("#hash = '{$hash}' AND #emailId = '{$rec->emailId}'"));
            
            $rec->hash = $hash;
            
            blast_EmailSend::save($rec, 'hash', 'UPDATE');
        }
    }
    
    
    /**
     * Обновява езика на списъка с имейлите
     */
    static function updateListLg()
    {
        $lQuery = blast_Lists::getQuery();
        $lQuery->where("#lg IS NULL OR #lg = '' OR #lg = 'auto'");
        $lQuery->where("#keyField = 'email'");
        
        while ($lRec = $lQuery->fetch()) {
            $ldQuery = blast_ListDetails::getQuery();
            $ldQuery->where("#listId = {$lRec->id}");
            
            $cnt = $ldQuery->count();
            
            if (!$cnt) continue;
            
            while($r = $ldQuery->fetch()) {
                echo "<li>" . $r->key;
            }
            
            $ldQuery->where("#key LIKE '%.bg'");
            
            $bgCnt = $ldQuery->count();
            
            $cntRes = $bgCnt / $cnt;
            
            if ($cntRes > 0.1) {
                $lRec->lg = 'bg';
            } else {
                $lRec->lg = 'en';
            }
            
            blast_Lists::save($lRec, 'lg');
        }
    }
}
