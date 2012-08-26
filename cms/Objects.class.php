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
class cms_Objects extends core_Master
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
     * Кой може да пише?
     */
    var $canWrite = 'cms,admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'cms,admin';
    

    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,tag,sourceClass,type,sourceId';

    
    var $singleFields = 'id,tag,sourceClass,type,sourceId,createdOn,createdBy,view=Изглед';


    /**
     * Описание на модела (таблицата)
     */
    function description()
    {   
        // Таг за показване на обекта
        $this->FLD('tag', 'varchar', 'caption=Tag,width=100%');

        // Мениджър-източник на обекта
        $this->FLD('sourceClass', 'class(interface=cms_ObjectSourceIntf)', 'caption=Източник,input=hidden,silent');

        // Име на изгледа, prepare{$name}, render{$name}
        $this->FLD('type', 'enum(group=Група,object=Обект)', 'caption=Тип,input=hidden,silent');

        // Параметри на обекта, $data
        $this->FLD('sourceId', 'int', 'caption=Ид,input=hidden,silent');

        // Шаблон на обекта
        $this->FLD('tpl', 'html', 'caption=Шаблон,width=100%');

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
        
        $objData = new stdClass();
        $objData->cmsObjectId = $rec->sourceId;
        $objData->cmsType  = $rec->type;
        
        if(!$rec->tpl) {
            $source->prepareCmsObject($objData);
             
            $rec->tpl = $source->getDefaultCmsTpl($objData)->content;
        }

        if(!$rec->id) {
            $query = self::getQuery();

            $query->where("#sourceClass = {$rec->sourceClass} AND #sourceId = {$rec->sourceId} AND #type = '{$rec->type}'");

            while($exRec = $query->fetch()) {
 
                if(!$data->form->info) {
                    $data->form->info = "<div style='background-color:#ffff99;border:solid 1px #ffcc66;padding:10px;margin-bottom:15px;'>Съществуващи публикации:<ul>";
                }

                $data->form->info .= '<li>' . $exRec->tag . '</li>';
            }

            if(!$data->form->info) {
                 $data->form->info .= '</div>';
            }

        }
    }


    /**
     *
     */
    function on_AfterRecToVerbal($mvc, $row, $rec, $fields = NULL)
    {
        $row->tag = "[obj={$rec->tag}]";

        if($fields['-single']) {
            $richText = cls::get('type_Richtext');

            $text = "[obj={$rec->tag}]";

            $row->view = $richText->toVerbal($text);
        }
    }

    
    function on_AfterPrepareSingleTitle($mvc, $data)
    {
        $data->title = $data->rec->tag;
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