<?php 


// Игнориране на затварянето на модул "Help"
defIfNot('BGERP_DEMO_MODE', FALSE);


/**
 * Подсистема за помощ - Логове
 *
 *
 * @category  bgerp
 * @package   help
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class help_Log extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Логове";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Лог";

    
    /**
     * Разглеждане на листов изглед
     */
    var $canSingle = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'help_Wrapper, plg_RowTools2';
    
    
   
    /**
     * Полета за листовия изглед
     */
    var $listFields = 'userId,infoId,seeOn,seeCnt,closedOn';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'user';
        
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'no_one';

    
    /**
     * Описание на модела
     */
    function description()
    {
		$this->FLD('userId', 'key(mvc=core_Users)', 'caption=Потребител');
		$this->FLD('infoId', 'key(mvc=help_Info,select=title)', 'caption=За кой клас, hint=За кой клас се отнася информацията');
		$this->FLD('seeOn', 'datetime', 'caption=Видяно->На, hint=Кога за първи път е видяно');
        $this->FLD('seeCnt', 'int', 'caption=Видяно->Брой, hint=Колко пъти е видяно');
        $this->FLD('closedOn', 'datetime', 'caption=Затворено->На, hint=Кога е затворено');

        $this->setDbUnique("userId,infoId");
    }
    
    
    /**
     * Как да вижда текущият потребител тази помощна информация?
     */
    static function getDisplayMode($infoId, $userId = NULL, $increasSeeCnt=TRUE)
    {

        // Ако нямаме потребител, вземаме текущия
        if(!isset($userId)) { 
            $userId = core_Users::getCurrent();
        }

        if(!$userId) {
            return 'none';
        }

        $nowDate = dt::now();
        $conf = core_Packs::getConfig('help');

        $rec = help_Log::fetch("#infoId = {$infoId} AND (#userId = {$userId})");
        if(!$rec) {
            $rec = new stdClass();
            $rec->infoId = $infoId;
            $rec->userId = $userId;
            $rec->seeOn  = $nowDate;
            $rec->seeCnt = 0;
            $rec->closedOn = NULL;
        }

        if($rec->seeCnt < max($conf->HELP_MAX_CLOSE_DISPLAY_CNT, $conf->HELP_MAX_OPEN_DISPLAY_CNT)) {
            
            if ($increasSeeCnt) {
                $rec->seeCnt++;
            }
            
            self::save($rec);
        }
		
        // Ако се в лимита за време/показвания за отворено показване и помощтта не е затворена ръчно
        // то връщаме режима за показване 'open'
        $untilOpenDate = dt::timestamp2mysql(dt::mysql2timestamp($rec->seeOn) + $conf->HELP_MAX_OPEN_DISPLAY_TIME);
        if(($untilOpenDate > $nowDate || $rec->seeCnt < $conf->HELP_MAX_OPEN_DISPLAY_CNT) && !$rec->closedOn) {
                
                return 'open';
        }
        
        /*
         * Ако и времето и брояча са под определените лимити за показване в затворено състояние, то
         * връщаме 'closed'
         */
        $untilCloseDate = dt::timestamp2mysql(dt::mysql2timestamp($rec->seeOn) + $conf->HELP_MAX_CLOSE_DISPLAY_TIME);
        if($untilCloseDate > $nowDate || $rec->seeCnt < $conf->HELP_MAX_CLOSE_DISPLAY_CNT) {
        	
        	if(BGERP_DEMO_MODE === TRUE) {
        		
        		return 'open';
        	} else {
                
                return 'close';
        	}
        }
        
        // Ако сме решили, че искаме винаги да се показва, дори и ако е затворено ръчно
        if(BGERP_DEMO_MODE === TRUE) {
        	return 'open';
        } else {
	        // Ако не трябва да показваме информацията нито в отворено, нито в затворено състояние
	        // връщаме 'none'
	        return 'none';
        }
    }
    
    
    /**
     * Затворил ли е потребителя информацията собственоръчно?
     */
    static function act_CloseInfo()
    {
    	// За кой клас се отнася
    	$id = core_Request::get('id', 'int');

    	// днешната дата
        $nowDate = dt::now();
        
        $cu = core_Users::getCurrent();
    	
    	// Намираме  запис
    	$rec = help_Log::fetch("#infoId = {$id} AND #userId = {$cu}"); 
    	
    	if ($rec) {
    		
	    	// добавяме дата
	    	$rec->closedOn = $nowDate;
	    	
	    	// и я записваме
	    	self::save($rec, 'closedOn');
    	}
    	
    	if (Request::get('ajax_mode')) {
    	    
    	    return array();
    	} else {
    	    shutdown();
    	}
    }
    
    
    /**
     * Увеличава броя на вижданията
     */
    static function act_See()
    {
    	// За кой клас се отнася
    	$id = core_Request::get('id', 'int');
        
        $cu = core_Users::getCurrent();
    	
    	// Намираме  запис
    	$rec = help_Log::fetch("#infoId = {$id} AND #userId = {$cu}"); 
    	
    	if ($rec){
    		
	    	$rec->seeCnt++;
	    	
	    	self::save($rec, 'seeCnt');
    	}
    	
    	if (Request::get('ajax_mode')) {
    	    
    	    return array();
    	} else {
    	    shutdown();
    	}
    }
}
