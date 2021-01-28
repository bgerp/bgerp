<?php


/**
 * Инсталиране/Деинсталиране на мениджъри свързани с support модула
 *
 * @category  bgerp
 * @package   support
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class support_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';


    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'support_Issues';


    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';


    /**
     * Описание на модула
     */
    public $info = 'Поддръжка на системи: сигнали и проследяването им';


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'support_Issues',
        'support_Systems',
        'support_IssueTypes',
        'support_Corrections',
        'support_Preventions',
        'support_Ratings',
        'support_Resolutions',
        'migrate::fixAssetResources2103'
    );


    /**
     * Роли за достъп до модула
     */
    public $roles = 'support';


    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(2.14, 'Обслужване', 'Поддръжка', 'support_Tasks', 'default', 'support, admin, ceo'),
    );


    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'support_TaskType';


    /**
     * Необходими пакети
     */
    public $depends = 'planning=0.1, cal=0.1';


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        //инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('Support', 'Прикачени файлове в поддръжка', null, '300 MB', 'powerUser', 'every_one');

        return $html;
    }


    /**
     * Мграция за поправка на assetResourceId полето
     */
    public function fixAssetResources2103()
    {
        $query = cal_Tasks::getQuery();

        $driverClassField = cls::get('cal_Tasks')->driverClassField;

        $query->where(array("#{$driverClassField} = '[#1#]'", support_TaskType::getClassId()));

        while ($rec = $query->fetch()) {
            $mustSave = false;
            if ($rec->driverRec->assetResourceId) {
                $rec->assetResourceId = $rec->driverRec->assetResourceId;

                $mustSave = true;
            } elseif ($rec->assetResourceId) {
                $mustSave = true;
            }

            if ($mustSave) {
                cal_Tasks::save($rec, 'assetResourceId');
            }
        }
    }
}
