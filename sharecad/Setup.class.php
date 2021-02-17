<?php


/**
 *
 *
 * @category  bgerp
 * @package   sharecad
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sharecad_Setup extends core_ProtoSetup
{


    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Разглеждане на DWG файлове';


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        $html .= core_Plugins::installPlugin('Разглеждане на DWG файлове', 'sharecad_plugins_DWG', 'fileman_webdrv_Dwg', 'private');

        return $html;
    }
}
