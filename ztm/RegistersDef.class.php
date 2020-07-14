<?php
/**
 * Мениджър за дефиниране на регистри в Zontromat
 *
 *
 * @category  bgerp
 * @package   ztm
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 
 * @title     Дефинирани регистри в Zontromat
 */
class ztm_RegistersDef extends core_Master
{
    public $title = 'Дефинирани регистри в Zontromat';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ztm, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ztm, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ztm, ceo';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'ztm, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ztm, ceo';
    public $canSingle = 'ztm, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    public $canReject = 'ztm, ceo';
    public $canRestore = 'ztm, ceo';
    
    
    /**
     * Кой може да променя състоянието на документите
     *
     * @see plg_State2
     */
    public $canChangestate = 'ztm, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'ztm_Wrapper, plg_Rejected, plg_Created, plg_State2, plg_RowTools2, plg_Modified, plg_Sorting';
    
    
    /**
     *
     * @var string
     */
     public $listFields = 'name, type, range, plugin, priority, default, description';
    
    /**
     * Описание на модела (таблицата)
     */
    protected function description()
    {
        $this->FLD('name', 'varchar(32)', 'caption=Име');
        $this->FLD('type', 'enum(int, bool, float, str, text, object,array)', 'caption=Тип');
        $this->FLD('range', 'text', 'caption=Диапазон');
        $this->FLD('plugin', 'varchar(32)', 'caption=Модул');
        $this->FLD('priority', 'enum(system, device, global, time)', 'caption=Приоритет за вземане на стойност');
        $this->FLD('default', 'varchar(32)', 'caption=Дефолтна стойност');
        $this->FLD('description', 'text', 'caption=Описание на регистъра');
        
        $this->setDbUnique('name');
        
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param embed_Manager $Embedder
     * @param stdClass      $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        
    }
    
    
    /**
     * Добавя бутони  към единичния изглед на документа
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        
        $data->toolbar->addBtn('Изход', array('ztm_RegistersDef','ret_url' => true));
        
       
    }
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
        
        $data->toolbar->addBtn('Базово Зареждане', array(ztm_RegistersDef, 'BasicLoadingOfRegisters', 'ret_url' => true),
            'order=10,title=Базово Зареждане,ef_icon = img/16/shopping.png');
        
    }
    
    /**
     * Изключва един том от по-голям
     */
    public function act_BasicLoadingOfRegisters()
    {
        /**
         * Установява необходима роля за да се стартира екшъна
         */
        requireRole('ztm');
        
        $regQuery = ztm_RegistersDef::getQuery();
        
        $existingRegisters = arr::extractValuesFromArray($regQuery->fetchAll(), 'name');
        
        $baseRegistersArr = array();
        
        $baseRegistersArr = file('../bgerp/ztm/csv/Registri.csv');
        
        for ($i = 1; $i<= count($baseRegistersArr); $i++){
            
            list($name, $type, $range, $plugin, $priority, $default, $description) = explode(',', $baseRegistersArr[$i]);
            
            if (in_array($name, $existingRegisters))continue;
            
            $baseRegisters = (object)array(
                'name' => $name,
                'type' => $type,
                'range' => $range,
                'plugin' => $plugin,
                'priority' => $priority,
                'default' => $default,
                'description' => $description,
            );
            
            ztm_RegistersDef::save($baseRegisters);
            
            unset($name, $type, $range, $plugin, $priority, $default, $description);
      
        }

        
        return new Redirect(getRetUrl());
    }

    
}