<?php


/**
 * Клас 'cond_VatExceptions' - ДДС изключения
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cond_VatExceptions extends core_Manager
{
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin';


    /**
     * Кой може да изтрива
     */
    public $canDelete = 'ceo,admin';


    /**
     * Кой може да добавя
     */
    public $canAdd = 'ceo,admin';


    /**
     * Кой може да редактира
     */
    public $canEdit = 'ceo,admin';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2,cond_Wrapper,plg_Created,plg_State2';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title,validTo,lastUsedOn,createdOn,createdBy,state';


    /**
     * Заглавие
     */
    public $title = 'ДДС изключения';


    /**
     * Заглавие на единичния обект
     */
    public $singleTitle = 'ДДС изключение';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar', 'caption=Изключение');
        $this->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последно,input=none,column=none');
        $this->FLD('validTo', 'date', 'caption=Валидно до');

        $this->setDbUnique('title');
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        if($rec->state == 'active') {
            if ($rec->validTo && $rec->validTo <= dt::now()) {
                $rec->state = 'closed';
                $form->setWarning('validTo', "Въведен е срок на валидност в миналото, изключението ще се деактивира|*!");
            }
        } else {
            $exValidTo = $mvc->fetchField($rec->id, 'validTo', false);
            if($exValidTo != $rec->validTo){
                $rec->state = 'active';
            }
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     * Забранява изтриването на вече използвани сметки
     *
     * @param core_Mvc      $mvc
     * @param string        $requiredRoles
     * @param string        $action
     * @param stdClass|NULL $rec
     * @param int|NULL      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'delete' && isset($rec)){
            if(!empty($rec->lastUsedOn)){
                $requiredRoles = 'no_one';
            }
        }

        if($action == 'changestate' && isset($rec)){
            if(!empty($rec->validTo) && $rec->validTo <= dt::now()){
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Какво е ДДС изключението за документите в нишката
     *
     * @param int $threadId
     * @return null|int
     */
    public static function getFromThreadId($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        if(is_object($firstDoc)){
            if($firstDoc->getInstance()->getField('vatExceptionId', false)) return $firstDoc->fetchField('vatExceptionId');
        }

        return null;
    }


    /**
     * Крон процес за затваряне на ДДС изключенията
     */
    public function cron_CloseExceptions()
    {
        $today = dt::today();
        $query = $this->getQuery();
        $query->where("#state = 'active' AND #validTo IS NOT NULL AND #validTo <= '{$today}'");
        while($rec = $query->fetch()){
            $rec->state = 'closed';
            $this->save($rec, 'state');
        }
    }
}