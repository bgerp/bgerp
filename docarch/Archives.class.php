<?php
/**
 * Мениджър Архиви
 *
 *
 * @category  bgerp
 * @package   docart
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Архиви
 */

class docarch_Archives extends core_Master
{
    public $title = 'Архив';
    
    public $loadList = 'plg_Created,plg_Modified';
    
    public $listFields = 'id,name,modifiedOn=Модифициране';
    
    protected function description()
    {
        //Наименование на архива
        $this->FLD('name', 'varchar(32)', 'caption=Наименование на архива');
        
        //Видове томове/обеми/контейнери за съхранение
        $this->FLD('volType', 'set(folder=Папка,box=Кутия, case=Нещоси, pallet=Палет, warehouse=Склад)', 'caption=Видове томове');
        
        //Какъв тип документи ще се съхраняват в този архив
        $this->FLD('documents', 'keylist(mvc=core_Classes, select=title,allowEmpty)', 'caption=Документи');
        
        //Кой може да добавя документи в този архив
        $this->FLD('sharedUsers', 'userList(rolesForAll=sales|ceo,allowEmpty,roles=ceo|sales)', 'caption=Потребители');
        
        
        //Срок за съхранение
        $this->FLD('storageTime', 'time(suggestions=1 година|2 години|3 години|4 години|5 години|10 години)', 'caption=Срок');
        
        //$this->setDbUnique('path');
    }
    
    
    /**
     * Преди показване на листовия тулбар
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        //$data->toolbar->addBtn('Бутон', array($mvc, 'Action'));
    }
    
    
    /**
     * @return string
     */
    public function act_Action()
    {
        /**
         * Установява необходима роля за да се стартира екшъна
         */
        requireRole('admin');
        
        
        return 'Action';
    }
   
}
