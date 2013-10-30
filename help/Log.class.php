<?php 


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
    var $loadList = 'help_Wrapper, plg_RowTools';
    
    
   
    /**
     * Полета за листовия изглед
     */
    var $listFields = '✍,userId,infoId,seeOn,seeCnt';


    /**
     * Поле за инструментите на реда
     */
    var $rowToolsField = '✍';
    
    
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
		$this->FLD('infoId', 'key(mvc=help_Info)', 'caption=За кой клас, hint=За кой клас се отнася информацията');
		$this->FLD('seeOn', 'datetime', 'caption=Видяно->На, hint=Кога за първи път е видяно');
        $this->FLD('seeCnt', 'int', 'caption=Видяно->Брой, hint=Колко пъти е видяно');

        $this->setDbUnique("userId,infoId");
    }


    /**
     * Трябва ли текущият потребител да види тази помощна информация?
     */
    static function haveToSee($infoId, $userId = NULL)
    {
        // Ако нямаме потребител, вземаме текущия
        if(!$userId) {
            $userId = core_Users::getCurrent();
        }
        $nowDate = dt::now();
        $conf = core_Packs::getConfig('help');

        $rec = help_Log::fetch("#infoId = {$infoId} && #userId = {$userId}");
        if(!$rec) {
            $rec = new stdClass();
            $rec->infoId = $infoId;
            $rec->userId = $userId;
            $rec->seeOn  = $nowDate;
            $rec->seeCnt = 0;
        }

        if($rec->seeCnt < $conf->HELP_MAX_SEE_CNT || $rec->seeCnt == 0) {
            $rec->seeCnt++;
            self::save($rec);
        }

        $untilDate = dt::timestamp2mysql(dt::mysql2timestamp($rec->seeOn) + $conf->HELP_MAX_SEE_TIME);

        if($untilDate > $nowDate || $rec->seeCnt < $conf->HELP_MAX_SEE_CNT) {
                
                return TRUE;
        }

        return FALSE;
    }
    
}