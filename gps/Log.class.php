<?php 


/**
 * Дефинира име на папка в която ще се ползва за временна директория
 */
defIfNot('GPS_LOG_TEMP_DIR', EF_TEMP_PATH . "/gps");


/**
 * Съхранява хронологични данни от gps тракери
 *
 *
 * @category  vendors
 * @package   gps
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class gps_Log extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'gps';
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('trackerId', 'varchar(12)', 'caption=Тракер Id');
        $this->FLD('data', 'blob', 'caption=gps данни');
        $this->FLD('gpsTime', 'datetime', 'caption=gps време');
        $this->FLD('remoteIp', 'ip', 'caption=Tракер IP');
    }
        
    /**
     * Входна точка за взимане на данни по http заявка
     * Очаква масив с данните от тракера
     */
    public function act_getData()
    {   
        return $fh;
    }
    
    
}
