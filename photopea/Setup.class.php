<?php


/**
 * Редактиране на изображения с Photopea.
 *
 * @category  vendors
 * @package   photopea
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
class photopea_Setup extends core_ProtoSetup
{
    public $version = '0.1';

    public $info = 'Редактиране на изображения с Photopea';


    public function install()
    {
        $html = parent::install();
        $html .= core_Plugins::installPlugin('Редактиране с Photopea', 'photopea_Plugin', 'fileman_Files', 'private');

        return $html;
    }
}
