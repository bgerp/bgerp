<?php


/**
 * Добавки от код към публичната страница
 *
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cms_Includes extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Добавки към публичната страница';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Добавка към публична статия';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_Modified, plg_State2, plg_RowTools2, plg_Printing, cms_Wrapper';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('domainId', 'key(mvc=cms_Domains, select=titleExt,allowEmpty)', 'caption=Домейн,mandatory,autoFilter');
        $this->FLD('place', 'varchar(32)', 'caption=Място,mandatory,suggestions=HTTP_HEADER|HEAD|JS|CSS|META_DESCRIPTION|META_KEYWORDS|STYLES|PAGE_CONTENT|SCRIPTS');
        $this->FLD('mode', 'enum(append, prepend, replace, push)', 'caption=Метод');
        $this->FLD('code', 'text', 'caption=Код,mandatory,width=100%');
    }
    
    
    /**
     * Добавя кодовете в посочения шаблон
     */
    public static function insert($tpl)
    {
        $domainId = cms_Domains::getPublicDomain('id');

        $query = self::getQuery();
        $query->where("#state = 'active'");

        if($domainId == 1) {
            $query->where("#domainId = 1 OR #domainId IS NULL");
        } else {
            $query->where("#domainId = {$domainId}");
        }

        while ($rec = $query->fetch()) {
            $rec->code = "\n" . $rec->code;
            switch ($rec->mode) {
                case 'append':
                    $tpl->append($rec->code, $rec->place);
                    break;
                case 'prepend':
                    $tpl->prepend($rec->code, $rec->place);
                    break;
                case 'replace':
                    $tpl->replace($rec->code, $rec->place);
                    break;
                case 'push':
                    $tpl->push($rec->code, $rec->place);
                    break;
            }
        }
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
     //   $data->form->setOptions('domainId', cms_Domains::getDomainOptions(true));
    }
    
}
