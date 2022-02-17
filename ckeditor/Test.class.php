<?php


/**
 * Тестове на CKEditor
 *
 * @category  bgerp
 * @package   blogm
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class ckeditor_Test extends core_Master
{
    /**
     * Заглавие на страницата
     */
    public $title = 'Тестове за CKEditor';
    
 
    /**
     * Зареждане на необходимите плъгини
     */
    public $loadList = 'plg_RowTools2, plg_State, plg_Printing, blogm_Wrapper, 
        plg_Search, plg_Created, plg_Modified, plg_Rejected';
    
  
 
    /**
     * Кой може да листва статии и да чете  статия
     */
    public $canRead = 'ceo, admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, admin';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, admin';
    
    
    /**
     * Кой може да добявя,редактира или изтрива статия
     */
    public $canWrite = 'ceo, admin';
    

    
    
    /**
     * Единично заглавие на документа
     */
    public $singleTitle = 'Test';
    
 
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('text1', 'RichHtml', 'caption=Test 1');
        $this->FLD('text2', 'RichHtml', 'caption=Test 2');
    }
}
