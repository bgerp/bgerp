<?php



/**
 * Клас 'survey_Wrapper'
 *
 *
 * @category  bgerp
 * @package   survey
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class survey_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
       $this->TAB('survey_Surveys', 'Анкети', 'admin, survey');
       $this->TAB('survey_Alternatives', 'Отговори', 'admin, survey');
       $this->TAB('survey_Votes', 'Гласуване', 'admin, survey');
       $this->title = 'Анкети';
    }
}