<?php



/**
 * Клас 'plg_RemoveCache' - Премахва записите в кеша, свързани с редактивани или изтривани записи
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class plg_RemoveCache extends core_Plugin
{
    
    /**
     * Задава за премахване записите от кеша, които съответстват на $rec
     */
    private static function setToRemove($mvc, $rec)
    {
        $rem = $mvc->removeCache($rec);
        $key = implode('|', $rem);
        $mvc->removeCache[$key] = $rem;

    }


	/**
	 * Извиква се след успешен запис в модела
	 */
	public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = NULL, $mode = NULL)
	{
        self::setToRemove($mvc, $rec);
    }
    

    /**
     * Преди изтриване на запис
     */
    public static function on_BeforeDelete(core_Mvc $mvc, &$res, &$query, $cond)
    {
        $_query = clone($query);
        while ($rec = $_query->fetch($cond)) {
            self::setToRemove($mvc, $rec);
        }
    }

    
    /**
     * Обновява броя на използваните места на шътдаун
     */
    public static function on_Shutdown($mvc)
    {
        if(is_array($mvc->removeCache)) {
            foreach($mvc->removeCache as $rem) {
                core_Cache::remove($rem[0], $rem[1]);
            }
        }
    }
}