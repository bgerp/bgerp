<h4>Приема HTTP GET заявка с данни и параметри за печат.</h4>
Ако не може да извлече данните от GET заявката ги взима от конфигурационни константи.
Ако искаме да печатаме на IP адрес, `$conf->DEVICE` трябва да е празно.

Инсталиране:

`sudo php -S localhost:8080 -t /home/user escpos.php`


Примерен код за печатане на `php`:


    <?php
    $conf = new stdclass();

    // $conf->DEVICE = "/dev/usb/lp0";

    $conf->DEVICE = "";
    $conf->IP_ADDRESS = "11.0.0.77";
    $conf->PORT = 9100;
    $conf->OUT = "\x1B\x69\x61\x00\x1B\x40\x1B\x69\x4C\x01\x1b\x28\43\x02\x00\xFC\x02\x1B\x24\xCB\x00\x1B\x28\x56\x02\x00\xCB\x00\x1B\x68\x0B\x1B\x58\x00\x64\x00\x41\x74\x20\x79\x6F\x75\x72\x20\x73\x69\x64\x65\x0C";
    
    $DATA = urlencode(gzcompress(serialize($conf)));
    
    
    echo file_get_contents("http://localhost:8080?DATA=$DATA");
    
    
    exit;
