<?php


/**
 * Пращане на SMS' и чрез prosms
 * 
 * @category  vendors
 * @package   prosms
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class prosms_SMS extends core_Manager
{
	
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'no_one';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'no_one';
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
	/**
	 * Интерфейсния клас за изпращане на SMS
	 */
	var $interfaces = 'callcenter_SentSMSIntf'; 
	
	
	/**
	 * 
	 */
	var $title = 'proSMS';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
	    $this->FLD('uid', 'varchar(16)', 'caption=Хендлър, input=none');
	    $this->FLD('data', 'blob(compress, serialize)');
	    
	    $this->setDbUnique('uid');
	}
	
	
	/**
     * Интерфейсен метод за изпращане на SMS' и
     * 
     * @param string $number - Номера на получателя
     * @param string $message - Текста на съобщението
     * @param string $sender - От кого се изпраща съобщението
     * @param array $params - Масив с класа и функцията, която ще се извика в act_Delivery
     * @params['class'] - Класа
     * @params['function'] - Функцията, която да се стартира от съответния клас
     * 
     * @return array $nRes - Mасив с информация, дали е получено
     * $nRes['sended'] boolean - Дали е изпратен или не
     * $nRes['uid'] string - Уникалното id на съобщението
     * $nRes['msg'] - Статуса
     */
    function sendSMS($number, $message, $sender, $params=array())
    {
        // Конфигурацията на модула
    	$conf = core_Packs::getConfig('prosms');
    	
    	// Масива, който ще връщаме
        $nRes = array();
        
        // Ако константата за УРЛ-то е зададена
        if ($conf->PROSMS_URL != '') {
            
            // Вземаме шаблона
            $tpl = new ET($conf->PROSMS_URL);
            
            // Заместваме данните
            $tpl->placeArray(array('USER' => urlencode($conf->PROSMS_USER), 'PASS' => urlencode($conf->PROSMS_PASS), 'FROM' => urlencode($sender), 'ID' => $uid, 'PHONE' => urlencode($number), 'MESSAGE' => urlencode($message)));
            
            // Вземаме съдържанието
            $url = $tpl->getContent();
            
            // Опитваме се да изпратим
            $ctx = stream_context_create(array('http' => array('timeout' => 5)));
            
            // Вземаме резултата
            $res = file_get_contents($url, 0, $ctx);
            
            // Ако има грешки
            if ((int)$res != 0) {
                
                // Сетваме променливите
                $nRes['sended'] = FALSE;
                $nRes['msg'] = tr("Не може да се изпрати");
            } else {
                
                // Ако няма грешки
                
                // Опитваме се да генерираме уникален номер
                do {
                    $uid = static::getUid();
                } while (static::fetch("#uid = '{$uid}'", 'id'));
                
                // Сетваме променливите
                $nRes['sended'] = TRUE;
                $nRes['msg'] = tr("Успешно изпратен SMS");
                $nRes['uid'] = $uid;
                
                // Създаваме запис в модела
                $nRec = new stdClass();
                $nRec->uid = $uid;
                $nRec->data = $params;
                static::save($nRec);
            }
        } else {
            
            // Ако не е дефиниран шаблона
            
            // Сетваме грешките
            $nRes['sended'] = FALSE;
            $nRes['msg'] = tr("Липсва константа за URL' то");
            
            // Записваме в лога
            static::log("Липсва константа за URL' то");
        }
    	
        return $nRes;
    }
    
    
    /**
     * Връща уникално id
     */
    static function getUid()
    {
        return $uid = str::getRand('aaaaaa') . 'prosms';
    }
    
    
    /**
     * Интерфейсен метод
     * Отбелязване на статуса на съобщенито
     * Извиква се от външната програма след промяна на статуса на SMS'а
     */
    function act_Delivery()
    {
        // Вземаме променливите
        $uid = request::get('idd', 'varchar');
        $status = request::get('status', 'varchar');
        $code = request::get('code', 'varchar');
        
        // Очакваме да има такъв запис
        expect($rec = static::fetch(array("#uid = '[#1#]'", $uid)), "Невалидна заявка.");
        
        // Ако не е получен успешно
        if ((int)$code !== 0) {
            $status = 'receiveError';
        } else {
            $status = 'received';
        }
        
        // Ако има зададен клас и фунцкия
        if ($rec->data['class'] && $rec->data['function']) {
            try {
                
                // Извикваме я
                call_user_func(array($rec->data['class'], $rec->data['function']), $rec->uid, $status);
                
            } catch (Exception $e) { }
        }
    }
}
