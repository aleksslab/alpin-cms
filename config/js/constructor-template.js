/**
 * Смена шаблона (AJAX)
 */
window.changeTemplate = function() {
    const select = document.getElementById('constructor-template-select');
    if (!select) return;
    
    const template = select.value;
    const container = document.getElementById('constructor-app');
    if (!container) return;
    
    // Разрешаем пустой pageId в режиме создания страницы
    const pageId = container.dataset.pageId || '';
    
    const showHeader = document.querySelector('input[name="show_header"]')?.checked ? 1 : 0;
    const showFooter = document.querySelector('input[name="show_footer"]')?.checked ? 1 : 0;
    
    // Обновляем скрытые поля
    const hiddenTemplate = document.getElementById('template-hidden');
    const hiddenHeader = document.getElementById('show-header-hidden');
    const hiddenFooter = document.getElementById('show-footer-hidden');
    if (hiddenTemplate) hiddenTemplate.value = template;
    if (hiddenHeader) hiddenHeader.value = showHeader;
    if (hiddenFooter) hiddenFooter.value = showFooter;
    
    container.innerHTML = `
        <div class="p-8 text-center text-slate-500">
            <span class="icon-refresh-cw animate-spin inline-block mr-2"></span> 
            Загрузка конструктора...
        </div>
    `;
    
    // Динамически подставляем action=create или action=edit
    const currentAction = pageId ? 'edit' : 'create';
    
    fetch('?tab=pages&action=' + currentAction + '&id=' + pageId + '&tab_edit=constructor&ajax=1&template=' + encodeURIComponent(template) + '&show_header=' + showHeader + '&show_footer=' + showFooter)
        .then(response => response.text())
        .then(html => {
            const savedMemoryData = (window.constructor && window.constructor.data) ? window.constructor.data : [];
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContainer = doc.getElementById('constructor-app');
            
            if (newContainer) {
                container.innerHTML = newContainer.innerHTML;
                container.dataset.pageId = newContainer.dataset.pageId || pageId;
                container.dataset.token = newContainer.dataset.token || '';
                container.dataset.modules = newContainer.dataset.modules || '{}';
                
                const newData = doc.getElementById('constructor-data');
                if (newData) {
                    const dataInput = document.getElementById('constructor-data');
                    if (dataInput) dataInput.value = JSON.stringify(savedMemoryData);
                }
                
                if (typeof window.initConstructor === 'function') {
                    window.initConstructor();
                }
            } else {
                container.innerHTML = `
                    <div class="p-8 text-center text-rose-500">
                        Ошибка: конструктор не найден
                    </div>
                `;
            }
        })
        .catch(() => {
            container.innerHTML = `
                <div class="p-8 text-center text-rose-500">
                    Ошибка сети. Попробуйте обновить страницу.
                </div>
            `;
        });
};

/**
 * Переключение хедера/футера (AJAX)
 */
window.toggleHeaderFooter = function() {
    const container = document.getElementById('constructor-app');
    if (!container) return;
    
    // Разрешаем пустой pageId в режиме создания страницы
    const pageId = container.dataset.pageId || '';
    
    const showHeader = document.querySelector('input[name="show_header"]')?.checked ? 1 : 0;
    const showFooter = document.querySelector('input[name="show_footer"]')?.checked ? 1 : 0;
    const template = document.getElementById('constructor-template-select')?.value || 'full-width';
    
    const hiddenHeader = document.getElementById('show-header-hidden');
    const hiddenFooter = document.getElementById('show-footer-hidden');
    const hiddenTemplate = document.getElementById('template-hidden');
    if (hiddenHeader) hiddenHeader.value = showHeader;
    if (hiddenFooter) hiddenFooter.value = showFooter;
    if (hiddenTemplate) hiddenTemplate.value = template;
    
    container.innerHTML = `
        <div class="p-8 text-center text-slate-500">
            <span class="icon-refresh-cw animate-spin inline-block mr-2"></span> 
            Обновление конструктора...
        </div>
    `;
    
    // Динамически подставляем action=create или action=edit
    const currentAction = pageId ? 'edit' : 'create';
    
    fetch('?tab=pages&action=' + currentAction + '&id=' + pageId + '&tab_edit=constructor&ajax=1&template=' + encodeURIComponent(template) + '&show_header=' + showHeader + '&show_footer=' + showFooter)
        .then(response => response.text())
        .then(html => {
            const savedMemoryData = (window.constructor && window.constructor.data) ? window.constructor.data : [];
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContainer = doc.getElementById('constructor-app');
            
            if (newContainer) {
                container.innerHTML = newContainer.innerHTML;
                container.dataset.pageId = newContainer.dataset.pageId || pageId;
                container.dataset.token = newContainer.dataset.token || '';
                container.dataset.modules = newContainer.dataset.modules || '{}';
                
                const newData = doc.getElementById('constructor-data');
                if (newData) {
                    const dataInput = document.getElementById('constructor-data');
                    if (dataInput) dataInput.value = JSON.stringify(savedMemoryData);
                }
                
                if (typeof window.initConstructor === 'function') {
                    window.initConstructor();
                }
            } else {
                container.innerHTML = `
                    <div class="p-8 text-center text-rose-500">
                        Ошибка: конструктор не найден
                    </div>
                `;
            }
        })
        .catch(() => {
            container.innerHTML = `
                <div class="p-8 text-center text-rose-500">
                    Ошибка сети. Попробуйте обновить страницу.
                </div>
            `;
        });
};
