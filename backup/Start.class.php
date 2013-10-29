<?php

/**
 * Период на който се взима binlog-a - 60 мин
 */
defIfNot('BACKUP_BINLOG_PERIOD',  60);

/**
 * Период на който се прави пълен бекъп на базата 7 дена
 */
defIfNot('BACKUP_DBDUMP_PERIOD',  10080);


/**
 * Четене и записване на локални файлове
 *
 *
 * @category  bgerp
 * @package   backup
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Стартиране архив
 */
class backup_Start
{
    
    /**
     * Заглавие
     */
    var $title = 'Стартира архивиране';
    
    
    /**
     *
     * 
     * 
     */
    static function Start()
    {
        
        
    }    
    
    /**
     *
     *
     * 
     */
    static function cron_Start()
    {
        $self->Start();
    }
    
}