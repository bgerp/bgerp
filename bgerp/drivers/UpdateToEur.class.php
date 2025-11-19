<?php


/**
 * Информация за преминване към Евро
 *
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Информация за преминване към Евро
 */
class bgerp_drivers_UpdateToEur extends core_BaseClass
{


    /**
     * Максимален брой блокове, които да могат да се поакзват в портала
     */
    public $maxCnt = 1;


    /**
     * @var string
     */
    public $interfaces = 'bgerp_PortalBlockIntf';

    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
    }
    
    
    /**
     * Може ли вградения обект да се избере
     *
     * @param NULL|int $userId
     *
     * @return bool
     */
    public function canSelectDriver($userId = null)
    {

        return true;
    }
    
    
    /**
     * Подготвя данните
     *
     * @param stdClass $dRec
     * @param null|int $userId
     *
     * @return stdClass
     */
    public function prepare($dRec, $userId = null)
    {

        return (object) array();
    }
    
    
    /**
     * Рендира данните
     *
     * @param stdClass $data
     *
     * @return core_ET
     */
    public function render($data)
    {
        $expId = crm_Companies::fetchField("#name = 'Експерта ООД'");
        if ($expId && crm_Companies::haveRightFor('single', $expId)) {
            $eLink = crm_Companies::getLinkToSingle($expId, 'name');
        } else {
            $eLink = ht::createLink('Експерта ООД', 'http://experta.bg/Bg/Danni', false, array('target' => '_blank'));
        }

        $data->tpl = new ET('
                                <div class="clearfix21 portal" style="margin-bottom:25px;">
                                <div class="legend">Подготовка към EUR</div>
                                    <p style="color: #333; margin: 5px 0; line-height: 1.5em; text-indent: 20px; font-weight: bold;">Системата ви е подготвена за преминаване към евро, но са необходими допълнителни миграции. </p>
                                    <p style="color: #333; margin: 5px 0; line-height: 1.5em; text-indent: 20px; font-weight: bold;">Моля, свържете се с ' . $eLink . '!</p>
                                </div>
                              ');

        return $data->tpl;
    }
    
    
    /**
     * Връща заглавието за таба на съответния блок
     *
     * @param stdClass $dRec
     *
     * @return string
     */
    public function getBlockTabName($dRec)
    {
        return tr('Към Евро');
    }


    /**
     * Името на стойността за кеша
     *
     * @param integer $userId
     *
     * @return string
     */
    public function getCacheTypeName($userId = null)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }

        return 'Portal_ToEUR_' . $userId;
    }


    /**
     * Помощна функция за вземане на ключа за кеша
     *
     * @param stdClass $dRec
     * @param null|integer $userId
     *
     * @return string
     */
    public function getCacheKey($dRec, $userId = null)
    {

        return md5(dt::now(false) . '|' . $userId);
    }
}
