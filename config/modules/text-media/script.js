/**
 * Текст + Медиа — скрипты для админки (модуль)
 */
(function() {
    'use strict';

    function initModule() {
        // Features
        const featuresContainer = document.getElementById('features-container');
        if (featuresContainer) {
            const addBtn = document.getElementById('add-feature-btn');
            if (addBtn) {
                const newBtn = addBtn.cloneNode(true);
                addBtn.parentNode.replaceChild(newBtn, addBtn);
                newBtn.addEventListener('click', function() {
                    const template = document.getElementById('tmpl-feature');
                    if (!template) return;
                    
                    const index = featuresContainer.querySelectorAll('.feature-item').length + 1;
                    let html = template.innerHTML;
                    html = html.replace(/__INDEX__/g, index);
                    const temp = document.createElement('div');
                    temp.innerHTML = html;
                    const newItem = temp.firstElementChild;
                    if (newItem) {
                        featuresContainer.appendChild(newItem);
                    }
                });
            }
            
            featuresContainer.addEventListener('click', function(e) {
                const btn = e.target.closest('.js-remove-feature');
                if (!btn) return;
                const item = btn.closest('.feature-item');
                if (item) {
                    const items = featuresContainer.querySelectorAll('.feature-item');
                    if (items.length > 1) {
                        item.remove();
                    } else {
                        alert('Должна остаться хотя бы одна особенность');
                    }
                }
            });
        }
        
        // Stats
        const statsContainer = document.getElementById('stats-container');
        if (statsContainer) {
            const addBtn = document.getElementById('add-stat-btn');
            if (addBtn) {
                const newBtn = addBtn.cloneNode(true);
                addBtn.parentNode.replaceChild(newBtn, addBtn);
                newBtn.addEventListener('click', function() {
                    const template = document.getElementById('tmpl-stat');
                    if (!template) return;
                    
                    const index = statsContainer.querySelectorAll('.stat-item').length + 1;
                    let html = template.innerHTML;
                    html = html.replace(/__INDEX__/g, index);
                    const temp = document.createElement('div');
                    temp.innerHTML = html;
                    const newItem = temp.firstElementChild;
                    if (newItem) {
                        statsContainer.appendChild(newItem);
                    }
                });
            }
            
            statsContainer.addEventListener('click', function(e) {
                const btn = e.target.closest('.js-remove-stat');
                if (!btn) return;
                const item = btn.closest('.stat-item');
                if (item) {
                    const items = statsContainer.querySelectorAll('.stat-item');
                    if (items.length > 1) {
                        item.remove();
                    } else {
                        alert('Должна остаться хотя бы одна статистика');
                    }
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModule);
    } else {
        initModule();
    }
})();