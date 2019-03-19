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
class h18_CoreUsers extends core_Manager
{
    public $loadList = 'h18_Wrapper';
    /**
     * Заглавие
     */
    public $title = 'Потребители';
    
    function description()
    {
        $conf = core_Packs::getConfig('h18');

        $this->db = cls::get('core_Db',
            array(  'dbName' => $conf->H18_BGERP_DATABASE,
                'dbUser' => $conf->H18_BGERP_USER,
                'dbPass' => $conf->H18_BGERP_PASS,
                'dbHost' => $conf->H18_BGERP_HOST
            ));
        
        $this->dbTableName = 'core_users';
        
        $this->FLD('nick', 'nick(64, ci)', 'caption=Ник,notNull,mandatory,width=100%');
        $this->FLD('state', 'enum(active=Активен,draft=Непотвърден,blocked=Блокиран,closed=Затворен,rejected=Заличен)',
        'caption=Състояние,notNull,default=draft');
    
        $this->FLD('names', 'varchar', 'caption=Лице->Имена,mandatory,width=100%');
        $this->FLD('email', 'email(64, ci)', 'caption=Лице->Имейл,mandatory,width=100%');
        
        // Поле за съхраняване на хеша на паролата
        $this->FLD('ps5Enc', 'varchar(128)', 'caption=Парола хеш,column=none,input=none,crypt');
        
        
        $this->FLD('rolesInput', 'keylist(mvc=core_Roles,select=role,groupBy=type, autoOpenGroups=team|rang, orderBy=orderByRole)', 'caption=Роли');
        $this->FLD('roles', 'keylist(mvc=core_Roles,select=role,groupBy=type)', 'caption=Експандирани роли,input=none');
        
        
        $this->FLD('lastLoginTime', 'datetime(format=smartTime)', 'caption=Последно->Логване,input=none');
        $this->FLD('lastLoginIp', 'type_Ip', 'caption=Последно->IP,input=none');
        $this->FLD('lastActivityTime', 'datetime(format=smartTime)', 'caption=Последно->Активност,input=none');
}
    
}