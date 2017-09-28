<?php


/**
 * Помощен клас за обединяване на старите и новите отчети
 * 
 * @category  bgerp
 * @package   frame2
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class frame2_AllReports extends core_Master
{
    
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Справка и отчет';
   
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Справки и отчети";
    
    
    /**
     * Права за писане
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Права за писане
     */
    public $canExport = 'powerUser';
    
    
    /**
     * Права за писане
     */
    public $canRefresh = 'powerUser';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'no_one';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'no_one';
    
    
	/**
	 * Кой може да добавя?
	 */
	public $canAdd = 'powerUser';
	
	
	/**
	 * Детайла, на модела
	 */
	public $details = 'frame2_ReportVersions';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    public $singleIcon = 'img/16/report.png';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "1.6|Общи";
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Необходими плъгини
     */
    public $loadList = 'frame_Wrapper';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FNC('source', 'class(allowEmpty, select=title)', 'caption=Вид, allowempty, mandatory, input, refreshForm, silent');
        $this->FNC('folderId', 'key(mvc=doc_Folders)', 'input=hidden, silent');
        $this->FNC('threadId', 'key(mvc=doc_Threads)', 'input=hidden, silent');
        $this->FNC('originId', 'key(mvc=doc_Containers)', 'input=hidden, silent');
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената папка като начало на нишка
     * 
     * @param $folderId int - key(mvc=doc_Folders)
     * 
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
        
        return (boolean)(frame2_Reports::canAddToFolder($folderId) || frame_Reports::canAddToFolder($folderId));
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     *
     * @param int $threadId - key(mvc=doc_Threads)
     * 
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
        
        return (boolean)(frame2_Reports::canAddToThread($threadId) || frame_Reports::canAddToThread($threadId));
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    public static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        
        //Добавяме текст по подразбиране за титлата на формата
        if ($form->rec->folderId) {
            $fRec = doc_Folders::fetch($form->rec->folderId);
            $title = tr("справка");
            if(core_Users::getCurrent('id', FALSE)){
                list($t,) = explode('<div', doc_Folders::recToVerbal($fRec)->title);
                $title .= ' |в|* ' . $t;
            }
        }
        
        $rec = $form->rec;
        
        if($rec->threadId) {
            $form->title = 'Добавяне на|* ';
        } else {
            $form->title = 'Създаване на|* ';
        }
        
        if($rec->threadId) {
            $thRec = doc_Threads::fetch($form->rec->threadId);
            setIfNot($data->singleTitle, $mvc->singleTitle);
            
            if($thRec->firstContainerId != $form->rec->containerId) {
                $firstDoc = doc_Containers::getDocument($thRec->firstContainerId);
                $form->title = core_Detail::getEditTitle($firstDoc->getInstance(), $firstDoc->that, $data->singleTitle, $rec->id, NULL, 50);
                unset($title);
            }
        }
        
        $form->title .= $title;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $intf = array();
        
        $interfaces = $interfaces2 = array();
        if (frame2_Reports::haveRightFor('add', $data->form->rec)) {
            $interfaces2 = frame2_Reports::getAvailableDriverOptions();
        }
        
        if (frame_Reports::haveRightFor('add', $data->form->rec)) {
            $interfaces = core_Classes::getOptionsByInterface(cls::get('frame_Reports')->innerObjectInterface, 'title');
            
            foreach ((array)$interfaces as $id => $int){
                if(!cls::load($id, TRUE)) continue;
                
                $Driver = cls::get($id);
                
                // Ако има права за добавяне на поне 1 отчет
                if(!$Driver->canSelectInnerObject()){
                    
                    unset($interfaces[$id]);
                }
            }
        }
        
        $intf = (array) $interfaces2 + (array) $interfaces;
        
        if ($intf) {
            asort($intf);
            $intf = self::prepareOptFor($intf);
        }
        
        $data->form->setOptions('source', $intf);
    }
    
    
    /**
     *
     *
     * @param array $options
     *
     * @return array
     */
    protected static function prepareOptFor($options)
    {
        $newOptions = array();
        
        if (!$options) return $newOptions;
        
        foreach ($options as $index => $opt){
            
            if(!is_object($opt)) {
                
                // Ако в името на класа има '->' то приемаме, че стринга преди знака е името на групата
                $optArr = explode('»', $opt);
                
                // Ако стринга е разделен на точно две части (име на група и име на клас)
                if(count($optArr) == 2){
                    
                    $newOptions[$optArr[0]] = (object)array(
                            'title' => trim($optArr[0]),
                            'group' => TRUE,
                    );
                    $newOptions[$index] = trim($optArr[1]);
                } else {
                    $newOptions[$index] = $opt;
                }
            } else {
                $newOptions[$index] = $opt;
            }
        }
        
        return $newOptions;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted() || $form->cmd == 'refresh') {
            if ($form->rec->source) {
                
                $clsInst = cls::get($form->rec->source);
                
                $intfArr = arr::make($clsInst->interfaces, TRUE);
                
                if ($intfArr['frame2_ReportIntf']) {
                    $urlArr = array('frame2_Reports', 'add', 'driverClass' => $form->rec->source);
                } else {
                    $urlArr = array('frame_Reports', 'add', 'source' => $form->rec->source);
                }
                
                $retUrl = array($mvc, 'add', 'source' => $form->rec->source, 'ret_url' => $form->rec->ret_url);
                
                if ($form->rec->folderId) {
                    $urlArr['folderId'] = $form->rec->folderId;
                    $retUrl['folderId'] = $form->rec->folderId;
                }
                
                if ($form->rec->threadId) {
                    $urlArr['threadId'] = $form->rec->threadId;
                    $retUrl['threadId'] = $form->rec->threadId;
                }
                
                if ($form->rec->originId) {
                    $urlArr['originId'] = $form->rec->originId;
                    $retUrl['originId'] = $form->rec->originId;
                }
                
                
                $urlArr['ret_url'] = $retUrl;
                
                return redirect($urlArr);
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($action == 'add' && $requiredRoles != 'no_one') {
            if (!frame2_Reports::haveRightFor('add', $rec, $userId) && !frame_Reports::haveRightFor('add', $rec, $userId)) {
                
                $requiredRoles = 'no_one';
            }
        }
    }
}
