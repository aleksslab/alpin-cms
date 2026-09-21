/**
 * Текстовый блок — инициализация Ace Editor
 */
(function() {
    'use strict';

    function initTextEditor() {
        var container = document.getElementById('text-editor-container');
        var textarea = document.getElementById('text-editor');
        
        if (!container || !textarea) {
            return;
        }
        
        if (typeof ace === 'undefined') {
            textarea.style.display = 'block';
            textarea.style.width = '100%';
            textarea.style.height = '300px';
            textarea.style.padding = '0.75rem';
            textarea.style.border = '1px solid #e2e8f0';
            textarea.style.borderRadius = '0.5rem';
            textarea.style.fontFamily = 'monospace';
            textarea.style.fontSize = '13px';
            textarea.style.resize = 'vertical';
            return;
        }
        
        try {
            var editor = ace.edit(container);
            
            editor.session.setMode('ace/mode/html');
            editor.setOptions({
                fontSize: '13px',
                fontFamily: 'monospace',
                tabSize: 4,
                useSoftTabs: true,
                showPrintMargin: false,
                wrap: true
            });
            
            editor.setValue(textarea.value || '', -1);
            
            editor.session.on('change', function() {
                textarea.value = editor.getValue();
            });
            
            window.aceEditors = window.aceEditors || {};
            window.aceEditors['text-editor'] = editor;
            
            // Ресайз при открытии модалки
            var modal = document.getElementById('module-settings-modal');
            if (modal) {
                var observer = new MutationObserver(function() {
                    if (modal.classList.contains('active')) {
                        setTimeout(function() {
                            editor.resize();
                        }, 200);
                    }
                });
                observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
            }
            
        } catch (e) {
            textarea.style.display = 'block';
            textarea.style.width = '100%';
            textarea.style.height = '300px';
            textarea.style.padding = '0.75rem';
            textarea.style.border = '1px solid #e2e8f0';
            textarea.style.borderRadius = '0.5rem';
            textarea.style.fontFamily = 'monospace';
            textarea.style.fontSize = '13px';
            textarea.style.resize = 'vertical';
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTextEditor);
    } else {
        setTimeout(initTextEditor, 100);
    }
    
    var modal = document.getElementById('module-settings-modal');
    if (modal) {
        var observer = new MutationObserver(function() {
            if (modal.classList.contains('active')) {
                setTimeout(initTextEditor, 150);
            }
        });
        observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
    }
})();