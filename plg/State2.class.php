<?php


/**
 * Клас 'plg_State2' - Поддръжка на поле 'state' за състояние на ред
 *
 *
 * @category  bgerp
 * @package   plg
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
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
    public function on_AfterDescription(&$mvc)
    {
        if (!isset($mvc->fields['state'])) {
            $mvc->FLD(
                'state',
                'enum(active=Активен,closed=Затворен)',
                'caption=Видимост,input=none,notSorting,notNull,value=active,smartCenter'
            );
            $this->activeState = 'active';
            $this->closedState = 'closed';
        }
        
        setIfNot($mvc->updateExistingStateOnImport, true);
    }
    
    
    /**
     * Определя активното и затвореното състояние
     */
    public function getActiveAndClosedState($mvc)
    { 
        if ($this->activeState && $this->closedState) {
            
            return;
        }
        $opt = $mvc->getFieldType('state')->options;
        
       
        
        if(isset($mvc->activeState)) {
            $this->activeState = $mvc->activeState;
        } else {
            foreach ($this->castToActive as $state) {
                if ($opt[$state]) {
                    $this->activeState = $state;
                    break;
                }
            }
        }

        if(isset($mvc->closedState)) {
            $this->closedState = $mvc->closedState;
        } else {
            foreach ($this->castToClosed as $state) {
                if ($opt[$state]) {
                    $this->closedState = $state;
                    break;
                }
            }
        }
        
        expect($this->activeState && $this->closedState);
    }
    
    
    /**
     * Подрежда по state, за да могат затворените да са отзад
     */
    public static function on_BeforePrepareListFilter($mvc, &$res, $data)
    {
        if (!$mvc->state2PreventOrderingByState) {
            $data->query->orderBy('#state');
        }
    }
    
    
    /**
     * Преди записване на в модела
     *
     * @param crm_Persons $mvc
     * @param stdClass    $rec
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
        // Ако мениджъра иска да се запазят старите състояния на импортираните записи
        if($mvc->updateExistingStateOnImport === false){
            
            // Ако записа е вече съществуващ взима се текущото състояние от базата
            // взима се тук за да може като стигне on_BeforeSave да не подмени състоянието с активно
            $conflictFields = array();
            $exRec = null;
            if(!$mvc->isUnique($rec, $conflictFields, $exRec)){
                $rec->state = $mvc->fetchField($exRec->id, 'state', false);
            }
        }
    }
    
    
    /**
     * Гарантира, че новите записи ще имат state по подразбиране - 'active'
     */
    public function on_BeforeSave(&$invoker, &$id, &$rec, $fields = null)
    {
        if (!$rec->state) {
            $this->getActiveAndClosedState($invoker);
            $rec->state = $this->activeState;
        }
    }
    
    
    /**
     * Ще има ли предупреждение при смяна на състоянието
     *
     * @param stdClass $rec
     * @param string $newState
     *
     * @return string|FALSE
     */
    public static function on_AfterGetChangeStateWarning($mvc, &$res, $rec, $newState)
    {
        if (!isset($res)) {
            $res = false;
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass     $row Това ще се покаже
     * @param stdClass     $rec Това е записа в машинно представяне
     */
    public function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->STATE_CLASS = "state-{$rec->state}";
        $row->ROW_ATTR['class'] .= " state-{$rec->state}";
        
        if ($mvc->haveRightFor('changeState', $rec)) {
            $this->getActiveAndClosedState($mvc);
            
            $newState = ($rec->state == $this->activeState) ? $this->closedState : $this->activeState;
            $warning = $mvc->getChangeStateWarning($rec, $newState);
            $warning = !empty($warning) ? $warning : false;
            $warningToolbar = !empty($warning) ? "warning={$warning}" : '';
            
            $add = '<img src=' . sbf('img/16/lightbulb_off.png') . " width='16' height='16'>";
            $cancel = '<img src=' . sbf('img/16/lightbulb.png') . " width='16' height='16'>";
            
            if ($rec->state == $this->activeState || $rec->state == $this->closedState) {
                $row->state = ht::createLink(
                    $rec->state == $this->activeState ? $cancel : $add,
                    array($mvc, 'changeState', $rec->id, 'ret_url' => true),
                    $warning,
                    array('title' => $rec->state == $this->activeState ? 'Деактивиране' : 'Активиране')
                );
                
                $row->state = ht::createElement(
                    
                    'div',
                    array('style' => 'text-align:center;'),
                    
                    $row->state
                
                );
                
                core_RowToolbar::createIfNotExists($row->_rowTools);
                $singleTitle = tr($mvc->singleTitle);
                $singleTitle = mb_strtolower($singleTitle);
                
                if ($rec->state == $this->activeState) {
                    $row->_rowTools->addLink('Деактивиране', array($mvc, 'changeState', $rec->id, 'ret_url' => true), "ef_icon=img/16/lightbulb.png,title=Деактивиране на|* {$singleTitle},{$warningToolbar}");
                } else {
                    $row->_rowTools->addLink('Активиране', array($mvc, 'changeState', $rec->id, 'ret_url' => true), "ef_icon=img/16/lightbulb_off.png,title=Активиране на|* {$singleTitle},{$warningToolbar}");
                }
            }
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    public function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        
        if ($mvc->haveRightFor('changeState', $rec)) {
            $this->getActiveAndClosedState($mvc);
            
            $singleTitle = mb_strtolower(tr($mvc->singleTitle));
            $newState = ($rec->state == $this->activeState) ? $this->closedState : $this->activeState;
            $warning = $mvc->getChangeStateWarning($rec, $newState);
            
            if ($rec->state == $this->activeState) {
                $data->toolbar->addBtn('Деактивиране', array($mvc, 'changeState', $rec->id, 'ret_url' => true), "ef_icon=img/16/lightbulb.png,title=Деактивиране на|* {$singleTitle},warning={$warning}");
            } else {
                $data->toolbar->addBtn('Активиране', array($mvc, 'changeState', $rec->id, 'ret_url' => true), "ef_icon=img/16/lightbulb_off.png,title=Активиране на|* {$singleTitle},warning={$warning}");
            }
        }
    }
    
    
    /**
     * Прихваща екшън-а 'changeState'
     */
    public function on_BeforeAction($mvc, &$content, &$act)
    {
        if ($act != 'changestate') {
            
            return;
        }
        
        $retUrl = getRetUrl();
        
        $mvc->requireRightFor($act, null, null, $retUrl);
        
        expect($id = Request::get('id', 'int'));
        
        expect($rec = $mvc->fetch($id));
        
        $mvc->requireRightFor($act, $rec, null, $retUrl);
        
        $this->getActiveAndClosedState($mvc);
        
        if ($rec->state == $this->activeState || $rec->state == $this->closedState) {
            $rec->state = ($rec->state == $this->activeState ? $this->closedState : $this->activeState);
            
            $act = '';
            $actState = '';
            if ($rec->state == $this->activeState) {
                $act = 'Активиране';
                $actState = $rec->state;
            } elseif ($rec->state == $this->closedState) {
                $act = 'Затваряне';
                $actState = $rec->state;
            }
            
            if ($act) {
                $mvc->logWrite($act, $rec->id);
            }
            
            $updateFields = 'state';
            if($mvc->hasPlugin('plg_Modified')){
                $updateFields = 'state,modifiedOn,modifiedBy';
            }
            
            if ($actState) {
                $mvc->invoke('BeforeChangeState', array($rec, $actState));
            }
            
            $mvc->save($rec, $updateFields);
            
            if ($actState) {
                $mvc->invoke('AfterChangeState', array($rec, $actState));
            }
        }
        
        $content = new Redirect($retUrl);
        
        return false;
    }
    
    
    /**
     * Поставя изискване да се избират за предложения само активните записи
     */
    public static function on_BeforePrepareSuggestions($mvc, &$suggestions, core_Type $type)
    {
        $type->params['where'] .= ($type->params['where'] ? ' AND ' : '') . " #state = 'active'";
    }
    
    
    /**
     * Поставя изискване да се селектират само активните записи
     */
    public static function on_BeforeMakeArray4Select($mvc, &$optArr, $fields = null, &$where = null)
    {
        $where .= ($where ? ' AND ' : '') . " #state = 'active'";
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'changestate' && !isset($mvc->canChangestate) && $requiredRoles != 'no_one') {
            $requiredRoles = $mvc->getRequiredRoles('edit', $rec, $userId);
        }
    }
}
