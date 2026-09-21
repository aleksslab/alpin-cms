/**
 * Портфолио — скрипты для админки
 */
(function() {
    'use strict';

    function updateCategorySelects() {
        var categories = [];
        var tags = document.querySelectorAll('#categories-container .category-tag');
        tags.forEach(function(tag) {
            var text = tag.textContent.trim();
            var catName = text.replace('×', '').trim();
            if (catName) {
                categories.push(catName);
            }
        });
        
        var hidden = document.getElementById('categories-hidden');
        if (hidden) {
            var allCategories = ['Все'];
            categories.forEach(function(cat) {
                allCategories.push(cat);
            });
            hidden.value = allCategories.join('|');
        }
        
        var selects = document.querySelectorAll('.category-select');
        selects.forEach(function(select) {
            var currentValue = select.value;
            var newOptions = '<option value="">Выберите категорию</option>';
            categories.forEach(function(cat) {
                var selected = cat === currentValue ? 'selected' : '';
                newOptions += '<option value="' + cat + '" ' + selected + '>' + cat + '</option>';
            });
            select.innerHTML = newOptions;
            if (currentValue && categories.indexOf(currentValue) !== -1) {
                select.value = currentValue;
            }
        });
        
        var template = document.getElementById('tmpl-portfolio-item');
        if (template) {
            var templateOptions = '';
            categories.forEach(function(cat) {
                templateOptions += '<option value="' + cat + '">' + cat + '</option>';
            });
            var html = template.innerHTML;
            html = html.replace(/<option value="">Выберите категорию<\/option>.*?<\/select>/, 
                '<option value="">Выберите категорию</option>' + templateOptions + '</select>');
            template.innerHTML = html;
        }
    }

    function initModule() {
        var container = document.getElementById('portfolio-items-container');
        if (!container) return;

        // ===== КАТЕГОРИИ =====
        var addCategoryBtn = document.getElementById('add-category-btn');
        var categoryInput = document.getElementById('new-category-input');
        var categoriesContainer = document.getElementById('categories-container');
        
        if (addCategoryBtn && categoryInput && categoriesContainer) {
            addCategoryBtn.addEventListener('click', function() {
                var name = categoryInput.value.trim();
                if (!name) return;
                if (name === 'Все') {
                    alert('Название "Все" зарезервировано');
                    return;
                }
                
                var existing = categoriesContainer.querySelectorAll('.category-tag');
                var exists = false;
                existing.forEach(function(tag) {
                    var text = tag.textContent.replace('×', '').trim();
                    if (text === name) exists = true;
                });
                if (exists) {
                    alert('Такая категория уже существует');
                    return;
                }
                
                var tag = document.createElement('span');
                tag.className = 'category-tag inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-sm text-slate-700';
                tag.innerHTML = name + '<button type="button" class="js-remove-category text-slate-400 hover:text-rose-500 transition-colors" data-category="' + name + '"><span class="icon-x text-sm"></span></button>';
                categoriesContainer.appendChild(tag);
                categoryInput.value = '';
                
                updateCategorySelects();
            });
            
            categoryInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    addCategoryBtn.click();
                }
            });
        }
        
        if (categoriesContainer) {
            categoriesContainer.addEventListener('click', function(e) {
                var btn = e.target.closest('.js-remove-category');
                if (!btn) return;
                var tag = btn.closest('.category-tag');
                if (tag) {
                    var catName = btn.getAttribute('data-category');
                    var selects = document.querySelectorAll('.category-select');
                    var inUse = false;
                    selects.forEach(function(select) {
                        if (select.value === catName) {
                            inUse = true;
                        }
                    });
                    
                    if (inUse) {
                        if (!confirm('Категория "' + catName + '" используется в работах. Удалить всё равно?')) {
                            return;
                        }
                        selects.forEach(function(select) {
                            if (select.value === catName) {
                                select.value = '';
                            }
                        });
                    }
                    
                    tag.remove();
                    updateCategorySelects();
                }
            });
        }

        // ===== РАБОТЫ =====
        var addBtn = document.getElementById('add-portfolio-item-btn');
        if (addBtn) {
            var newBtn = addBtn.cloneNode(true);
            addBtn.parentNode.replaceChild(newBtn, addBtn);
            newBtn.addEventListener('click', function() {
                var template = document.getElementById('tmpl-portfolio-item');
                if (!template) return;
                
                var index = container.querySelectorAll('.portfolio-item-card').length;
                var html = template.innerHTML;
                html = html.replace(/__INDEX__/g, index);
                var temp = document.createElement('div');
                temp.innerHTML = html;
                var newItem = temp.firstElementChild;
                if (newItem) {
                    container.appendChild(newItem);
                    newItem.querySelectorAll('.js-image-url-input').forEach(function(input) {
                        if (typeof updatePreviewOnManualInput === 'function') {
                            updatePreviewOnManualInput(input);
                        }
                    });
                }
            });
        }

        container.addEventListener('click', function(e) {
            var btn = e.target.closest('.js-remove-portfolio-item');
            if (!btn) return;
            var card = btn.closest('.portfolio-item-card');
            if (card) {
                var items = container.querySelectorAll('.portfolio-item-card');
                if (items.length > 1) {
                    card.remove();
                } else {
                    alert('Должна остаться хотя бы одна работа');
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModule);
    } else {
        initModule();
    }
})();