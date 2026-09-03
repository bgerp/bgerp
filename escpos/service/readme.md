<h4>Приема HTTP GET заявка с данни и параметри за печат.</h4>
Ако не може да извлече данните от GET заявката ги взима от конфигурационни константи. <br \>
Ако искаме да печатаме на IP адрес, `$conf->DEVICE` трябва да е празно. <br \>
<br \>
Инсталиране:<br \>
Сваля се скрипта от github с командата:<br \>

`wget https://raw.githubusercontent.com/bgerp/bgerp/DC1/escpos/service/escpos.php`

Добавя се в крона на root потребителя:<br \>

`php -S localhost:8080 -t /home/user /home/user/escpos.php`
 
 <br \>

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

<br \>
<h4>Проверка на място</h4>
Всички команди се изпълняват на компютъра, на който е закачен принтерът - не на сървъра с bgERP.
<br \>
<br \>

1. Пуснат ли е сървисът и слуша ли на очаквания порт:

    pgrep -af "php -S"
    ss -ltnp | grep 8080

2. Отговаря ли. Нарочно се подават невалидни данни, за да не се печата:

    curl -i "http://localhost:8080/?DATA=xxx"

Очаква се `err: Непарсируеми или липсващи данни.` - значи сървисът върви и отговаря.
`curl: (7) Failed to connect` значи, че не е пуснат или слуша на друг порт.

3. Достъпен ли е принтерът:

    test -w /dev/usb/lp0 && echo "портът е достъпен за писане"
    nc -zv 11.0.0.77 9100

4. Най-точната проверка - от браузъра, от отворена страница на bgERP (F12 -> Console).
Само така се проверява и дали браузърът не блокира заявката:

    $.ajax({url: 'http://localhost:8080', data: {DATA: 'xxx'}, crossDomain: true, cache: false})
        .done(function(r){ console.log('OK ->', r); })
        .fail(function(x){ console.log('FAIL ->', x.status, x.statusText); });

`OK -> err: Непарсируеми или липсващи данни.` значи, че връзката е наред от край до край.
`FAIL -> 0 error` значи, че браузърът не е получил отговор - точната причина се вижда в таба Network.
<br \>
<br \>
Ако заявката се вижда в лога на сървиса, но браузърът дава статус `0`, проблемът е в блокирането от
браузъра - CORS, Private Network Access или mixed content. Ако заявката изобщо не стига до лога,
връзката не е установена - сървисът не е пуснат или адресът/портът е грешен.
<br \>
<br \>
Забележка: ако bgERP се отваря по `https`, а `serverUrl` е `http://` към IP адрес в мрежата,
браузърът блокира заявката като mixed content и това не може да се оправи с хедъри.
`http://localhost` не се блокира.
