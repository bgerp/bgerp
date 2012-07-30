<?php

/**
 * Информация за всички файлове във fileman_Files
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_Info extends core_Manager
{
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Информация за файловете";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'no_one';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'bgerp_Wrapper,plg_RowTools';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('dataId', 'key(mvc=fileman_Data)', 'caption=Данни на файл,notNull');
        $this->FLD('type', 'varchar(32)', 'caption=Тип');
        $this->FLD('content', 'blob(1000000)', 'caption=Съдържание');
        
        $this->setDbUnique('dataId,type');
    }
    
    
    /**
     * Подготвя данните за информацията за файла
     */
    static function prepare_(&$data, $fh)
    {
        $data->fRec = fileman_Files::getByFh($fh);

        // Разширението на файла
        $ext = self::getExt($data->rec->name);
        
        // Вземаме уеб-драйверите за това файлово разширение
        $webdrvArr = self::getDriver($ext);

        foreach($webdrvArr as $drv) {
            $data->tabs = arr::combine($data->tabs, $drv->getTabs($data->fRec));
        }
    }
    
    
    /**
     * Рендира информацията за файла
     */
    static function render_($data)
    {
        // В $data очакваме да имаме всички табове с данни
        // $data->tabs = assay()
        // $data->tabs[name]->title = ....
        // $data->tabs[name]->url   = ....
        // $data->tabs[name]->order   = ....
        // $data->currentTab   = ....

    }


    /**
     * Връща масив от инстанции на уеб-драйвери за съответното разширение
     * Първоначалните уеб-драйвери на файловете се намират в директорията 'fileman_webdrv'
     */
    static function getDriver_($ext, $pathArr = array('fileman_webdrv'))
    {   
        $ext = strtolower($ext);

        $res = array();

        foreach($pathArr as $path) {
            $className = $path . '_' . $ext;
            if(cls::load($className, TRUE) {
                $res[] = cls::get($className);
            }
        }

        if(count($res) == 0) {
            $res[] = 'fileman_webdrv_Generic';
        }

        return $res;
    }

    
 }