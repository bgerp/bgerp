<?php

/**
 * Колко голям да бъде максималния обект, който се съхранява
 * в кеша не-компресиран?
 */
defIfNot('EF_CACHE_MAX_UNCOMPRESS', 10000);


/**
 * Максимален размер за полето на типа
 */
defIfNot('EF_CACHE_TYPE_SIZE', 16);


/**
 * Максимален размер за полето на манипулатора
 */
defIfNot('EF_CACHE_HANDLER_SIZE', 32);


/**
 * Клас 'core_Cache' - Кеширане на обекти, променливи или масиви за определено време
 *
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS:$Id:$
 * @link
 * @since      v 0.1
 */
class core_Cache extends core_Manager
{
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Кеширани обекти';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('type', 'identifier(' . EF_CACHE_TYPE_SIZE . ')', 'caption=Тип на обекта,notNull');
        $this->FLD('handler', 'varchar(' . EF_CACHE_HANDLER_SIZE . ')', 'caption=Манипулатор');
        $this->FLD('data', 'blob', 'caption=Данни');
        $this->FLD('isCompressed', 'enum(no,yes)', 'caption=Компресиране,acolumn=none,input1=none');
        $this->FLD('isSerialized', 'enum(no,yes)', 'caption=Сериализиране,acolumn=none,input1=none');
        $this->FLD('lifetime', 'int', 'caption=Живот,notNull'); // В секунди
        $this->load('plg_Created,plg_SystemWrapper,plg_RowTools');
        
        $this->setDbUnique('handler,type');
    }
    
    
    /**
     * Въща съдаржанието на кеша за посочения обект
     */
    function get($type, $handler)
    {
        $Cache = cls::get('core_Cache');
        
        $Cache->trimHandler($handler);
        $Cache->trimType($type);
        
        $rec = $Cache->fetch(array(
            "#type = '[#1#]' AND #handler = '[#2#]'",
            $type,
            $handler
        ));
        
        $data = $rec->data;
        
        if ($rec->isCompressed == 'yes') {
            $data = gzuncompress($data);
        }
        
        if ($rec->isSerialized == 'yes') {
            $data = unserialize($data);
        }
        
        return $data;
    }
    
    
    /**
     * Записва обект в кеша
     */
    function set($type, $handler, $data, $lifetime = 1, $timeMeasure = 'days')
    {
        $Cache = cls::get('core_Cache');
        
        $rec->type = $type;
        $rec->handler = $handler;
        $rec->data = $data;
        
        // Съкращаваме по-дългите идентификатори
        $Cache->trimType($rec->type);
        $Cache->trimHandler($rec->handler);
        
        if (is_object($rec->data) || is_array($rec->data) ) {
            $rec->data = serialize($rec->data);
            $rec->isSerialized = 'yes';
        } else {
            $rec->isSerialized = 'no';
        }
        
        if (!$handler) {
            $rec->handler = md5($rec->data);
        } else {
            $rec->handler = $handler;
        }
        
        $rec->id = $Cache->fetchField(array("#type = '[#1#]' AND #handler = '[#2#]'", $type, $rec->handler), 'id');
        
        // Ако имаме запис с този манипулатор и той е генериран на база данни, значи в кеша имаме точно този запис
        if($rec->id && !$handler) return $rec->handler;
        
        if (strlen($rec->data) > EF_CACHE_MAX_UNCOMPRESS ) {
            $rec->data = gzcompress($rec->data);
            $rec->isCompressed = 'yes';
        } else {
            $rec->isCompressed = 'no';
        }
        
        switch (strtolower(trim($timeMeasure))) {
            
            case 'day':
            case 'days':
                $rec->lifetime = 24 * 60 * 60 * $lifetime;
                break;
            
            case 'hour':
            case 'hours':
                $rec->lifetime = 60 * 60 * $lifetime;
                break;
            
            case 'minute':
            case 'minutes':
                $rec->lifetime = 60 * $lifetime;
                break;
            
            case 'second':
            case 'seconds':
                $rec->lifetime = $lifetime;
                break;
            
            default:
            expect(FALSE, "Непозната мярка за време|* \"{$timeMeasure}\"");
        }
        
        $Cache->save($rec);
        
        return $rec->handler;
    }
    
    
    /**
     * Изтрива обектите от указания тип(ове) (и манипулатор)
     */
    function remove($type, $handler = NULL)
    {
        $Cache = cls::get('core_Cache');
        
        if ($handler === NULL) {
            $type = arr::make($type);
            
            foreach ($type as $t) {
                $Cache->delete("#type = '{$t}'");
            }
        } else {
            $Cache->delete("#type = '{$type}' AND #handler = '{$handler}'");
        }
    }
    
    
    /**
     *  Извиква се след подготовката на toolbar-а за табличния изглед
     */
    function on_AfterPrepareListToolbar($mvc, $res, $data)
    {
        $data->toolbar->addBtn('Изтриване на изтеклите записи', array(
            $mvc,
            'DeleteExpiredData',
            'ret_url' => TRUE
        ));
        
        $data->toolbar->addBtn('Изтриване на всички записи', array(
            $mvc,
            'DeleteExpiredData',
            'all' => TRUE,
            'ret_url' => TRUE
        ));
        
        $data->toolbar->removeBtn('btnAdd');
        
        return $data;
    }
    
    
    /**
     * 'Ръчно' почистване на кеша
     */
    function act_DeleteExpiredData()
    {
        requireRole('admin');
        
        return $this->renderWrapping($this->cron_DeleteExpiredData(Request::get('all')));
    }
    
    
    /**
     * Почистване на обектите с изтекъл срок
     */
    function cron_DeleteExpiredData($all = FALSE)
    {
        if($all) {
            $where = '1 = 1';
        } else {
            $where = " DATE_ADD(#createdOn, interval #lifetime second) < '" . dt::verbal2mysql() . "'";
        }
        
        $deletedRecs = $this->delete($where);
        
        return "Log: <B>{$deletedRecs}</B> expired objects was deleted";
    }
    
    
    /**
     * Инсталация на MVC манипулатора
     */
    function on_AfterSetupMVC($mvc, &$res)
    {
        $res .= "<p><i>Нагласяне на Cron</i></p>";
        
        $rec->systemId = 'ClearCache';
        $rec->description = 'Почиства кеша';
        $rec->controller = "{$this->className}";
        $rec->action = 'DeleteExpiredData';
        $rec->period = 24 * 60;
        $rec->offset = 2 * 60;
        $rec->delay = 0;
        $rec->timeLimit = 200;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $res .= "<li><font color='green'>Задаване на Cron да почиства кеша</font></li>";
        } else {
            $res .= "<li>Отпреди Cron е бил нагласен да почиства кеша</li>";
        }
        
        return $res;
    }
    
    
    /**
     * Съкращава идентификатора на типа
     */
    function trimType(&$type)
    {
        $type = str::convertToFixedKey($type, EF_CACHE_TYPE_SIZE, 8);
    }
    
    
    /**
     * Съкращава манипулатора
     */
    function trimHandler(&$handler)
    {
        $handler = str::convertToFixedKey($handler, EF_CACHE_HANDLER_SIZE, 8);
    }
    
    
    /**
     * Подреждане - най-отгоре са последните записи
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $data->query->orderBy('#createdOn', 'DESC');
    }
}