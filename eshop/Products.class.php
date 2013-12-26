<?php



/**
 * Мениджър на групи с продукти.
 *
 *
 * @category  bgerp
 * @package   eshop
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class eshop_Products extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Продукти в онлайн магазина";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Е-Магазин";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, eshop_Wrapper, plg_State2';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,name,groupId,state';
    
    
    /**
     * Полета по които се прави пълнотекстово търсене от плъгина plg_Search
     */
    var $searchFields = 'name';
    
            
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Продукт";
    
    
    /**
     * Икона за единичен изглед
     */
    var $singleIcon = 'img/16/wooden-box.png';

    
    /**
     * Кой може да чете
     */
    var $canRead = 'eshop,ceo';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    var $canEditsysdata = 'eshop,ceo';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'eshop,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'eshop,ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'eshop,ceo';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'eshop,ceo';
	
    
    /**
     * Кой може да качва файлове
     */
    var $canWrite = 'eshop,ceo';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'eshop,ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';


    /**
     * Нов темплейт за показване
     */
    //var $singleLayoutFile = 'cat/tpl/SingleGroup.shtml';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('code', 'varchar(10)', 'caption=Код');
        $this->FLD('name', 'varchar(64)', 'caption=Продукт, mandatory,width=100%');
        $this->FLD('image', 'fileman_FileType(bucket=eshopImages)', 'caption=Илюстрация');
        $this->FLD('info', 'richtext(bucket=Notes)', 'caption=Описание');

        // Запитване за нестандартен продукт
        $this->FLD('coDriver', 'class(interface=techno_ProductsIntf)', 'caption=Запитване->Драйвер');
        $this->FLD('coParams', 'text', 'caption=Запитване->Параметри');
        $this->FLD('coMoq', 'varchar', 'caption=Запитване->МКП,hint=Минимално количество за поръчка');

        $this->FLD('groupId', 'key(mvc=eshop_Groups,select=name)', 'caption=Група, mandatory, silent');

        $this->setDbUnique('code');
    }


    /**
     * $data->rec, $data->row
     */
    function prepareGroupList_($data)
    {
        $data->row = $this->recToVerbal($data->rec);
    }


    /**
     *
     * @return $tpl
     */
    function renderGroupList_($data)
    {   
        $layout = new ET();

        if(is_array($data->rows)) {
            foreach($data->rows as $id => $row) {
                
                $rec = $data->recs[$id];

                $pTpl = new ET(getFileContent('eshop/tpl/ProductListGroup.shtml'));
                
                if($rec->code) {
                    $row->code      = "<span>" . tr('Код') . ": <b>{$row->code}</b></span>";
                }
 
                if($rec->coMoq) {
                    $row->coMoq = "<span>" . tr('МКП') . ": <b>{$row->coMoq}</b></span>";
                }

                if($rec->coDriver) {
                    $row->coInquiry   = ht::createLink(tr('Запитване'), array(cls::get($rec->coDriver), 'Inquiry', $id), 'Все още не работи...', 'ef_icon=img/16/button-question-icon.png');
                }

                $pTpl->placeObject($row);

                $layout->append($pTpl);
            }
        }

        return $layout;
    }

}