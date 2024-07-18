<?php



/**
 * Пакет за клиентски ваучери
 *
 *
 * @category  bgerp
 * @package   voucher
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class voucher_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';


    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'voucher_Cards';


    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';


    /**
     * Описание на модула
     */
    public $info = 'Клиентски ваучери и препоръчители';


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'voucher_Types',
        'voucher_Cards',
    );


    /**
     * Роли за достъп до модула
     */
    public $roles = array('voucher');


    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.4, 'Търговия', 'Ваучери', 'voucher_Cards', 'default', 'ceo, voucher'),
    );


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        foreach (array('pos_Receipts', 'sales_Sales') as $cls){
            $Class = cls::get($cls);
            $Class->setupMvc();
        }

        return $html;
    }

    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData($itr = '')
    {
        $res = parent::loadSetupData($itr);
        $modified = $skipped = 0;
        $array = array('default-voucher' => array('title' => 'Стандартен ваучер', 'path' => 'voucher/tpl/DefaultVoucherLabel.shtml', 'lang' => 'bg', 'class' => 'voucher_Types', 'sizes' => array('85.5', '54')),);

        core_Users::forceSystemUser();
        foreach ($array as $sysId => $cArr) {
            label_Templates::addDefaultLabelsFromArray($sysId, $cArr, $modified, $skipped);
        }
        core_Users::cancelSystemUser();

        $class = ($modified > 0) ? ' class="green"' : '';
        $res .= "<li{$class}>Променени са са {$modified} шаблона за етикети, пропуснати са {$skipped}</li>";

        return $res;
    }

}
