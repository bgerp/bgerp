<?php



/**
 * Клас 'plg_State2' - Поддръжка на поле 'state' за състояние на ред
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_State2 extends core_Plugin
{

    /**
     * Наименование на активното състояние
     */
    private $activeState;
    
    /**
     * Наименование на затвореното състояние
     */
    private $closedState;


    /**
     * Кои състояния се кастват до Активно
     */
    private $castToActive = array('active', 'opened', 'free');


    /**
     * Кои състояния се кастват до затворено
     */
    private $castToClosed = array('closed', 'stopped', 'rejected');

    
    /**
     * Добавя полето за състояние, ако то липсва
     */
    function on_AfterDescription(&$mvc)
    {
        if (!isset($mvc->fields['state'])) {
            $mvc->FLD('state',
                'enum(active=Активен,closed=Затворен)',
                'caption=Видимост,input=none,notSorting,notNull,value=active');
            $this->activeState = 'active';
            $this->closedState = 'closed';
        } 
    }


    /**
     * Определя активното и затвореното състояние
     */
    function getActiveAndClosedState($mvc)
    {
        if($this->activeState && $this->closedState) {

            return;
        }
        $opt = $mvc->getFieldType('state')->options;
            
        foreach($this->castToActive as $state) {
            if($opt[$state]) {
                $this->activeState = $state;
                break;
            }
        }

        foreach($this->castToClosed as $state) {
            if($opt[$state]) {
                $this->closedState = $state;
                break;
            }
        }

        expect($this->activeState && $this->closedState);
    }
    
    
    /**
     * Подрежда по state, за да могат затворените да са отзад
     */
    function on_BeforePrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('#state');
    }
    
    
    /**
     * Гарантира, че новите записи ще имат state по подразбиране - 'active'
     */
    function on_BeforeSave(&$invoker, &$id, &$rec, $fields = NULL)
    {   
        if (!$rec->state) {
            $this->getActiveAndClosedState($invoker);
            $rec->state = $this->activeState;
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->ROW_ATTR['class'] .= " state-{$rec->state}";
        
        if ($mvc->haveRightFor('changeState', $rec)) {
           
            $this->getActiveAndClosedState($mvc);

            $add = "<img src=" . sbf("img/16/lightbulb_off.png") . " width='16' height='16'>";
            $cancel = "<img src=" . sbf("img/16/lightbulb.png") . " width='16' height='16'>";
            
            if($rec->state == $this->activeState || $rec->state == $this->closedState) {
                $row->state = ht::createLink($rec->state == $this->activeState ? $cancel : $add ,
                    array($mvc, 'changeState', $rec->id, 'ret_url' => TRUE),
                    NULL,
                    array('title' => $rec->state == $this->activeState ? 'Деактивиране' : 'Активиране'));
            
                $row->state = ht::createElement('div',
                    array('style' => "text-align:center;"), $row->state);

                core_RowToolbar::createIfNotExists($row->_rowTools);
                $singleTitle = tr($mvc->singleTitle);
                $singleTitle = mb_strtolower($singleTitle);
                
                if($rec->state == $this->activeState) {
                    $row->_rowTools->addLink('Деактивиране', array($mvc, 'changeState', $rec->id, 'ret_url' => TRUE), "ef_icon=img/16/lightbulb.png,title=Деактивиране на|* {$singleTitle}");
                } else {
                    $row->_rowTools->addLink('Активиране', array($mvc, 'changeState', $rec->id, 'ret_url' => TRUE), "ef_icon=img/16/lightbulb_off.png,title=Активиране на|* {$singleTitle}");
                }

            }
        }
    }
    
    
    /**
     * Прихваща екшън-а 'changeState'
     */
    function on_BeforeAction($mvc, &$content, &$act)
    {
        if($act != 'changestate') return;
        
        $retUrl = getRetUrl();
        
        $mvc->requireRightFor($act, NULL, NULL, $retUrl);
        
        expect($id = Request::get('id', 'int'));
        
        expect($rec = $mvc->fetch($id));
        
        $mvc->requireRightFor($act, $rec, NULL, $retUrl);
        
        $this->getActiveAndClosedState($mvc);

        if($rec->state == $this->activeState || $rec->state == $this->closedState) {

            $rec->state = ($rec->state == $this->activeState ? $this->closedState : $this->activeState);
            
            $act = '';
            if ($rec->state == $this->activeState) {
                $act = 'Активиране';
            } elseif ($rec->state == $this->closedState) {
                $act = 'Затваряне';
            }
            
            if ($act) {
                $mvc->logWrite($act, $rec->id);
            }
            
            $mvc->save($rec, 'state');
        }
        
        $content = new Redirect($retUrl);
        
        return FALSE;
    }
    
    
    /**
     * Поставя изискване да се избират за предложения само активните записи
     */
    public static function on_BeforePrepareSuggestions($mvc, &$suggestions, core_Type $type)
    {
    	$type->params['where'] .= ($type->params['where'] ? " AND " : "") . " #state = 'active'";
    }
    
    
    /**
     * Поставя изискване да се селектират само активните записи
     */
    public static function on_BeforeMakeArray4Select($mvc, &$optArr, $fields = NULL, &$where = NULL)
    {
        $where .= ($where ? " AND " : "") . " #state = 'active'";
    }

    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if($action == 'changestate' && !isset($mvc->canChangestate) && $requiredRoles != 'no_one' && is_object($rec) && $rec->id) {
            
            $requiredRoles = $mvc->getRequiredRoles('edit', $rec, $userId);
        }
    }

    
}