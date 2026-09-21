<?php
$rowId = $row['id'] ?? '';
$rowSettings = $row['settings'] ?? [];
$columns = $row['columns'] ?? [];
$rowClass = $rowSettings['class'] ?? '';
$jsRowId = !empty($row['_jsId']) ? $row['_jsId'] : ('row_' . uniqid());
?>

<div class="constructor-row bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm <?php echo e($rowClass); ?>" 
     data-row-id="<?php echo e($jsRowId); ?>" 
     data-zone="<?php echo e($zoneName); ?>">
    
    <!-- ШАПКА РЯДА -->
    <div class="bg-slate-50/50 px-3 py-2 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-3 text-xs text-slate-500">
            <!-- ID ряда -->
            <div class="flex items-center gap-1.5">
                <span class="text-[10px] font-bold text-slate-400">ID:</span>
                <input type="text" value="<?php echo e($rowId); ?>" placeholder="(не задан)" 
                       class="row-id-input text-[10px] px-1.5 py-0.5 border border-slate-200 rounded bg-white w-24 focus:outline-none focus:border-[var(--primary-color)] font-mono"
                       data-row-id="<?php echo e($jsRowId); ?>">
            </div>
            
            <span class="text-slate-300">|</span>
            
            <!-- Классы ряда -->
            <div class="flex items-center gap-1.5 flex-1 min-w-[120px]">
                <span class="text-[10px] font-bold text-slate-400">Класс:</span>
                <input type="text" value="<?php echo e($rowClass); ?>" placeholder="w-full, bg-slate-50, my-4..." 
                       class="row-class-input text-[10px] px-2 py-0.5 border border-slate-200 rounded bg-white w-full max-w-[200px] focus:outline-none focus:border-[var(--primary-color)]"
                       data-row-id="<?php echo e($jsRowId); ?>">
            </div>
            
            <span class="text-slate-300">|</span>
            <span class="text-[10px] text-slate-400">Колонок: <?php echo count($columns); ?></span>
        </div>
        
        <!-- Кнопки управления -->
        <div class="flex gap-0.5">
            <button type="button" onclick="window.moveRow('<?php echo e($jsRowId); ?>', 'up')" class="w-7 h-7 rounded flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all" title="Переместить вверх">
                <span class="icon-chevron-up text-xs"></span>
            </button>
            <button type="button" onclick="window.moveRow('<?php echo e($jsRowId); ?>', 'down')" class="w-7 h-7 rounded flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all" title="Переместить вниз">
                <span class="icon-chevron-down text-xs"></span>
            </button>
            <button type="button" onclick="window.deleteRow('<?php echo e($jsRowId); ?>')" class="w-7 h-7 rounded flex items-center justify-center text-rose-400 hover:text-rose-600 hover:bg-rose-50 transition-all" title="Удалить ряд">
                <span class="icon-trash-2 text-xs"></span>
            </button>
        </div>
    </div>
    
    <!-- ПАНЕЛЬ УПРАВЛЕНИЯ РЯДОМ -->
    <div class="px-3 py-1.5 bg-slate-50/30 border-b border-slate-100 flex flex-wrap items-center gap-2">
        <button type="button" onclick="window.addColumn('<?php echo e($jsRowId); ?>')" class="px-2.5 py-0.5 text-[10px] bg-white border border-slate-200 text-slate-600 font-bold rounded hover:bg-slate-50 transition-all flex items-center gap-1">
            <span class="icon-plus text-xs"></span> Колонка
        </button>
        <button type="button" onclick="window.openModuleModal('<?php echo e($jsRowId); ?>')" class="px-2.5 py-0.5 text-[10px] bg-white border border-slate-200 text-slate-600 font-bold rounded hover:bg-slate-50 transition-all flex items-center gap-1">
            <span class="icon-plus text-xs"></span> Модуль
        </button>
        <span class="text-[10px] text-slate-400 ml-1">
            Ширина: 
            <?php 
            $widths = array_map(function($col) { 
                return $col['width_class'] ?? 'w-full';
            }, $columns);
            echo implode(' + ', $widths); 
            ?>
        </span>
    </div>
    
    <!-- КОЛОНКИ -->
    <div class="constructor-columns p-2 flex flex-wrap" data-row-id="<?php echo e($jsRowId); ?>">
        <?php foreach ($columns as $col): ?>
            <?php 
            $colId = $col['id'] ?? '';
            $widthClass = $col['width_class'] ?? 'w-full';
            $modules = $col['modules'] ?? [];
            $colClass = $col['settings']['class'] ?? '';
            $jsColId = !empty($col['_jsId']) ? $col['_jsId'] : ('col_' . uniqid());
            ?>
            <div class="constructor-column bg-slate-50/50 border border-slate-200 rounded-lg p-2 <?php echo e($widthClass); ?> <?php echo e($colClass); ?>" 
                 data-col-id="<?php echo e($jsColId); ?>" 
                 data-row-id="<?php echo e($jsRowId); ?>">
                
                <!-- ШАПКА КОЛОНКИ -->
                <div class="flex items-center justify-between mb-1.5 text-[10px] text-slate-500">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <!-- ID колонки -->
                        <div class="flex items-center gap-1">
                            <span class="text-[9px] font-bold text-slate-400">ID:</span>
                            <input type="text" value="<?php echo e($colId); ?>" placeholder="(не задан)" 
                                   class="col-id-input text-[9px] px-1 py-0 border border-slate-200 rounded bg-white w-20 focus:outline-none focus:border-[var(--primary-color)] font-mono"
                                   data-col-id="<?php echo e($jsColId); ?>">
                        </div>
                        
                        <span class="text-slate-300">|</span>
                        
                        <!-- Ширина - теперь с полноценными классами -->
                        <div class="flex items-center gap-0.5">
                            <button type="button" onclick="window.changeColumnWidth('<?php echo e($jsColId); ?>', -1)" class="w-4 h-4 rounded flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all">
                                <span class="icon-chevron-left text-[8px]"></span>
                            </button>
                            <span class="text-[9px] font-bold text-slate-700 w-12 text-center font-mono"><?php echo e($widthClass); ?></span>
                            <button type="button" onclick="window.changeColumnWidth('<?php echo e($jsColId); ?>', 1)" class="w-4 h-4 rounded flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all">
                                <span class="icon-chevron-right text-[8px]"></span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Кнопки управления колонкой -->
                    <div class="flex gap-0.5">
                        <button type="button" onclick="window.deleteColumn('<?php echo e($jsColId); ?>')" class="w-5 h-5 rounded flex items-center justify-center text-rose-400 hover:text-rose-600 hover:bg-rose-50 transition-all" title="Удалить колонку">
                            <span class="icon-x text-[9px]"></span>
                        </button>
                    </div>
                </div>
                
                <!-- КЛАССЫ КОЛОНКИ -->
                <div class="mb-1.5 flex items-center gap-1.5">
                    <span class="text-[9px] font-bold text-slate-400">Класс:</span>
                    <input type="text" value="<?php echo e($colClass); ?>" placeholder="bg-white, p-4, border..." 
                           class="col-class-input text-[9px] px-1.5 py-0.5 border border-slate-200 rounded bg-white w-full focus:outline-none focus:border-[var(--primary-color)]"
                           data-col-id="<?php echo e($jsColId); ?>">
                </div>
                
                <!-- МОДУЛИ -->
                <div class="space-y-1.5 min-h-[40px]" data-col-id="<?php echo e($jsColId); ?>">
                    <?php foreach ($modules as $module): ?>
                        <?php 
                        $modId = $module['id'] ?? '';
                        $modType = $module['type'] ?? 'unknown';
                        $modData = $module['data'] ?? [];
                        $modGlobal = $module['global'] ?? true;
                        $jsModId = !empty($module['_jsId']) ? $module['_jsId'] : ('mod_' . uniqid());
                        
                        $moduleInfo = $modulesList[$modType] ?? ['name' => $modType, 'icon' => 'icon-box', 'has_settings' => false];
                        $hasSettings = $moduleInfo['has_settings'] ?? false;
                        $moduleName = $moduleInfo['name'] ?? $modType;
                        $moduleIcon = $moduleInfo['icon'] ?? 'icon-box';
                        ?>
                        <div class="bg-white border border-slate-200 rounded-md p-1.5 group" 
                             data-module-id="<?php echo e($jsModId); ?>" 
                             data-type="<?php echo e($modType); ?>"
                             data-col-id="<?php echo e($jsColId); ?>">
                            <div class="flex items-center justify-between gap-1">
                                <div class="flex items-center gap-1.5 flex-1 min-w-0">
                                    <span class="text-[8px] font-mono text-slate-400"><?php echo e($modId); ?></span>
                                    <span class="<?php echo e($moduleIcon); ?> text-slate-400 text-xs"></span>
                                    <span class="text-[10px] font-medium text-slate-700 truncate"><?php echo e($moduleName); ?></span>
                                    
                                    <?php if (!empty($modData) && is_array($modData) && count($modData) > 0): ?>
                                        <span class="text-[8px] text-slate-400 truncate max-w-[60px]">
                                            <?php 
                                            $firstKey = array_key_first($modData);
                                            $firstVal = $modData[$firstKey] ?? '';
                                            if (is_string($firstVal) && strlen($firstVal) > 0) {
                                                echo mb_strimwidth($firstVal, 0, 20, '...');
                                            } elseif (is_array($firstVal) && count($firstVal) > 0) {
                                                echo count($firstVal) . ' эл.';
                                            }
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($modGlobal): ?>
                                        <span class="text-[8px] bg-emerald-50 text-emerald-600 px-1 rounded">глоб.</span>
                                    <?php else: ?>
                                        <span class="text-[8px] bg-amber-50 text-amber-600 px-1 rounded">своё</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex items-center gap-0.5 flex-shrink-0">
                                    <?php if ($hasSettings): ?>
                                        <button type="button" onclick="window.openModuleSettings('<?php echo e($jsModId); ?>')" class="w-5 h-5 rounded flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all" title="Настройки">
                                            <span class="icon-edit text-[10px]"></span>
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" onclick="window.deleteModule('<?php echo e($jsModId); ?>')" class="w-5 h-5 rounded flex items-center justify-center text-rose-400 hover:text-rose-600 hover:bg-rose-50 transition-all" title="Удалить модуль">
                                        <span class="icon-trash-2 text-[10px]"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <button type="button" onclick="window.openModuleModalForColumn('<?php echo e($jsColId); ?>')" class="w-full py-1 text-[10px] border border-dashed border-slate-300 text-slate-400 rounded hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-all flex items-center justify-center gap-0.5">
                        <span class="icon-plus text-[10px]"></span> Добавить модуль
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>