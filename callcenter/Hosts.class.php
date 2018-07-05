<?php 


/**
 * Модул за хостове за връзка с астерикс
 *
 * @category  bgerp
 * @package   callcenter
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class callcenter_Hosts extends core_Master
{
    
    
    /**
     * Името на кофата за файловете
     */
    public static $bucket = 'archiveTalks';
    
    
    /**
     * Заглавие на модела
     */
    public $title = 'Хостове';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Хост';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'admin';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'admin';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Кой може да разглежда сингъл изгледа?
     */
    public $canSingle = 'admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой има право да го оттегля?
     */
    public $canReject = 'admin';
    
    
    /**
     * Кой има право да го използва?
     */
    public $canUse = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'callcenter_Wrapper, plg_RowTools2, plg_Created, plg_Modified, plg_Rejected';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar', 'caption=Име, mandatory');
        $this->FLD('hostId', 'key(mvc=ssh_Hosts, select=name)', 'caption=Хост,mandatory');
        $this->FLD('path', 'varchar', 'caption=Път, mandatory');
        $this->FLD('template', 'varchar', 'caption=Шаблон, mandatory, title=Шаблон за име на файл');
    }
    
    
    /**
     * Архивира обаждането и връща манипулатора на файла
     *
     * @param array $placeArr
     *
     * @return FALSE|string
     */
    public static function archiveTalk($id, $placeArr)
    {
        if (!$id) {
            return false;
        }
        
        if (!($rec = self::fetch((int) $id))) {
            return false;
        }
        
        if (!$rec->hostId) {
            return false;
        }
        
        // Свързаме се към отдалечения хост
        try {
            $ssh = new ssh_Actions($rec->hostId);
        } catch (ErrorException $e) {
            self::logWarning($e->getMessage(), $rec->id);
            
            return false;
        }
        
        $fPath = self::prepareFileName($rec, $placeArr);
        
        // Вземаме съдъжанието
        try {
            $content = $ssh->getContents($fPath);
        } catch (Exception $e) {
            self::logWarning($e->getMessage(), $rec->id);
            
            return false;
        }
        
        if (!trim($content)) {
            self::logWarning('Файлът няма съдържание', $rec->id);
            
            return false;
        }
        
        $fName = pathinfo($fPath, PATHINFO_BASENAME);
        
        $fileHnd = fileman::absorbStr($content, self::$bucket, $fName);
        
        if (!$fileHnd) {
            return false;
        }
        
        return $fileHnd;
    }
    
    
    /**
     * Подготвя пътя до файла, като замества плейсхолдерите
     *
     * @param stdClass $rec
     * @param array    $placeArr
     *
     * @return string
     */
    protected static function prepareFileName($rec, $placeArr)
    {
        $template = $rec->template;
        
        foreach ($placeArr as $place => $val) {
            $place = '[#' . $place . '#]';
            $template = str_ireplace($place, $val, $template);
        }
        
        $path = rtrim($rec->path, '/');
        $path .= '/' . $template;
        
        return $path;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $data->form->setDefault('template', '[#uniqId#].mp3');
    }
    
    
    /**
     *
     *
     * @param callcenter_Hosts $mvc
     * @param string           $requiredRoles
     * @param string           $action
     * @param NULL|stdClass    $rec
     * @param NULL|integer     $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'use') {
            if (!$rec) {
                $requiredRoles = 'no_one';
            }
            
            if (!$rec->state == 'rejected') {
                $requiredRoles = 'no_one';
            }
            
            if (!$rec->hostId) {
                $requiredRoles = 'no_one';
            }
            
            if ($requiredRoles != 'no_one') {
                try {
                    $sshHost = ssh_Hosts::fetchConfig((int) $rec->hostId);
                } catch (ErrorException $e) {
                    $sshHost = false;
                }
                
                if (!$sshHost) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     *
     * @param callcenter_Hosts $mvc
     * @param string           $res
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        //Създаваме, кофа, където ще държим всички прикачени файлове
        $res .= fileman_Buckets::createBucket(self::$bucket, 'Архивирани обаждания', null, '300 MB', 'user', 'user');
    }
}
