<?php

/**
 * Информация за всички файлове във fileman_Files
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_Info1 extends core_Manager
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
    var $loadList = 'fileman_Wrapper, plg_RowTools, plg_Created';
    
    
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
        // Записи за текущия файл
        $data->fRec = fileman_Files::fetchByFh($fh);

        // Разширението на файла
        $ext = fileman_Files::getExt($data->fRec->name);
        
        // Вземаме уеб-драйверите за това файлово разширение
        $webdrvArr = self::getDriver($ext);

        // Обикаляме всички открити драйвери
        foreach($webdrvArr as $drv) {
            
            // Стартираме процеса за извличане на данни
            $drv->startProcessing($data->fRec);
            
            // Комбиниране всички открити табове
            $data->tabs = arr::combine($data->tabs, $drv->getTabs($data->fRec));
        }
    }
    
    
    /**
     * Рендира информацията за файла
     */
    static function render_($data)
    {
        // Масив с всички табове
        $tabsArr = $data->tabs;
        
        // Подреждаме масивити според order
        $tabsArr = static::orderTabs($tabsArr);
        
        // Текущия таб, ако не е зададен или ако няма такъв е първия
        $currentTab = $tabsArr[$data->currentTab] ? $data->currentTab : array_shift(array_keys($tabsArr));

        // Създаваме рендер на табове
        $tabs = CLS::get('core_Tabs');
        
        // Обикаляме всички табове
        foreach($data->tabs as $name => $rec) {
            
            // Ако е текущия таб таб
            if($name == $currentTab) {
                 $tabs->TAB($name, $rec->title, array());
                 
                 // Вземаме съдържанеито на тялот
                 $body = $rec->html;
            } else {
                
                // Създаваме таб
                $tabs->TAB($name, $rec->title, array('currentTab' => $name, 'id' => $data->rec->fileHnd));
            }
        }
        
        // Рендираме съдържанието на таба
        $tpl = $tabs->renderHtml($body, $currentTab);

        return $tpl;
    }
    

    /**
     * Връща масив от инстанции на уеб-драйвери за съответното разширение
     * Първоначалните уеб-драйвери на файловете се намират в директорията 'fileman_webdrv'
     */
    static function getDriver_($ext, $pathArr = array('fileman_webdrv'))
    {   
        // Разширението на файла
        $ext = strtolower($ext);

        // Масив с инстанциите на всички драйвери, които отговарят за съответното разширение
        $res = array();

        // Обхождаме масива с пътищата
        foreach($pathArr as $path) {
            
            // Към пътя добавяме разширението за да получим драйвера
            $className = $path . '_' . $ext;
            
            // Ако има такъв клас
            if(cls::load($className, TRUE)) {
                
                // Записваме инстанцията му
                $res[] = cls::get($className);
            }
        }

        // Ако не може да се намери нито един драйвер
        if(count($res) == 0) {
            
            // Създаваме инстанция на прародителя на драйверите
            $res[] = cls::get('fileman_webdrv_Generic');
        }

        // Връщаме масива
        return $res;
    }
    
    
    /**
     * Подреждане на табовете в зависимост от order
     */
    static function orderTabs($tabsArr)
    {
        // TODO
        return $tabsArr;
    }
 }