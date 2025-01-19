#!/bin/bash

echo "Скриптът стартира."

# Проверка за административни права
if [ "$(id -u)" -ne 0 ]; then
    echo "Administrator rights required."
    exit 1
fi

# След проверката за административни права
echo "Проверка за административни права: OK."

# 1. Копиране на скрипта
TARGET_PATH="/usr/local/bin/bgerp"
TARGET_SCRIPT="$TARGET_PATH/agent.sh"


# Създаване на директорията, ако не съществува
if [ ! -d "$TARGET_PATH" ]; then
    mkdir -p "$TARGET_PATH"
fi

# Проверка дали скриптът трябва да се копира
if [ "$(readlink -f "$0")" != "$TARGET_SCRIPT" ] || [ ! -f "$TARGET_SCRIPT" ]; then
    cp "$0" "$TARGET_SCRIPT"
    # След копирането на скрипта
    echo "Скриптът е копиран в $TARGET_SCRIPT."

    # 2. Настройка на cron за изпълнение на всеки 5 минути
    CRON_JOB="*/5 * * * * root bash $TARGET_SCRIPT >/dev/null 2>&1"
    CRON_FILE="/etc/cron.d/system_monitoring_task"

    echo "$CRON_JOB" > "$CRON_FILE"
    chmod 644 "$CRON_FILE"
    
    # След настройката на cron
    echo "Cron задачата е настроена."

    exit 0
fi

# 3. Събиране на системна информация

# Уникален идентификатор на системата
UNIQUE_ID=$(cat /etc/machine-id)

# Име на компютъра
NAME=$(hostname)

# Информация за ОС
OS_VER=$(lsb_release -d | cut -f2-)

# Време на последно зареждане
LAST_BOOT_TIME=$(who -b | awk '{print $3 " " $4}')

# Локален IP адрес
OWN_IP=$(hostname -I | awk '{print $1}')

# MAC адрес
MAC_ADDR=$(ip link show | awk '/ether/ {print $2}' | head -n1)

# Публичен IP адрес
UP_IP=$(curl -s ifconfig.me)

# Отворени портове
OPEN_PORTS=$(netstat -tuln | awk '/LISTEN/ {print $4}' | awk -F: '{print $NF}' | sort -n | uniq | tr '\n' '|' | sed 's/|$//')

# Свободна памет в MB
FREE_MEM=$(free -m | awk '/Mem:/ {print $4}')
FREE_MEM=$((FREE_MEM / 10))  # Конвертиране в 10 MB

# A: Изчисляване на свободното място на root файловата система в MB
FREE_DISK_C=$(df -m / | awk 'NR==2 {print $4}')
FREE_DISK_C=$((FREE_DISK_C / 10))  # Конвертиране в 10 MB

# B: Изчисляване на свободното място на всички блокови устройства в MB
FREE_DISK_D=$(df -m | grep -E '^/dev/' | awk '{sum += $4} END {print sum}')
FREE_DISK_D=$((FREE_DISK_D / 10))  # Конвертиране в 10 MB

# Брой на процесите
PROCESSES_CNT=$(ps aux | wc -l)

# Топ 20 процеса по използване на CPU
TOP_PROCESSES=$(ps aux --sort=-%cpu | head -n21 | awk 'NR>1 {print $11}' | tr '\n' '|' | sed 's/|$//')

# Хеш стойност на файла /etc/hosts
if [ -f /etc/hosts ]; then
    HOSTS_HASH=$(md5sum /etc/hosts | awk '{print $1}')
else
    HOSTS_HASH="FileNotFound"
fi

# Брой на задачите в cron
READY_TASKS_COUNT=$(crontab -l 2>/dev/null | grep -v '^#' | wc -l)

# Данните, които са трансферирани по мрежата от последното стартиране
PREV_NET_STATS_FILE="$TARGET_PATH/prev_net_stats"

# Текущи мрежови статистики
CURRENT_NET_STATS=$(cat /proc/net/dev | tail -n +3 | awk '{print $1,$2,$10}')

# Зареждане на предишните стойности
if [ -f "$PREV_NET_STATS_FILE" ]; then
    PREV_NET_STATS=$(cat "$PREV_NET_STATS_FILE")
else
    PREV_NET_STATS="$CURRENT_NET_STATS"
    echo "$CURRENT_NET_STATS" > "$PREV_NET_STATS_FILE"
fi

# Изчисляване на разликата
INCOMING_DATA=0
OUTGOING_DATA=0

while read -r iface rx tx; do
    prev_line=$(echo "$PREV_NET_STATS" | grep "^$iface ")
    if [ -n "$prev_line" ]; then
        prev_rx=$(echo "$prev_line" | awk '{print $2}')
        prev_tx=$(echo "$prev_line" | awk '{print $3}')
        rx_diff=$((rx - prev_rx))
        tx_diff=$((tx - prev_tx))
        if [ "$rx_diff" -lt 0 ]; then rx_diff=$rx; fi
        if [ "$tx_diff" -lt 0 ]; then tx_diff=$tx; fi
        INCOMING_DATA=$((INCOMING_DATA + rx_diff))
        OUTGOING_DATA=$((OUTGOING_DATA + tx_diff))
    else
        INCOMING_DATA=$((INCOMING_DATA + rx))
        OUTGOING_DATA=$((OUTGOING_DATA + tx))
    fi
done <<< "$CURRENT_NET_STATS"

# Записване на текущите стойности за следващото изпълнение
echo "$CURRENT_NET_STATS" > "$PREV_NET_STATS_FILE"

# Конфигурируема информация
INCTANCE="[#INSTANCE#]"

# Подготовка на данните за POST заявката
BODY="instance=$INSTANCE&uniqueID=$UNIQUE_ID&name=$NAME&osVer=$OS_VER&lastBootTime=$LAST_BOOT_TIME&ownIp=$OWN_IP&macAddr=$MAC_ADDR&upIp=$UP_IP&openPorts=$OPEN_PORTS&freeMem=$FREE_MEM&freeDiskC=$FREE_DISK_C&freeDiskD=$FREE_DISK_D&processesCnt=$PROCESSES_CNT&topProcess=$TOP_PROCESSES&hostsHash=$HOSTS_HASH&readyTasksCount=$READY_TASKS_COUNT&incomingData=$INCOMING_DATA&outgoingData=$OUTGOING_DATA"

# Преди изпращане на POST заявката
echo "Изпращане на данни към сървъра..."

# 4. Изпращане на POST заявката
URI="[#MONITORING_END_POINT#]"
RESPONSE=$(curl -s -X POST -d "$BODY" "$URI")
echo "Отговор от сървъра: $RESPONSE"

# 5. Записване на съобщение в системния лог
logger -t ExpertaMonitor "Monitoring successfully completed."