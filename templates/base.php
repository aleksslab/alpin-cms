<?php
/**
 * Базовый шаблон
 * Универсальный рендеринг для всех типов шаблонов
 */

$allZones = $template['zones'] ?? ['main' => ['class' => '']];
$templateId = $template['id'] ?? 'full-width';

$showHeader = $page['show_header'] ?? true;
$showFooter = $page['show_footer'] ?? true;

$hasCustomHeader = false;
$hasCustomFooter = false;
foreach ($page['rows'] as $row) {
    if (($row['zone'] ?? '') === 'header') {
        $hasCustomHeader = true;
    }
    if (($row['zone'] ?? '') === 'footer') {
        $hasCustomFooter = true;
    }
}

// Определяем зоны с контентом
$zonesWithContent = [];
foreach ($allZones as $zoneName => $zoneConfig) {
    $zoneRows = array_filter($page['rows'], function($row) use ($zoneName) {
        return ($row['zone'] ?? 'main') === $zoneName;
    });
    if (!empty($zoneRows)) {
        $zonesWithContent[$zoneName] = $zoneRows;
    }
}

// Проверяем, есть ли контент в сайдбарах
$hasSidebarLeft = isset($zonesWithContent['sidebar-left']) && !empty($zonesWithContent['sidebar-left']);
$hasSidebarRight = isset($zonesWithContent['sidebar-right']) && !empty($zonesWithContent['sidebar-right']);
$hasMain = isset($zonesWithContent['main']) && !empty($zonesWithContent['main']);
$hasSidebar = $hasSidebarLeft || $hasSidebarRight;

if ($showHeader) {
    $headerVariant = $settings['header_variant'] ?? 'default';    
    $headerFixed = $settings['header_fixed'] ?? true;
    $headerFile = APP_ROOT . '/templates/headers/' . $headerVariant . '/header.php';
    if (file_exists($headerFile)) {
        include $headerFile;
    } else {
        require_once APP_ROOT . '/includes/header.php';
    }
}

if (!empty($page['rows'])) {
    
    $flexZones = ['main', 'sidebar-left', 'sidebar-right'];
    $flexOpen = false;
    
    foreach ($allZones as $zoneName => $zoneConfig) {
        $isGlobal = !empty($zoneConfig['global']);
        
        if ($isGlobal) {
            if ($zoneName === 'header' && $showHeader) {
                continue;
            }
            if ($zoneName === 'footer' && $showFooter) {
                continue;
            }
        }
        
        $zoneRows = $zonesWithContent[$zoneName] ?? [];
        if (empty($zoneRows)) {
            continue;
        }
        
        $isFlexZone = in_array($zoneName, $flexZones);
        
        // Открываем flex-контейнер только если есть контент в сайдбаре
        if ($isFlexZone && $hasSidebar && !$flexOpen) {
            echo '<div class="flex flex-wrap">';
            $flexOpen = true;
        }
        
        // Определяем класс зоны
        $zoneClass = $zoneConfig['class'] ?? '';
        
        // Для main, если нет сайдбара и шаблон НЕ boxed — переопределяем класс на w-full
        if ($zoneName === 'main' && !$hasSidebar && $templateId !== 'boxed') {
            $zoneClass = 'w-full';
        }
        
        if (!empty($zoneClass)) {
            echo '<div class="' . e($zoneClass) . '">';
        }
        
        foreach ($zoneRows as $row) {
            $rowId = $row['id'] ?? '';
            $rowClass = $row['settings']['class'] ?? '';
            
            echo '<section' . (!empty($rowClass) ? ' class="' . e($rowClass) . '"' : '') . (!empty($rowId) ? ' id="' . e($rowId) . '"' : '') . '>';
            
            $columnsCount = count($row['columns']);
            
            if ($columnsCount === 1) {
                $col = $row['columns'][0];
                $colId = $col['id'] ?? '';
                $widthClass = $col['width_class'] ?? 'w-full';
                $colClass = $col['settings']['class'] ?? '';
                
                $colClasses = trim($widthClass . ' ' . $colClass);
                echo '<div' . (!empty($colClasses) ? ' class="' . e($colClasses) . '"' : '') . (!empty($colId) ? ' id="' . e($colId) . '"' : '') . '>';
                
                foreach ($col['modules'] as $module) {
                    $modId = $module['id'] ?? '';
                    $data = $module['data'] ?? [];
                    $moduleData = $data;
                    $moduleClass = $module['settings']['class'] ?? '';
                    $moduleFile = APP_ROOT . '/modules/' . $module['type'] . '.php';
                    
                    echo '<div' . (!empty($moduleClass) ? ' class="' . e($moduleClass) . '"' : '') . (!empty($modId) ? ' id="' . e($modId) . '"' : '') . '>';
                    if (file_exists($moduleFile)) {
                        include $moduleFile;
                    } else {
                        echo '<!-- Модуль ' . $module['type'] . ' не найден -->';
                    }
                    echo '</div>';
                }
                
                echo '</div>';
            } else {
                echo '<div class="flex flex-wrap">';
                foreach ($row['columns'] as $col) {
                    $colId = $col['id'] ?? '';
                    $widthClass = $col['width_class'] ?? 'w-full';
                    $colClass = $col['settings']['class'] ?? '';
                    
                    $colClasses = trim($widthClass . ' ' . $colClass);
                    echo '<div' . (!empty($colClasses) ? ' class="' . e($colClasses) . '"' : '') . (!empty($colId) ? ' id="' . e($colId) . '"' : '') . '>';
                    
                    foreach ($col['modules'] as $module) {
                        $modId = $module['id'] ?? '';
                        $data = $module['data'] ?? [];
                        $moduleData = $data;
                        $moduleClass = $module['settings']['class'] ?? '';
                        $moduleFile = APP_ROOT . '/modules/' . $module['type'] . '.php';
                        
                        echo '<div' . (!empty($moduleClass) ? ' class="' . e($moduleClass) . '"' : '') . (!empty($modId) ? ' id="' . e($modId) . '"' : '') . '>';
                        if (file_exists($moduleFile)) {
                            include $moduleFile;
                        } else {
                            echo '<!-- Модуль ' . $module['type'] . ' не найден -->';
                        }
                        echo '</div>';
                    }
                    
                    echo '</div>';
                }
                echo '</div>';
            }
            
            echo '</section>';
        }
        
        if (!empty($zoneClass)) {
            echo '</div>';
        }
        
        // Закрываем flex-контейнер после последней flex-зоны
        if ($isFlexZone && $flexOpen) {
            $currentIndex = array_search($zoneName, array_keys($allZones));
            $hasMoreFlex = false;
            $zoneKeys = array_keys($allZones);
            for ($i = $currentIndex + 1; $i < count($zoneKeys); $i++) {
                if (in_array($zoneKeys[$i], $flexZones) && isset($zonesWithContent[$zoneKeys[$i]])) {
                    $hasMoreFlex = true;
                    break;
                }
            }
            if (!$hasMoreFlex) {
                echo '</div>';
                $flexOpen = false;
            }
        }
    }
    
    if ($flexOpen) {
        echo '</div>';
    }
}

if ($showFooter) {
    $footerVariant = $settings['footer_variant'] ?? 'default';
    $footerFile = APP_ROOT . '/templates/footers/' . $footerVariant . '/footer.php';
    if (file_exists($footerFile)) {
        include $footerFile;
    } else {
        include APP_ROOT . '/includes/footer.php';
    }
}