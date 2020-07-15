<?php


/**
 * Клас 'ztm_Registers' - Документ за Транспортни линии
 *
 *
 * @category  bgerp
 * @package   ztm
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class ztm_RegisterLongValues extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Дълги стойностти на регистрите';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'ztm_Wrapper';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой има право да пише?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('registerId', 'key(mvc=ztm_Registers, select=id)','caption=Регистер,mandatory,input=none');
        $this->FLD('value', 'blob(serialize, compress)', 'mandatory,input=none');
        $this->FLD('hash', 'varchar', 'mandatory,input=none');
        
        $this->setDbUnique('registerId');
        $this->setDbIndex('hash');
    }
}