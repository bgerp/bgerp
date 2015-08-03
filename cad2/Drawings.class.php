<?php


/**
 * Контролер за изчертаване на геометрични фигури
 */
class cad2_Drawings extends embed_Manager {

    var $oldClassName = 'cad2_Shapes';
    
    /**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'cad2_ShapeIntf';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Clone, plg_Created, cad2_Wrapper';  
                    

    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Фигура";
    

    public $title = "Фигури";
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/draw_island.png';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,name,params=Параметри,createdOn,createdBy';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Кой може да го прочете?
     */
    public $canRead = 'cad,ceo,admin';
    
    
    /**
     * Кой може да променя?
     */
    public $canEdit = 'cad,ceo,admin';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'cad,ceo,admin';
    
        
    /**
     * Кой може да го разгледа?
     */
    public $canList = 'cad,ceo,admin';
    
    
    /**
     * Кой може да го отхвърли?
     */
    public $canReject = 'cad,ceo,admin';
    
    
    /**
     * Кой може да качва файлове
     */
    public $canWrite = 'cad,ceo,admin';
    
    
	
    /**
     * Нов темплейт за показване
     */
    //public $singleLayoutFile = 'cat/tpl/products/SingleProduct.shtml';
    
    
    /**
     * Кой има достъп до единичния изглед
     */
    public $canSingle = 'cad,ceo,admin';
    
	
   	
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, remember=info,width=100%');
    }

    function on_AfterRenderSingle($mvc, $tpl, $data)
    { 
        $driver = cls::get($data->rec->driverClass);
        $svg = $driver->getCanvas();
        $driver->render($svg, (array) $data->rec);
        $tpl->append('<div class="clearfix21"></div>');

        $tpl->append($svg->render());
    }


    /**
     * След подготвяне на формата за филтриране
     *
     * @param blast_EmailSend $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareListFilter($mvc, &$data)
    {
        // Подреждаме записите, като неизпратените да се по-нагоре
        $data->query->orderBy("createdOn", 'DESC');
    }


    static function on_AfterRead($mvc, $rec)
    {
        if(!$rec->name) {
            $rec->name = $mvc->getVerbal($rec, 'driverClass') . "({$rec->id})";
        }
    }


    function on_AfterPrepareSingleToolbar($mvc, $res, $data)
    {
        if(TRUE || $mvc->haveRightFor('update', $data->rec)) {
            $data->toolbar->addBtn('SVG', array($mvc, 'DownloadSvg', $data->rec->id), NULL, 'ef_icon=fileman/icons/svg.png');
        }
    }


    function act_DownloadSvg()
    {
        requireRole('ceo,admin,cad');
        $id = Request::get('id', 'int');
        $rec = self::fetch($id);
        $driver = cls::get($rec->driverClass);
        $svg = $driver->getCanvas();
        $driver->render($svg, (array) $rec);
        $fileName = trim(fileman_Files::normalizeFileName($rec->name), '_') . '';

    	header("Content-type: application/svg");
    	header("Content-Disposition: attachment; filename={$fileName}.svg");
    	header("Pragma: no-cache");
    	header("Expires: 0");

        echo $svg->render();
        shutdown();
    }




}