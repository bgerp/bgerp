<?php 

/**
 * Клас 'email_ServiceEmails' - регистър на квалифицираните като твърд спам писма
 *
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Yusein Yuseinov <yyuseinov@gmnail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class email_ServiceEmails extends core_Manager
{
    /**
     * Плъгини за работа
     */
    public $loadList = 'email_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin, ceo, email';
    
    
    /**
     * Кой има право да променя?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin, email';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'id,msg=Имейл';
    
    
    /**
     * Описание на модела
     */
    public function addFields()
    {
        $this->FLD('data', 'blob(16777216,compress)', 'caption=Данни');
        $this->FLD('accountId', 'key(mvc=email_Accounts,select=email)', 'caption=Сметка');
        $this->FLD('uid', 'int', 'caption=Imap UID');
        $this->FLD('createdOn', 'datetime(format=smartTime)', 'caption=Създаване');
    }


    /**
     * Показва писмото в по-добър вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = null)
    {
        $mime = cls::get('email_Mime');
        
        $accountId = $mvc->getVerbal($rec, 'accountId');
        $createdOn = $mvc->getVerbal($rec, 'createdOn');
        $uid = $mvc->getVerbal($rec, 'uid');
        
        // Парсира съдържанието на писмото
        $mime->parseAll($rec->data);
        
        $headers = $mime->getHeadersVerbal();
        
        $text = $mime->textPart;
        $textType = cls::get('type_Richtext');
        $msg = '';
        if ($text) {
            $text = $textType->toVerbal(str::truncate($text, 20000));

            if ($mvc->haveRightFor('list')) {

                if (core_Permanent::get($mvc->className . '_service_' . $rec->id)) {
                    $msg = 'Този имейл е бил възстановяван преди';
                } else {
                    $msg = false;
                }

                $restoreLink = ht::createBtn('Възстановяване', array($mvc, 'restore', $rec->id), $msg, null, array('ef_icon' => 'img/16/restore.png'));
            } else {
                $restoreLink = '';
            }

            $msg .= "<div style='font-size:0.9em;font-family:monotype;'><div style='background-color:#cfc;padding:5px;font-size:1em;margin-bottom:5px;'>Получено в <b>{$accountId}</b> на <b>{$createdOn}</b> с UID=<b>{$uid}</b><div>{$restoreLink}</div></div>" .
                "{$headers}<hr>{$text}</div>";
        }

        $row->msg = $msg;
    }


    /**
     * Екшън за възстановяване на имейли
     *
     * @return Redirect
     */
    function act_Restore()
    {
        $this->requireRightFor('list');

        $id = Request::get('id', 'int');

        $rec = $this->fetch(array("#id = '[#1#]'", $id));

        expect($rec && $rec->data);

        $mime = cls::get('email_Mime');

        $data = $mime->parseAll($rec->data);

        $Incomings = cls::get('email_Incomings');

        Mode::push('forceDownload', true);

        Users::forceSystemUser();

        $rId = $Incomings->process($mime, $rec->accountId, $rec->uid);

        Users::cancelSystemUser();

        Mode::pop('forceDownload');

        $eRec = $Incomings->fetch($rId);

        expect($eRec);

        $keepMinutes = email_Setup::get('SERVICEMAILS_KEEP_DAYS') / 60;
        $keepMinutes = floor($keepMinutes);
        $keepMinutes += 1000;

        core_Permanent::set($this->className . '_service_' . $rec->id, true, $keepMinutes);

        // Обучаваме SPAS, че това не е СПАМ
        if (core_Packs::isInstalled('spas')) {

            try {
                $sa = spas_Test::getSa();

                $res = $sa->learn($rec->data, spas_Client::LEARN_HAM);

                if ($res) {
                    $resStr = 'ОК';
                } else {
                    $resStr = 'Проблем';
                }

                email_Incomings::logNotice("Резултат от обучение след форсирано сваляне - " . $resStr, $rec->id);
            } catch (spas_client_Exception $e) {
                reportException($e);
                email_Incomings::logErr('Грешка при обучение на SPAS: ' . $e->getMessage());
            }
        }

        // Даваме права за сингъла
        $modeAllowedContainerIdName = $Incomings->getAllowedContainerName();
        $allowedCidArr = Mode::get($modeAllowedContainerIdName);
        if (!isset($allowedCidArr)) {
            $allowedCidArr = array();
        }
        $allowedCidArr[$eRec->containerId] = $eRec->containerId;
        Mode::setPermanent($modeAllowedContainerIdName, $allowedCidArr);

        return new Redirect(array($Incomings, 'single', $eRec->id));
    }


    /**
     * Сортиране от най-новите, към най-старите
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('#createdOn', 'DESC');
        $data->query->orderBy('#id', 'DESC');
    }
    
    
    /**
     * Изтрива стари записи в bgerp_Recently
     */
    public function cron_DeleteOldServiceMails()
    {
        $lastCreated = dt::addDays(-email_Setup::get('SERVICEMAILS_KEEP_DAYS') / (24 * 3600));
        
        $delClsArr = array('email_Receipts', 'email_Returned', 'email_Spam', 'email_Unparsable');
        
        $res = '';
        
        foreach ($delClsArr as $clsName) {
            $inst = cls::get($clsName);
            $delCnt = $inst->delete(array("#createdOn < '[#1#]'", $lastCreated));
            
            if ($delCnt) {
                $inst->logNotice("Бяха изтрити {$delCnt} записа");
                
                $res .= "<li>Бяха изтрити {$delCnt} записа от " . $inst->className;
            }
        }
        
        return $res;
    }


    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('id', 'DESC');
    }
}
