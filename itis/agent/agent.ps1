# Проверка за администраторски права
If (-Not ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Error "Administrator right required."
    Exit
}

# 1. Копиране на скрипта
$targetPath = "C:\Program Files\BgERP"
$targetScript = Join-Path $targetPath "bgERP-agent.ps1"
 
# Проверка за съществуване на директорията и създаване, ако липсва
if (-Not (Test-Path $targetPath)) {
    New-Item -ItemType Directory -Path $targetPath -Force | Out-Null
}

# Проверка дали скриптът трябва да се копира
if ((-Not ($PSCommandPath -ieq $targetScript)) -or (-Not (Test-Path $targetScript))) {
    Copy-Item -Path $PSCommandPath -Destination $targetScript -Force
    
    # 2. Настройка на Scheduler (настройка за фонов режим)
    $taskName = "SystemMonitoringTask"
 
    # Създаване на тригер за задачата
    $trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) -RepetitionInterval (New-TimeSpan -Minutes 5)
 
    # Създаване на действие за задачата
    $action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$targetScript`""
 
    # Настройки на задачата 
    $settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -DontStopOnIdleEnd -Hidden -Priority 7 -WakeToRun:$false
 
    # Потребителски настройки за стартиране без логнат потребител
    $principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount
 
    # Премахване на старата задача (ако съществува)
    try {
        if (Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue) {
    	   # Спиране на задачата, ако се изпълнява
    	   Stop-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
    	   Start-Sleep -Seconds 2  # Кратка пауза, за да се уверим, че задачата е спряна
 
    	   # Премахване на задачата
    	   Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
    	   Write-Output "Old task '$taskName' was removed succesfuly."
        }
    } catch {
        Write-Error "Unsuccesful removing of existing task: $_"
        Exit
    }
 
    # Създаване на задачата
    Register-ScheduledTask -TaskName $taskName -Trigger $trigger -Action $action -Settings $settings -Principal $principal -Description "System monitoring task"
}


# 3. Изпращане на POST заявка
$uri = "[#MONITORING_END_POINT#]"

# Функция за извличане на System GUID
function Get-SystemGUID {
    try {
        # Извличане на GUID чрез WMI
        $guid = (Get-CimInstance -ClassName Win32_ComputerSystemProduct).UUID
        return $guid
    } catch {
        Write-ToEventLog -Message "Unsuccesful retriving of System GUID." -EntryType "Error"
        return $null
    }
}

# Функция за запис в Event Log
function Write-ToEventLog {
    param (
        [string]$Message,
        [string]$Source = "ExpertaMonitor",
        [string]$LogName = "Application",
        [string]$EntryType = "Information"
    )

    # Проверка и регистрация на източника, ако не съществува
    if (-Not (Get-EventLog -LogName $LogName -Source $Source -ErrorAction SilentlyContinue)) {
        New-EventLog -LogName $LogName -Source $Source -ErrorAction SilentlyContinue
    }

    # Записване на съобщението в Event Log
    Write-EventLog -LogName $LogName -Source $Source -EntryType $EntryType -EventId 1000 -Message $Message
}

# Събиране на информация
$name = $env:COMPUTERNAME

# Конфигурируема информация
$instance = "[#INSTANCE#]"

# Събиране на информация за ОС
$osInfo = Get-ComputerInfo -Property OsName, OsArchitecture, WindowsVersion, WindowsBuildLabEx
$lastUpdate = (Get-HotFix | Sort-Object InstalledOn -Descending | Select-Object -First 1).InstalledOn
$installDate = (Get-CimInstance Win32_OperatingSystem).InstallDate
$osVer = "$($osInfo.OsName), $($osInfo.OsArchitecture), Version: $($osInfo.WindowsVersion), Build: $($osInfo.WindowsBuildLabEx), Last Update: $lastUpdate, Installed On: $installDate"
$lastBootTime = (Get-CimInstance -ClassName Win32_OperatingSystem).LastBootUpTime
$lastBootTimeFormatted = $lastBootTime.ToString("yyyy-MM-dd HH:mm:ss")
$ownIp = (Get-NetIPAddress -AddressFamily IPv4 -InterfaceAlias Ethernet).IPAddress
$macAddr = (Get-NetAdapter | Where-Object {$_.Status -eq "Up"} | Select-Object -First 1).MacAddress
$upIp = (Test-Connection -ComputerName google.com -Count 1).IPv4Address.IPAddressToString
$openPorts = (Get-NetTCPConnection | Where-Object {
    $_.State -eq 'Established' -and $_.RemotePort -ne $null -and $_.RemoteAddress -notlike '127.*' -and $_.RemoteAddress -notlike '::1'
} | Select-Object -ExpandProperty RemotePort | Sort-Object | Select-Object -Unique) -join "|"
$freeMem = [math]::Floor((Get-CimInstance -ClassName Win32_OperatingSystem).FreePhysicalMemory / 1024 / 10) # Свободна памет в кратни на 10MB
$freeDiskC = [math]::Floor((Get-PSDrive -Name C).Free / 1MB / 10) # Свободно дисково пространство C: в кратни на 10MB
$freeDiskD = If (Test-Path D:\) { [math]::Floor((Get-PSDrive -Name D).Free / 1MB / 10) } Else { "N/A" } # Свободно дисково пространство D:
$processesCnt = (Get-Process).Count
# Извличане на 20-те най-активни процеса по CPU и запис в променлива
$topProcesses = (Get-Process |
    Sort-Object -Property CPU -Descending |
    Select-Object -Property Name -Unique |
    Select-Object -First 20 -ExpandProperty Name) -join "|"

# Извличане на уникалния идентификатор
$uniqueID = Get-SystemGUID

# 4. Хеш стойността на файла hosts (използваме MD5)
$hostsPath = "$env:SystemRoot\System32\drivers\etc\hosts"
if (Test-Path $hostsPath) {
    $hostsHash = (Get-FileHash -Path $hostsPath -Algorithm MD5).Hash
} else {
    $hostsHash = "FileNotFound"
}

# 5. Броя на задачите, които изпълнява Task Scheduler
$readyTasksCount = (Get-ScheduledTask | Where-Object { $_.State -eq 'Ready' }).Count

# 6. Данните, които са трансферирани по мрежата от последното стартиране
# Файл за съхранение на предишните стойности
$prevNetStatsFile = Join-Path $targetPath "prev_net_stats.json"

# Текущи мрежови статистики
$currentNetStats = Get-NetAdapterStatistics | Select-Object Name, ReceivedBytes, SentBytes

# Зареждане на предишните стойности
if (Test-Path $prevNetStatsFile) {
    $prevNetStats = Get-Content $prevNetStatsFile | ConvertFrom-Json
} else {
    # Ако няма предишни стойности, инициализираме с текущите стойности
    $prevNetStats = $currentNetStats
    $prevNetStats | ConvertTo-Json | Set-Content -Path $prevNetStatsFile
}

# Изчисляване на разликата
$incomingData = 0
$outgoingData = 0

foreach ($adapter in $currentNetStats) {
    $prevAdapter = $prevNetStats | Where-Object { $_.Name -eq $adapter.Name }
    if ($prevAdapter) {
        $receivedDiff = $adapter.ReceivedBytes - $prevAdapter.ReceivedBytes
        $sentDiff = $adapter.SentBytes - $prevAdapter.SentBytes
        # Проверка за отрицателни стойности (при рестартиране на броячите)
        if ($receivedDiff -lt 0) { $receivedDiff = $adapter.ReceivedBytes }
        if ($sentDiff -lt 0) { $sentDiff = $adapter.SentBytes }
        $incomingData += $receivedDiff
        $outgoingData += $sentDiff
    } else {
        # Ако няма предишни данни за адаптера, използваме текущите стойности
        $incomingData += $adapter.ReceivedBytes
        $outgoingData += $adapter.SentBytes
    }
}

# Записване на текущите стойности за следващото изпълнение
$currentNetStats | ConvertTo-Json | Set-Content -Path $prevNetStatsFile

# Конвертиране на данните в MB
# $incomingDataMB = [math]::Round($incomingData / 1MB, 2)
# $outgoingDataMB = [math]::Round($outgoingData / 1MB, 2)

# Данни за POST
$body = @{
    uniqueID = $uniqueID 
    name = $name
    osVer = $osVer
    lastBootTime = $lastBootTimeFormatted
    ownIp = $ownIp
    macAddr = $macAddr
    upIp = $upIp
    openPorts = $openPorts
    freeMem = [math]::Round($freeMem, 2)
    freeDiskC = [math]::Round($freeDiskC, 2)
    freeDiskD = $freeDiskD
    processesCnt = $processesCnt
    topProcess = $topProcesses
    hostsHash = $hostsHash
    readyTasksCount = $readyTasksCount
    incomingData = $incomingData
    outgoingData = $outgoingData
    instance = $instance
}

# Изпращане на заявката
$responce = Invoke-RestMethod -Uri $uri -Method Post -Body $body -ContentType "application/x-www-form-urlencoded"
Write-Output "Response: $response"

# Край
Write-ToEventLog -Message "Monitoring is succesfuly completed." -EntryType "Information"
