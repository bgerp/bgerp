<?php



/**
 * Модел "Гласуване"
 *
 *
 * @category  bgerp
 * @package   survey
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class survey_Votes extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Гласуване';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'survey_Wrapper, plg_Sorting, plg_Created';
    
  
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'id, alternativeId, rate, userUid, createdOn';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Гласуване';

    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = '40';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'survey, ceo, admin';
    
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'survey,ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('alternativeId', 'key(mvc=survey_Alternatives, select=label)', 'caption=Въпрос, input=hidden, silent');
        $this->FLD('rate', 'key(mvc=survey_Options, select=text)', 'caption=Отговор');
        $this->FLD('userUid', 'varchar(80)', 'caption=Потребител');
        
        $this->setDbUnique('alternativeId, userUid');
    }
    
    
    /**
     * Екшън който записва гласуването, и се вика от Ajax заявка
     */
    public function act_Vote()
    {
        //Намираме на кой въпрос, кой отговор е избран
        expect($alternativeId = Request::get('alternativeId', 'int'));
        expect($rowId = Request::get('rowId'), 'int');
        
        // Подготвяме записа
        $rec = new stdClass();
        $rec->alternativeId = $alternativeId;
        $rec->rate = $rowId;
        $rec->userUid = static::getUserUid($alternativeId);
        
        $altRec = new stdClass();
        $altRec->id = $alternativeId;
    
        // Докато потребителя може да гласува презаписваме отговора който
        // е посочил на дадения въпрос,
        if (survey_Alternatives::haveRightFor('vote', $altRec)) {
            $this->save($rec, null, 'REPLACE');
            echo  json_encode(array('success' => 'yes'));
        } else {
            echo  json_encode(array('success' => 'no'));
        }
        
        shutdown();
    }
    
    
    /**
     *
     * @param  int   $alternativeId - ид на въпроса
     * @return mixed $rec/FALSE - Кой е последния отговор, ако има
     */
    public static function lastUserVote($alternativeId)
    {
        $userUid = static::getUserUid($alternativeId);
        $query = static::getQuery();
        $query->where(array('#alternativeId = [#1#]', $alternativeId));
        $query->where(array("#userUid = '[#1#]'", $userUid));
        if ($rec = $query->fetch()) {
            
            return $rec;
        }
        
        return false;
    }
    
    
    /**
     * Намираме userUid-a  на гласувалия потребител:
     * Ако е потребител в системата това е ид-то му,
     * Ако анкетата е изпратена по поща това е мид-а на анкетата
     * Ако потребителя не е потребител в системата и нямаме мид, записваме
     * Ип-то му
     * @return string $userUid - Потребителя, който е гласувал
     */
    public static function getUserUid($alternativeId)
    {
        expect($alternativeId > 0);

        $aRec = survey_Alternatives::fetch((int) $alternativeId);
        $sRec = survey_Surveys::fetch($aRec->surveyId);


        $uid = '';
        if ($mid = Request::get('m') && $sRec->userBy != 'browser') {
            $uid = 'mid|' . $mid;
        } elseif (core_Users::haveRole('user') && $sRec->userBy != 'browser') {
            $uid = 'id|' . core_Users::getCurrent();
        } elseif ($sRec->userBy == 'browser') {
            $uid = 'brid|' . log_Browsers::getBridId();
        } else {
            $uid = 'ip|' . $_SERVER['REMOTE_ADDR'];
        }
 
        return $uid;
    }
    
    
    /**
     * Преброява гласовете които е получил даден въпрос, ако не е зададен
     * номер на ред, връща броя на всички гласувания на въпроса, ако е зададен
     * преброява само гласовете които са дадени на въпросния ред
     * @param int alternativeId - ид на въпроса
     * @param int row - реда който е избран
     * @return int - Броя гласове
     */
    public static function countVotes($alternativeId, $id = null)
    {
        $query = static::getQuery();
        $query->where(array('#alternativeId = [#1#]', $alternativeId));
        if ($id) {
            $query->where(array('#rate = [#1#]', $id));
        }
        
        return $query->count();
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if ($fields['-list']) {
            
            // Кой го е отговорил
            $row->userUid = $mvc->verbalUserUid($rec->userUid);
        }
    }
    
    
    /**
     * Връща вербалната стойност на подадения userUid
     * @param string(32) $userUid - ид на потребител/мид/ип на
     *                            гласувалия потребител
     */
    public function verbalUserUid($userUid)
    {
        list($type, $val) = explode('|', $userUid);
        $varchar = cls::get('type_Varchar');
        
        if ($type == 'id') {
            
            // ако е ид, намираме ника на потребителя
            $nick = core_Users::fetchField($val, 'nick');
            $userUid = $varchar->toVerbal($nick);
        } elseif ($type == 'mid') {
            
            // ако е mid
            $userUid = $varchar->toVerbal("mid:{$val}");
        } elseif ($type == 'ip') {
            
            // ако е Ип на потребител
            $userUid = $varchar->toVerbal($val);
            $userUid = ht::createLink("IP: {$userUid}", "http://bgwhois.com/?query={$uid->ip}", null, array('target' => '_blank'));
        }
        
        return $userUid;
    }
    
    
    /**
     * Модификация на списъка с резултати
     */
    public function on_AfterPrepareListRecs($mvc, $res, $data)
    {
        // За коя анкета филтрираме гласовете
        $surveyId = Request::get('surveyId', 'int');
        if ($data->recs && $surveyId) {
            foreach ($data->recs as $rec) {
                
                // За всеки въпрос на който е отговорено, проверяваме дали
                // принадлежи на посочената анкета, ако не го премахваме
                $recSurveyId = survey_Alternatives::fetchField($rec->alternativeId, 'surveyId');
                if ($recSurveyId != $surveyId) {
                    unset($data->recs[$rec->id]);
                }
            }
        }
    }
    
    
    /**
     * Извиква се преди подготовката на титлата в списъчния изглед
     */
    public static function on_AfterPrepareListTitle($mvc, $res, $data)
    {
        $surveyId = Request::get('surveyId', 'int');
        if (isset($surveyId)) {
            if ($surveyTitleRec = survey_Surveys::fetch($surveyId)) {
                $title = survey_Surveys::getVerbal($surveyTitleRec, 'title');
                $data->title = "Гласуване за|* <span class=\"green\">{$title}</span>";
            }
        }
    }
}
