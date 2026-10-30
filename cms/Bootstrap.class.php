<?php


/**
 * Зарежда избраната Bootstrap версия за CMS темите.
 *
 * @category  bgerp
 * @package   cms
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_Bootstrap
{
    /**
     * Добавя CSS и JavaScript; новият bundle включва и Popper.
     *
     * @param core_ET $tpl
     */
    public static function enable($tpl)
    {
        if (cms_Setup::get('BOOTSTRAP_VERSION') === '5.3.8') {
            $tpl->push('cms/bootstrap/5.3.8/css/bootstrap.min.css', 'CSS');
            $tpl->push('cms/bootstrap/5.3.8/js/bootstrap.bundle.min.js', 'JS');
        } else {
            $tpl->push('cms/css/bootstrap.css', 'CSS');
            $tpl->push('cms/js/bootstrap.min.js', 'JS');
        }
    }
}
