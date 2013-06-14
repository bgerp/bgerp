#!/bin/bash

ipaddress=`ifconfig | grep inet | awk 'NR==1 {print $2}' | awk 'BEGIN { FS=":" } { print $2 }'`

rm /etc/issue
echo  '[0;36m' > /etc/issue
cat /etc/issue.ascii >> /etc/issue
echo "[0m"  >> /etc/issue
echo '[0;36m'  >> /etc/issue
echo "[1m*** Добре дошли във виртуалната машина на bgERP 2.0 ***"  >> /etc/issue
echo "[1m*** Използва Ubuntu 12.04 - Kernel \r (\l). ***[0m">> /etc/issue


if [ -f "/etc/init.d/networking" ]; then
    if [ "$ipaddress" = "" ] || [ "$ipaddress" = "127.0.0.1" ]; then
        /etc/init.d/networking force-reload
        ipaddress=`ifconfig | grep inet | awk 'NR==1 {print $2}' | awk 'BEGIN { FS=":" } { print $2 }'`
    fi
fi

echo '[1;33m' >> /etc/issue

if [ "$ipaddress" != "" ] && [ "$ipaddress" != "127.0.0.1" ]; then
  echo "[1m*** Приложението е достъпно на адрес: http://$ipaddress                        ***"  >> /etc/issue
  echo "[1m*** За повече детайли http://bgerp.com/cms_Articles/Article/Virtualna-mashina/ ***[0m"  >> /etc/issue
  echo "" >> /etc/issue
else
  echo "[1m*** Машината не може да конфигурира мрежовия интерфейс.                              ***"  >> /etc/issue
  echo "[1m*** Повече детайли на visit http://bgerp.com/cms_Articles/Article/Virtualna-mashina/ ***[0m"  >> /etc/issue
  echo "" >> /etc/issue
fi

if [ -f "/root/change-password.sh" ]; then
  echo '[1;31m' >> /etc/issue
  echo "******************************************************************************" >> /etc/issue
  echo "*  За вход в конзолата използвайте потребителско име 'root' и парола 'root'  *" >> /etc/issue
  echo "*                                                                            *" >> /etc/issue
  echo "*                                 ВНИМАНИЕ                                   *" >> /etc/issue
  echo "*  От съображения за сигурност, при първото влизане ще ви се поиска да       *" >> /etc/issue 
  echo "*  смените паролата.                                                         *" >> /etc/issue 
  echo "******************************************************************************" >> /etc/issue
  echo '[0m' >> /etc/issue
fi
