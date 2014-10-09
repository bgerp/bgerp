<?php
class schema_plg_Wrapper extends core_Plugin
{
    public static function on_AfterDescription($wrapper)
    {
        $wrapper->TAB('schema_Migrations', 'Миграции', 'admin');
    }
}