<?php



/**
 * Публични обекти
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_Objects extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Обекти, достъпни за публикуване";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_State2, plg_RowTools, plg_Printing, cms_Wrapper, plg_Sorting';


    /**
     * Полета, които ще се показват в листов изглед
     */
    // var $listFields = ' ';
    
     
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'cms,admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'cms,admin';


    /**
     * Описание на модела (таблицата)
     */
    function description()
    {   
        // Таг за показване на обекта
        $this->FLD('tag', 'varchar', 'caption=Tag');

        // Мениджър-източник на обекта
        $this->FLD('sourceClass', 'class(interface=cms_ObjectSourceIntf)', 'caption=Източник,input=hidden,silent');

        // Име на изгледа, prepare{$name}, render{$name}
        $this->FLD('type', 'enum(group=Група,object=Обект)', 'caption=Тип,input=hidden,silent');

        // Параметри на обекта, $data
        $this->FLD('sourceId', 'int', 'caption=Ид на източника,input=hidden,silent');

        // Шаблон на обекта
        $this->FLD('tpl', 'html', 'caption=Шаблон');

        $this->setDbUnique('tag');
         
        Request::setProtected('sourceClass,type,sourceId');
    }


    /**
     *
     */
    function on_AfterPrepareEditForm($mvc, &$data)
    {
        $rec = $data->form->rec;

        $source = cls::getInterface('cms_ObjectSourceIntf', $rec->sourceClass);
        
        $data = new stdClass();
        $data->cmsObjectId = $rec->sourceId;
        $data->cmsType  = $rec->type;
        
        if(!$rec->tpl) {
            $source->prepareCmsObject($data);
             
            $rec->tpl = $source->getDefaultCmsTpl($data)->content;
        }
    }


    /**
     *  Връща html съдържанието на обекта
     */
    static function getObjectByTag($tag)
    {
         $rec = self::fetch("#tag = '{$tag}'");
        
        if(!$rec) {

            return "[obj={$tag}]";
        }

         $data = new stdClass();
         $data->cmsObjectId = $rec->sourceId;
         $data->cmsType  = $rec->type;

         $source = cls::getInterface('cms_ObjectSourceIntf', $rec->sourceClass);

         $source->prepareCmsObject($data);

         $res = $source->renderCmsObject($data, new ET($rec->tpl));

         return $res;
    }
    
    
 }