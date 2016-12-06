<?php


/**
 * Мениджър на моливи
 *
 *
 * @category  bgerp
 * @package   sens2
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class draw_Pens extends core_Master
{


     
    /**
     * Необходими плъгини
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools2, plg_Rejected, draw_Wrapper';
	
    
    
    /**
     * Заглавие
     */
    var $title = 'Моливи';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'ceo,draw,admin';
    
    
    /**
     * Права за запис
     */
    var $canRead = 'ceo, draw, admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'debug';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,admin,draw';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,admin,draw';
    

    /**
     * Полето "Наименование" да е хипервръзка към единичния изглед
     */
    var $rowToolsSingleField = 'name';


    /**
     * Заглавие в единичния изглед
     */
    var $singleTitle = 'Скрипт';


    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/edit.png';

 
    var $rowToolsField = 'name';

    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(255)', 'caption=Наименование, mandatory,notConfig');
        $this->FLD('color', 'color_Type', 'caption=Цвят');
        $this->FLD('background', 'color_Type', 'caption=Фон');
        $this->FLD('thickness', 'double', 'caption=Дебелина,suggestions=0.1|0.2|0.3|0.4|0.5');
        $this->FLD('dasharray', 'varchar', 'caption=Пунктир');

        $this->setDbUnique('name');
    }
    
}
