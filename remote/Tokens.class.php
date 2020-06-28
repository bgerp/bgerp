<?php 

/**
 * Временни кодове за комуникация с отдалечени системи
 *
 *
 * @category  bgerp
 * @package   remote
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class remote_Tokens extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Временни кодове';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Кодове';
    
    
    /**
     * Разглеждане на листов изглед
     */
    public $canSingle = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'remote_Wrapper';
    
    
    /**
     * Полета за листовия изглед
     */
    // var $listFields = '✍';
    
    
    /**
     * Поле за инструментите на реда
     */
    public $rowToolsField = '✍';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'debug';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Време за живот на token-ите по подразбиране
     */
    const DEFAULT_TOKEN_EXPIRY_TIME = 7200;
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('authId', 'key(mvc=remote_Authorizations)', 'caption=Оторизация,mandatory');
        $this->FLD('token', 'password(64)', 'caption=Временен код,input=none');
        $this->FLD('expiredOn', 'datetime', 'caption=Годен до');
        $this->FLD('ip', 'ip', 'caption=Ip');
        
        $this->setDbUnique('token, authId');
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    public function on_AfterPrepareListFilter($mvc, $data)
    {
    }
    
    
    /**
     * Опитва се да запише подадения $token
     */
    public static function storeToken($authId, $token, $expiredOn)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (strlen($ip) > 15) {
            $ip = substr($ip, 0, 10) . substr(md5($ip), 0, 5);
        }
        
        if ($rec = self::fetch(array("#authId = [#1#] AND #token = '[#2#]'", $authId, $token))) {
            if ($rec->ip != $ip || (dt::mysql2timestamp($rec->expiredOn) - self::DEFAULT_TOKEN_EXPIRY_TIME + 5 < dt::mysql2timestamp())) {
                
                return false;
            }
            
            return $rec->id;
        }
        
        $rec = (object) array('authId' => $authId, 'token' => $token, 'expiredOn' => $expiredOn, 'ip' => $ip);
        
        self::save($rec);
        
        return $rec->id;
    }
    
    
    /**
     * Изтриване на изтеклите токъни
     */
    public function cron_DeleteExpiredTokens()
    {
        $now = dt::now();
        
        $cnt = self::delete("#expiredOn < '{$now}'");
        
        return "Изтрити са ${cnt} tokens";
    }
}
