<?php

/**
 * Променливи в логическите блокове
 *
 *
 * @category  bgerp
 * @package   sens2
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sens2_ScriptDefinedVars  extends core_Detail
{
    public $oldClassName = 'sens2_LogicDefinedVars';
    
    /**
     * Необходими плъгини
     */
    var $loadList = 'plg_Created, plg_Modified,plg_RowTools, sens2_Wrapper';
                      
    
    /**
     * Заглавие
     */
    var $title = 'Редове към Логическите блокове';
    
    var $singleTitle = 'Действие';

    /**
     * Права за писане
     */
    var $canWrite = 'ceo,sens,admin';
    
    
    /**
     * Права за запис
     */
    var $canRead = 'ceo, sens, admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo, sens, admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,admin,sens';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,admin,sens';
    

    /**
     * Ключ към матера
     */
    var $masterKey = 'scriptId';
    

    var $currentTab = 'Логика';

    var $listFields = '№,name,scope,value,modifiedOn=Модифициране';

    var $rowToolsField = '№';
    
    
    /**
     * Runtime съхраняване на контекстите за всеки скрипт
     */
    static $contex = array();


    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('scriptId', 'key(mvc=sens2_Scripts,title=name)', 'caption=Блок,column=none,silent,oldFieldName=logicId');
        $this->FLD('name', 'identifier(32,cyr)', 'caption=Променлива,mandatory');
        $this->FLD('scope', 'enum(local=Локална,global=Глобална)', 'caption=Видимост');
        $this->FLD('value', 'double', 'caption=Стойност,input=none,notNull');

        $this->setDbUnique('scriptId,name');
    }
    

    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {

    }


    /**
     * Изпълнява се след въвеждането на данните от заявката във формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
    }


    /**
     *
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy("#name", 'ASC');
    }


    /**
     * Изпълнява се след подготвянето на вербалните стойности за един ред
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if($rec->scope == 'global') {
            $cnt = self::count(array("#name = '[#1#]' AND #scope = 'global'", $rec->name));
            $row->scope .= " ({$cnt})";
        }

        $row->name = '$' . $row->name;
    }

    
    /**
     * Връща контекста от променливи за зададения скррипт
     */
    static function getContex($scriptId)
    {
        if(!isset(self::$contex[$scriptId])) {
            // Вземаме стойностите на променливите за контекста на дадения скрипт
            self::$contex[$scriptId] = array();
            $query = self::getQuery();
            while($rec = $query->fetch("#scriptId = {$scriptId}")) {
                self::$contex[$scriptId]['$' . $rec->name] = (double) $rec->value ;
            }
        }

        return self::$contex[$scriptId];
    }


    /**
     * Задава стойност на посочената променлива
     * Връща броя на промените записи или FALSE, ако не бъде обновено нищо
     */
    static function setValue($scriptId, $var, $value)
    {
        $var = ltrim($var, '$');

        $rec = self::fetch(array("#scriptId = {$scriptId} AND #name = '[#1#]'", $var));
        
        if(!$rec) {

            return FALSE;
        }

        $now = dt::verbal2mysql();

        $me = cls::get('sens2_ScriptDefinedVars');
        
        $table = $me->dbTableName;
        
        $query = "UPDATE `{$table}` SET `value` = {$value}, `modified_on` = '{$now}' WHERE `name` = '{$var}' AND";
        
        if($rec->scope == 'global') {
            $query .= " `scope` = 'global'";
        } else {
            $query .= " `script_id` = {$scriptId}";
        }
        
        if(self::$contex[$scriptId]) {
            self::$contex[$scriptId][$var] = $value;
        }

        $me->db->query($query);

        $me->dbTableUpdated();


        return $me->db->affectedRows();
    }


}
