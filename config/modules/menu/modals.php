<!-- Единая модалка для добавления/редактирования пункта -->
<div id="item-modal" class="modal-backdrop-fixed hidden">
    <div class="modal-content-card" style="max-width: 650px;">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
            <h3 class="text-base font-bold text-slate-800" id="item-modal-title">Добавить пункт меню</h3>
            <button type="button" onclick="closeItemModal()" class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 text-slate-400 rounded-lg hover:text-slate-600 transition-all cursor-pointer">
                <span class="icon-x text-xl"></span>
            </button>
        </div>
        <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">
            <input type="hidden" id="edit-item-id" value="">
            
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Тип пункта</label>
                <select id="item-type" onchange="toggleItemFields(this.value)" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <option value="page">Страница</option>
                    <option value="section">Раздел (якорь)</option>
                    <option value="custom">Произвольная ссылка</option>
                    <option value="dropdown">Выпадающее меню</option>
                    <option value="divider">Разделитель</option>
                </select>
            </div>
            
            <div id="item-label-wrap">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Название</label>
                <input type="text" id="item-label" placeholder="Название пункта" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            
            <div id="item-url-wrap" style="display:none;">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Ссылка</label>
                <input type="text" id="item-url" placeholder="/#section-id" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            
            <div id="item-page-wrap" style="display:none;">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Страница</label>
                <select id="item-page" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <?php foreach ($pageOptions as $page): ?>
                        <option value="<?php echo $page['url']; ?>"><?php echo $page['label']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Вложенные пункты для dropdown -->
            <div id="item-children-wrap" style="display:none;">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Вложенные пункты</label>
                    <button type="button" onclick="addChildItem()" class="px-2 py-1 text-xs bg-[var(--primary-color)] text-white rounded-lg hover:bg-[var(--primary-dark)] transition-all flex items-center gap-1">
                        <span class="icon-plus text-xs"></span> Добавить
                    </button>
                </div>
                <div id="item-children-container" class="space-y-2">
                    <div class="text-center py-4 text-xs text-slate-400 border-2 border-dashed border-slate-200 rounded-lg">
                        Нет вложенных пунктов
                    </div>
                </div>
            </div>
        </div>
        <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex justify-end gap-2 flex-shrink-0">
            <button type="button" onclick="closeItemModal()" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl hover:bg-slate-50 transition cursor-pointer">Отмена</button>
            <button type="button" onclick="saveItem()" class="px-6 py-2 bg-[var(--primary-color)] text-white font-bold rounded-xl hover:bg-[var(--primary-dark)] transition shadow-lg shadow-[var(--primary-color)]/20" id="item-modal-save-btn">
                Добавить
            </button>
        </div>
    </div>
</div>

<!-- Шаблон для вложенного пункта -->
<template id="child-item-template">
    <div class="flex items-center gap-2 bg-white p-2 rounded-lg border border-slate-200 child-item">
        <select class="px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] child-type" style="width:110px; flex-shrink:0;">
            <option value="page">Страница</option>
            <option value="section">Раздел</option>
            <option value="custom">Ссылка</option>
        </select>
        <input type="text" placeholder="Название" class="flex-1 min-w-[80px] px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] child-label">
        <input type="text" placeholder="Ссылка" class="flex-1 min-w-[80px] px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] child-url">
        <button type="button" onclick="removeChildItem(this)" class="w-7 h-7 rounded-lg flex items-center justify-center text-rose-400 hover:text-rose-600 hover:bg-rose-50 transition-all flex-shrink-0" title="Удалить">
            <span class="icon-x text-sm"></span>
        </button>
    </div>
</template>