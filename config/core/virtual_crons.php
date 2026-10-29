<?php
/**
 * Виртуальные кроны
 *
 * Запускаются при заходе админа в админку. Не требуют настройки crontab.
 */

if (!defined('APP_ROOT')) {
    die('Доступ запрещен');
}

/**
 * Запускает виртуальные кроны (бэкап и публикацию).
 * Вызывается из config/index.php после авторизации.
 *
 * @return void
 */
function run_virtual_crons(): void {
    // --- ВИРТУАЛЬНЫЙ КРОН БЭКАПА ---
    $cronSettings = getBackupSettingsData();
    
    if (!empty($cronSettings['cron_virtual'])) {
        $lastCronBackup = intval($cronSettings['last_backup_time'] ?? 0);
        $cronPeriodHours = intval($cronSettings['cron_period'] ?? 24);

        if (time() >= ($lastCronBackup + ($cronPeriodHours * 3600))) {
            $cronUrl = getBaseUrl() . dirname($_SERVER['SCRIPT_NAME']) . '/cron/backup.php?token=' . urlencode(getCronToken());

            $cronContext = stream_context_create([
                'http' => [
                    'timeout' => 1.0, 
                    'ignore_errors' => true
                ]
            ]);
            @file_get_contents($cronUrl, false, $cronContext);
        }
    }
    
    // --- ВИРТУАЛЬНЫЙ КРОН ПУБЛИКАЦИИ ---
    $lastPublish = getLastCronPublishTime();
    $publishInterval = getCronPublishMinInterval();

    if (time() >= ($lastPublish + $publishInterval)) {
        setLastCronPublishTime(time());

        $publishUrl = getBaseUrl() . dirname($_SERVER['SCRIPT_NAME']) . '/cron/publish.php?token=' . urlencode(getCronToken());

        $publishContext = stream_context_create([
            'http' => [
                'timeout' => 1.0,
                'ignore_errors' => true
            ]
        ]);
        @file_get_contents($publishUrl, false, $publishContext);
    }
}