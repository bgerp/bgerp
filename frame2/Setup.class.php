<?php


/**
 * Как да е форматирана датата
 */
defIfNot('FRAME2_CLOSE_LAST_SEEN_BEFORE', 4 * dt::SECONDS_IN_MONTH);


/**
 * Как да е форматирана датата
 */
defIfNot('FRAME2_MAX_VERSION_HISTORY_COUNT', '10');


/**
 * class frame2_Setup
 *
 * Инсталиране/Деинсталиране на пакета frame2
 *
 *
 * @category  bgerp
 * @package   frame2
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class frame2_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * От кои други пакети зависи
     */
    public $depends = '';
    
    
    /**
     * Начален контролер на пакета за връзката в core_Packs
     */
    public $startCtr = 'frame2_Reports';
    
    
    /**
     * Начален екшън на пакета за връзката в core_Packs
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Динамични справки и отчети';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'frame2_Reports',
        'frame2_ReportVersions',
        'frame2_AllReports',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'report';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'FRAME2_CLOSE_LAST_SEEN_BEFORE' => array('time', 'caption=Затваряне на последно видяни справки преди->Време'),
        'FRAME2_MAX_VERSION_HISTORY_COUNT' => array('int', 'caption=Колко версии да се пазят на справките->Брой'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'frame2_CsvExport';


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        // Добавяме кофа за файловете от Метро
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('reports', 'Файлове за импортиране данни за справки', 'csv,xml,txt', '2MB', 'user', 'every_one');

        return $html;
    }
}
