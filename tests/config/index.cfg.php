<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);

// Дали да са включени финкциите за дебъг и настройка
DEFINE('EF_DEBUG', TRUE);

// Името на папката със статичните ресурсни файлове:
// css, js, png, gif, jpg, flv, swf, java, xml, txt, html ...
// Тази папка се намира в webroot-a, заедно с този файл
// Самите статични файлове могат физически да не са в тази папка
 # DEFINE('EF_SBF', 'sbf');

// Общата коренна директория на приложенията, фреймуърка [ef],
// [conf], [vendors], [temp], [uploads] и др. Такава директория
// може и да не съществува, обаче ако няколко от горните директории 
// са на едно място, дефинирането на EF_ROOT_PATH е удобство
DEFINE( 'EF_ROOT_PATH', '/home/developer/projects/ef_root.git');

// Кода на фреймърка. По подразбиране е в
// EF_ROOT_PATH/ef
 # DEFINE( 'EF_EF_PATH', 'PATH_TO_FOLDER');

// Конфигурационите файлове. По подразбиране е в
// EF_ROOT_PATH/conf
 # DEFINE( 'EF_CONF_PATH', 'PATH_TO_FOLDER');

// Името на приложението. Допускат се само малки латински букви и цифри
// Ако не е дефинирано, системата се опитва да го открие сама
DEFINE('EF_APP_NAME', 'bgerp');
