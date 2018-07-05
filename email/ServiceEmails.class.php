<?php 


/**
 * Клас 'email_Spam' - регистър на квалифицираните като твърд спам писма
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Milen Georgiev <milen2experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
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
        $this->FLD('data', 'blob(compress)', 'caption=Данни');
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
        if ($text) {
            $text = $textType->toVerbal(str::truncate($text, 20000));

            $msg .= "<div style='font-size:0.9em;font-family:monotype;'><div style='background-color:#cfc;padding:5px;font-size:1em;margin-bottom:5px;'>Получено в <b>{$accountId}</b> на <b>{$createdOn}</b> с UID=<b>{$uid}</b></div>" .
                "{$headers}<hr>{$text}</div>";
        }

        $row->msg = $msg;
    }
    
    
    /**
     * Сортиране от най-новите, към най-старите
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('#createdOn', 'DESC');
    }
}
