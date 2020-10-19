<?php

/**
 * class school_Setup
 *
 * Инсталиране/Деинсталиране на пакета за организиране на обучения
 *
 *
 * @category  bgerp
 * @package   edu
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class school_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'school_Classes';
    
    
    /**
     * Екшън - входна точка в пакета.
     */
    public $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    // public $depends = '';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Организиране на обучения, курсове и квалификации. За образователни институции: школи, училища, университети...';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'school_Formats',
        'school_Venues',
        'school_ReqDocuments',
        'school_Specialties',
        'school_Stages',
        'school_Subjects',
        'school_SubjectDetails',
        'school_Courses',
        'school_CourseDetails',
        'school_Classes',
        'school_ClassStudents',
        'school_ClassSchedules',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'edu,teacher, student';
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'school_ClassDriver';


    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(2.39, 'Обслужване', 'Училище', 'school_Classes', 'default', 'edu, ceo, teacher'),
    );
}
