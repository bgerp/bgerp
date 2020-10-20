<?php

/**
 * class school_Wrapper
 *
 * Обвивка на пакета за организиране на обучения
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
class school_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('school_Classes', 'Групи', 'ceo, edu, teacher');
        $this->TAB('school_Courses', 'Курсове', 'edu,ceo, teacher');
        $this->TAB('school_Subjects', 'Дисциплини', 'edu,ceo, teacher');
        $this->TAB('school_Specialties', 'Настройки->Специалности', 'edu, ceo, teacher');
        $this->TAB('school_Stages', 'Настройки->Етапи', 'edu, ceo, teacher');
        $this->TAB('school_ReqDocuments', 'Настройки->Документи', 'edu, ceo, teacher');
        $this->TAB('school_Venues', 'Настройки->Зали', 'edu, ceo, teacher');
        $this->TAB('school_Formats', 'Настройки->Формати', 'edu, ceo, teacher');

        

        $this->title = 'Училище';
    }
}
