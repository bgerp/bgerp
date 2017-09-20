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
    var $loadList = 'email_Wrapper, plg_RowTools, plg_Sorting';
    
    
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
	 * Кой може да го разглежда?
	 */
	var $canList = 'admin, email';
	
	
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,hash,accountId,uid,status';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'accountId,uid,status';
    
	
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
        $this->FLD('accountId', 'key(mvc=email_Accounts,select=email,allowEmpty)', 'caption=Сметка, autoFilter');
        $this->FLD('uid', 'int', 'caption=Имейл UID');
        $this->FLD('status', 'enum(returned,receipt,spam,incoming,misformatted)', 'caption=Статус,notNull');
        $this->FLD('downloadedOn', 'datetime', 'caption=Свалено на,notNull');
        $this->FLD('deleted', 'enum(no=Не, yes=Да)', 'caption=Изтрито,notNull');

        $this->setDbUnique('hash');
        $this->setDbIndex('accountId,uid');
    }
    
    
 	/**
     * Изпълнява се след подготовката на формата за филтриране
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $form = $data->listFilter;
        
        // В хоризонтален вид
        $form->view = 'horizontal';
        
        // Добавяме бутон
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $form->showFields = 'accountId';
        
        $form->input('accountId', 'silent');
        
        if($form->rec->accountId){
        	$data->query->where(array("#accountId = '[#1#]'", $form->rec->accountId));
        }
        
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
     * Изчислява хеша на част от хедърите на писмото
     * 
     * @param string $headersStr
     * 
     * @return string
     */
    private static function getHeaderPartHash($headersStr) 
    {
        static $headerHashArr = array();
        
        $headerHash = self::getHeaderHash($headersStr);
        
        if (!isset($headerHashArr[$headerHash])) {
            $headerValArr = array('from', 'to', 'cc', 'bcc', 'subject', 'delivered-to', 'message-id', 'x-original-to', 'x-sender-ip');
            
            try {
                $headersArr = email_Mime::parseHeaders($headersStr);
                $hashStr = '|';
                foreach ($headerValArr as $hVar) {
                    $hashStr .= email_Mime::getHeadersFromArr($headersArr, $hVar, '*', FALSE) . '|';
                }
                $headerHashArr[$headerHash] = md5($hashStr);
            } catch (ErrorException $e) {
                reportException($e);
                $headerHashArr[$headerHash] = $headerHash;
            }
        }
        
        return $headerHashArr[$headerHash];
    }
	
	
    /**
     * Връща TRUE, ако писмо със същите хедъри е свалено преди,иначе FALSE
     */
    static function isDown($headers) 
    {
        $hash = self::getHeaderHash($headers);
        $hashPart = self::getHeaderPartHash($headers);
        $res = self::fetchField(array("#hash = '[#1#]' OR #hash = '[#2#]'", $hash, $hashPart), 'id', FALSE) > 0;
		
        return $res;
    }
	
    
    /**
     * Задава статуса по отношение на 
     * Връща id на записа, ако задаването е успешно и NULL в противен случай
     */
    static function setStatus($headers, $status, $accId, $uid)
    {   
        $rec = new stdClass();
        $rec->hash = self::getHeaderPartHash($headers);
        if(self::fetchField("#hash = '{$rec->hash}'", 'id')) {
			
            return FALSE;
        }

        $rec->accountId = $accId;

        $rec->uid = $uid;

        $rec->status = $status;
        
        $rec->downloadedOn = dt::now();
        
        $rec->deleted = 'no';

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
            $incQuery->show('emlFile,accId,uid');
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