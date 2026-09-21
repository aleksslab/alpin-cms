<!-- Модалка исключений минификации -->
<div id="minify-exceptions-modal" class="modal-backdrop-fixed !z-9995">
    <div class="modal-content-card" style="max-width: 700px; max-height: 90vh;">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
            <h3 class="text-base font-bold text-slate-800">Исключения минификации</h3>
            <button type="button" onclick="document.getElementById('minify-exceptions-modal').classList.remove('active')" 
                    class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 text-slate-400 rounded-lg hover:text-slate-600 transition-all">
                <span class="icon-x text-xl"></span>
            </button>
        </div>
        
        <div class="p-4 overflow-y-auto" style="max-height: calc(90vh - 140px);">
            <p class="text-xs text-slate-500 mb-3">Отключите минификацию для CSS и/или JS конкретного модуля, если возникают проблемы.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <?php foreach ($modules as $id => $data): 
                    $excludeCss = $minifyExceptions[$id]['css'] ?? false;
                    $excludeJs = $minifyExceptions[$id]['js'] ?? false;
                ?>
                    <div class="flex items-center gap-3 p-2 bg-slate-50 rounded-xl border border-slate-200">
                        <span class="text-xs font-medium text-slate-700 w-24 truncate"><?php echo e($data['name'] ?? $id); ?></span>
                        <label class="flex items-center gap-1 cursor-pointer text-[10px]">
                            <input type="checkbox" class="minify-exception-checkbox" 
                                   name="minify_exclude_css[<?php echo $id; ?>]" 
                                   value="1" 
                                   data-module="<?php echo $id; ?>" 
                                   data-type="css">
                            <span class="text-slate-500">CSS</span>
                        </label>
                        <label class="flex items-center gap-1 cursor-pointer text-[10px]">
                            <input type="checkbox" class="minify-exception-checkbox" 
                                   name="minify_exclude_js[<?php echo $id; ?>]" 
                                   value="1" 
                                   data-module="<?php echo $id; ?>" 
                                   data-type="js">
                            <span class="text-slate-500">JS</span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="text-[10px] text-slate-400 mt-3">Изменения сохранятся при нажатии кнопки <strong>"Сохранить настройки"</strong> на странице.</p>
        </div>
        
        <div class="p-4 border-t border-slate-100 flex justify-end">
            <button type="button" onclick="window.saveMinifyExceptions()" 
                    class="px-4 py-2 bg-[var(--primary-color)] text-white font-bold rounded-xl hover:bg-[var(--primary-dark)] transition">
                Сохранить
            </button>
        </div>
    </div>
</div>