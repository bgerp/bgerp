<?php 


/**
 * Временни кодове за комуникация с отдалечени системи
 *
 *
 * @category  bgerp
 * @package   remote
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class remote_Tokens extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Временни кодове";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Кодове";

    
    /**
     * Разглеждане на листов изглед
     */
    var $canSingle = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'remote_Wrapper';
    



    /**
     * Полета за листовия изглед
     */
    // var $listFields = '✍';


    /**
     * Поле за инструментите на реда
     */
    var $rowToolsField = '✍';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'debug';
        
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'no_one';


    /**
     * Време за живот на token-ите по подразбиране
     */
    const DEFAULT_TOKEN_EXPIRY_TIME = 7200;


    /**
     * Описание на модела
     */
    function description()
    {
		$this->FLD('authId', 'key(mvc=remote_Authorizations)', 'caption=Оторизация,mandatory');
		$this->FLD('token', 'password(64)', 'caption=Временен код,input=none');
        $this->FLD('expiredOn', 'datetime', 'caption=Годен до');
        
        $this->setDbUnique('token, authId');
    }
    


    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
    }


    /**
     * Опитва се да запише подадения $token
     */
    public static function storeToken($authId, $token, $expiredOn)
    {
        if(self::fetch(array("#authId = [#1#] AND #token = '[#2#]'", $authId, $token))) {

            return FALSE;
        }

        $rec = (object) array('authId' => $authId, 'token' => $token, 'expiredOn' => $expiredOn);
        
        self::save($rec);

        return $rec->id;
    }


    /**
     * Изтриване на изтеклите токъни
     */
    function cron_DeleteExpiredTokens()
    {
        $now = dt::now();
        
        $cnt = self::delete("#expiredOn < '{$now}'");

        return "Изтрити са $cnt tokens";
    }



}