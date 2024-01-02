<h4>Приема HTTP GET заявка с параметър amount за сума към ПОС теминала</h4>
При неуспешна транзакция връща грешка <br \>
<br \>
Инсталиране:<br \>
Сваля се скрипта от github с командата:<br \>

`wget https://raw.githubusercontent.com/bgerp/bgerp/DC1/borica/service/pos_comm.php`

Добавя се в крона на root потребителя:

<br \>

`php -S localhost:8081 -t /home/user /home/user/pos_comm.php`
 
<br \>
 
Порта `COM1` или `COM2` трябва да е конфигуриран във файла на сървиса `pos_comm.php` по следния начин: `Boud Rate: 1200; Check: Even; Data Bit: 7; Stop Bit: 2; Flow Control: None`


или

`1200,Even,7,two`


<br \>



Примерен код за печатане на `php`:


    <?php
    $amount = '1.03';

    $DATA = urlencode(serialize($amount));
    
    
    echo file_get_contents("http://localhost:8081?DATA=$DATA");
    
    
    exit;
