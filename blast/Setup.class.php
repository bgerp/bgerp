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
defIfNot('BLAST_UNSUBSCRIBE_TEXT_FOOTER', "|Ако не желаете да получавате повече информация от нас, използвайте следната връзка|*:\n[unsubscribe]|линк|*[/unsubscribe]");


/**
 * Период на изпращането на информационни съобщения по крон
 */
defIfNot('BLAST_EMAILS_CRON_PERIOD', '60');


/**
 * Ограничение на времето при изпращане по крон
 */
defIfNot('BLAST_EMAILS_CRON_TIME_LIMIT', '50');


/**
 * Повторна проверка за валидност на имейли след - 1 седмица
 */
defIfNot('BLAST_RECHECK_EMAILS_AFTER', 604800);


/**
 * Брой имейли за проверка при всяко извикване
 */
defIfNot('BLAST_RECHECK_EMAILS_LIMIT', 5);


/**
 * След колко време, ако няма комуникация с имейла да се спре да се проверява
 */
defIfNot('BLAST_STOP_CHECKING_EMAILS_PERIOD', 15778476);


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
        'BLAST_EMAILS_CRON_TIME_LIMIT'   => array ('time(suggestions=30 сек.|50 сек.|1 мин.|2 мин.|3 мин.)', 'caption=Ограничение на времето при изпращане по крон->Време'),
        
        'BLAST_RECHECK_EMAILS_AFTER'   => array ('time(suggestions=15 дни|1 месец|2 месеца)', 'caption=Повторна проверка за валидност на имейли след->Време'),
        'BLAST_RECHECK_EMAILS_LIMIT'   => array ('int', 'suggestions=3|5|10, caption=Лимит за проверка на имейли за всяко извикване->Брой'),
        'BLAST_STOP_CHECKING_EMAILS_PERIOD'   => array ('time(suggestions=3 месеца|6 месеца|1 година)', 'caption=Колко време след последната комуникация да се спре проверката на имейла->Време'),
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
        'migrate::fixEmails',
        'migrate::addEmailSendHash',
        'migrate::updateListLg2',
        'migrate::stateOfBlockedEmails',
        'migrate::calcProgress',
        'migrate::removeEmptyLines'
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
        $html .= $Bucket->createBucket('csvContacts', 'CSV контактни данни', 'csv,txt,text,', '10MB', 'user', 'powerUser');
        
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
        $res = bgerp_Menu::remove($this);
        
        return $res;
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
     * Обновява езика на списъците
     */
    static function updateListLg2()
    {
        $lQuery = blast_Lists::getQuery();
        $lQuery->where("#lg IS NULL OR #lg = '' OR #lg = 'auto'");
        
        while ($lRec = $lQuery->fetch()) {
            $ldQuery = blast_ListDetails::getQuery();
            $ldQuery->where("#listId = {$lRec->id}");
            
            $cnt = $ldQuery->count();
            
            if ($cnt && $lRec->keyField == 'email') {
                $ldQuery->where("#key LIKE '%.bg'");
                
                $bgCnt = $ldQuery->count();
                
                $cntRes = $bgCnt / $cnt;
                
                if ($cntRes > 0.1) {
                    $lRec->lg = 'bg';
                } else {
                    $lRec->lg = 'en';
                }
            } else {
                $lRec->lg = 'en';
            }
            
            blast_Lists::save($lRec, 'lg');
        }
    }
    
    
    /**
     * Миграция за обновяване на времето на стартиране
     */
    public static function updateEmailsSendOn()
    {
        $cls = cls::get('blast_Emails');
        
        $cls->db->connect();
        
        $startOnField = str::phpToMysqlName('startOn');
        
        if (!$cls->db->isFieldExists($cls->dbTableName, $startOnField)) return ;
        
        $cls->FLD('startOn', 'datetime', 'caption=Дата');
        
        $query = $cls->getQuery();
        $query->where("#startOn IS NOT NULL");
        $query->where("#sendingDay IS NULL");
        $query->where("#sendingTo IS NULL");
        $query->where("#sendingFrom IS NULL");
        
        while ($rec = $query->fetch()) {
            $timeStamp = dt::mysql2timestamp($rec->startOn);
            $rec->sendingDay = date('w', $timeStamp);
            $rec->sendingFrom = date('G', $timeStamp) * 3600;
            $cls->save($rec, 'sendingDay, sendingFrom');
        }
    }
    
    
    /**
     * Миграция, за промяна на състоянието на всички имейли в блокирани
     */
    public static function stateOfBlockedEmails()
    {
        $query = blast_BlockedEmails::getQuery();
        
        while ($rec = $query->fetch()) {
            $rec->state = 'blocked';
            blast_BlockedEmails::save($rec, 'state');
        }
    }
    
    
    /**
     * Миграция, за промяна на прогреса
     */
    public static function calcProgress()
    {
        $query = blast_Emails::getQuery();
        
        while ($rec = $query->fetch()) {
            $rec->progress = blast_EmailSend::getSendingProgress($rec->id);
                
            blast_Emails::save($rec, 'progress');
        }
    }
    
    
    /**
     * Премахва празните редове имейли
     */
    public static function removeEmptyLines()
    {
        blast_BlockedEmails::delete("#email IS NULL OR #email = ''");
    }
}
