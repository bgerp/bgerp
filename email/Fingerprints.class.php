<?php 


/**
 * Клас 'email_Fingerprints' - регистър на хешовете на хедърите на всички свалени имейли
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Milen Georgiev <milen2experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Fingerprints extends core_Manager
{
    /**
     * Плъгини за работа
     */
    var $loadList = 'email_Wrapper,  email_incoming_Wrapper';
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Отпечатъци на свалени писма";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, ceo, email';
    
    
    /**
     * Кой има право да променя?
     */
    var $canWrite = 'no_one';
    

    /**
     * Цветове на редовете, според статуса им
     */
    var $statusToColor = array( 
        'returned' => '#ff9999',
        'receipt'  => '#33ffff',
        'spam'     => '#cccccc',
        'incoming' => '#eeeeee',
        'misformatted' => '#ffcc00',
        );


    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('hash', 'varchar(32)', 'caption=Хеш');
        $this->FLD('accountId', 'key(mvc=email_Accounts,select=email)', 'caption=Сметка');
        $this->FLD('uid', 'int', 'caption=Имейл UID');
        $this->FLD('status', 'enum(returned,receipt,spam,incoming,misformatted)', 'caption=Статус,notNull');

        $this->setDbUnique('hash');
    }
    

    /**
     * Преди извличането на записите ги подрежда от по-нови, към по-стари
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $data->query->orderBy("#id", 'DESC');
    }


    /**
     * След конвертиране към вербален формат, задава цвета на реда, според състоянието
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->ROW_ATTR['style'] .= 'background-color:' . $mvc->statusToColor[$rec->status] . ';';
    }

    
    /**
     * Изчислява хеша на хедърите на писмото
     */
    private static function getHeaderHash($headers) 
    {
        $whiteSpace = array("\n", "\r", "\t", " ");
        $null       = array('', '', '', '');
        $headers    = str_replace($whiteSpace, $null, $headers);

        $hash = md5($headers);
        
        return $hash;
    }


    /**
     * Връща TRUE, ако писмо със същите хедъри е свалено преди,иначе FALSE
     */
    static function isDown($headers) 
    {
        $hash = self::getHeaderHash($headers);

        $res = self::fetchField("#hash = '{$hash}'", 'id') > 0;
self::log("<li> $hash $res");
        return $res;
    }

    
    /**
     * Задава статуса по отношение на 
     * Връща id на записа, ако задаването е успешно и NULL в противен случай
     */
    static function setStatus($headers, $status, $accId, $uid)
    {   
        $rec = new stdClass();

        $rec->hash = self::getHeaderHash($headers);

        if(self::fetchField("#hash = '{$rec->hash}'", 'id')) {

            return FALSE;
        }

        $rec->accountId = $accId;

        $rec->uid = $uid;

        $rec->status = $status;

        self::save($rec);

        return $rec->id;
    }


    /**
     * Начално установяване
     */
    function on_AfterSetupMVC($mvc, &$res)
    {
        if(!self::fetch('1=1') || Request::get('force')) {
            set_time_limit(10000);
            core_Debug::$isLogging = FALSE;
            $incQuery = email_Incomings::getQuery();
            while($incRec = $incQuery->fetch()) {
                if($incRec->emlFile) {
                    $fRec = fileman_Files::fetch($incRec->emlFile, NULL, FALSE);

                    $emlSource = @fileman_Files::getContent($fRec->fileHnd);
                    
                    $mime = cls::get('email_Mime');

                    $mime->parseAll($emlSource);

                    $headers = $mime->getHeadersStr();

                    self::setStatus($headers, 'incoming', $incRec->accId, $incRec->uid);
                }
            }
        }
    }
    
}