<?php


/**
 * Инсталиране на агенти за наблюдение на IT устройствата
 *
 *
 * @category  bgerp
 * @package   itis
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 */
class itis_Deployments extends core_Manager
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, itis_Wrapper, plg_Sorting, plg_Created, plg_Rejected';
    
    
    /**
     * Заглавие
     */
    public $title = 'Агенти за наблюдения';
    
    public $singleTitle = 'Агент';

    /**
     * Права за запис
     */
    public $canAdd = 'itis,admin';
    
    /**
     * Права за запис
     */
    public $canReject = 'itis,admin';

    /**
     * Права за запис
     */
    public $canDelete = 'no_one';

    /**
     * Права за запис
     */
    public $canEdit = 'no_one';

    /**
     * Права за четене
     */
    public $canRead = 'itis,admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin,itis';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'admin,itis';

    /**
     * Кой може да сваля агенти?
     */
    public $canDownload = 'admin,itis';
    
 
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Наименование,mandatory');
        $this->FLD('goups', 'keylist(mvc=itis_Groups,select=name)', 'caption=Групи');
        $this->FLD('os', 'enum(,linux=Linux,windows=Windows)', 'caption=ОС,mandatory');
        $this->FLD('download', 'datetime(format=smartTime)', 'caption=Сваляне,input=none');

        $this->setDbUnique('name');
    }

    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $form = $data->form;
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        if (stripos($user_agent, 'Windows') !== false) {
            $form->setDefault('os', 'windows');
        } elseif (stripos($user_agent, 'Linux') !== false) {
            $form->setDefault('os', 'linux');
        }
    }


   /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if(!isset($rec->download) && $mvc->haveRightFor('download', $rec)) {
            $row->download = ht::createBtn('Download', array($mvc, 'download', $rec->id));
        }
    }

    


    public function act_Download()
    {
        $id = Request::get('id', 'int');
        $rec = $this->fetch($id);

        if(!$rec) {
            return new Redirect(array($this), 'Липсващ запис');
        }

        $this->requireRightFor('download', $rec);
        
        $file = ($rec->os == 'windows') ? 'agent.ps1' : 'agent.sh';

        $agent = getFileContent('itis/agent/' . $file);
        
        $from = array('[#INSTANCE#]', '[#MONITORING_END_POINT#]');

        $to = array(str::addHash($rec->id, 8, 'AGENT'), toUrl(array('itis_Devices', 'Log'), 'absolute'));

        $agent = str_replace($from, $to, $agent);

        $mimeType = ($rec->os == 'windows') ? 'application/octet-stream' : 'text/x-sh';

        // Заглавки за изтегляне
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="bgERP-' . $file . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($agent));

        // Прочитане и изтегляне на файла
        echo $agent;
        exit;
    }

    


}
