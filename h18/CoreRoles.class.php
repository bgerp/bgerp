<?php



/**
 * Четене и записване на локални файлове
 *
 *
 * @category  bgerp
 * @package   H18
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Локален файлов архив
 */
class h18_CoreRoles extends core_Manager
{
    public $loadList = 'h18_Wrapper';
    /**
     * Заглавие
     */
    public $title = 'Роли';
    
    function description()
    {
        $conf = core_Packs::getConfig('h18');

        $this->db = cls::get('core_Db',
            array(  'dbName' => $conf->H18_BGERP_DATABASE,
                'dbUser' => $conf->H18_BGERP_USER,
                'dbPass' => $conf->H18_BGERP_PASS,
                'dbHost' => $conf->H18_BGERP_HOST
            ));
        
        $this->dbTableName = 'core_roles';
        
        $this->FLD('role', 'varchar(64)', 'caption=Роля,mandatory,translate');
        $this->FLD('inheritInput', 'keylist(mvc=core_Roles,select=role,groupBy=type,where=#type !\\= \\\'rang\\\' AND #type !\\= \\\'team\\\',orderBy=orderByRole)', 'caption=Наследяване,notNull,');
        $this->FLD('inherit', 'keylist(mvc=core_Roles,select=role,groupBy=type)', 'caption=Калкулирано наследяване,input=none,notNull');
        $this->FLD('type', 'enum(job=Модул,team=Екип,rang=Ранг,system=Системна,position=Длъжност,external=Външен достъп)', 'caption=Тип,notNull');
    }
    
}