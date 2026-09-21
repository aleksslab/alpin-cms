/**
 * Медиа-модалка
 */
(function() {
    /**
     * Экранирует HTML-спецсимволы для безопасной вставки в innerHTML.
     * @param {*} text Значение для экранирования
     * @returns {string}
     */
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    let activeInputTarget = null;

    function openMediaModal(button, root = false) {
        const group = button.closest('.js-media-input-group');
        if (group) { 
            activeInputTarget = group.querySelector('.js-image-url-input'); 
        }
        const modal = document.getElementById('media-modal');
        if (modal) {
            // data-folder уже задан в media_modal.php, не трогаем
            modal.setAttribute('data-root', root ? '1' : '0');
            modal.classList.add('active');
        }
    }

    function closeMediaModal() {
        const modal = document.getElementById('media-modal');
        if (modal) { modal.classList.remove('active'); }
        const errorMsg = document.getElementById('upload-error-msg');
        if (errorMsg) { errorMsg.classList.add('hidden'); }
        activeInputTarget = null;
    }

    window.selectImageForTarget = function(url) {
        if (activeInputTarget) {
            activeInputTarget.value = url;
            
            const container = activeInputTarget.closest('.js-media-module-container');
            if (container) {
                const previewImg = container.querySelector('.js-slide-preview-img');
                const placeholder = container.querySelector('.js-slide-preview-placeholder');
                if (previewImg && placeholder) {
                    previewImg.src = '../' + url;
                    previewImg.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                }
            }
            closeMediaModal();
        }
    };

    window.updatePreviewOnManualInput = function(input) {
        const container = input.closest('.js-media-module-container');
        if (!container) return;
        
        const previewImg = container.querySelector('.js-slide-preview-img');
        const placeholder = container.querySelector('.js-slide-preview-placeholder');
        if (!previewImg || !placeholder) return;

        const val = input.value.trim();
        if (val === '') {
            previewImg.classList.add('hidden');
            placeholder.classList.remove('hidden');
            previewImg.src = '';
        } else {
            previewImg.src = (val.startsWith('http://') || val.startsWith('https://')) ? val : '../' + val;
            previewImg.classList.remove('hidden');
            placeholder.classList.add('hidden');
        }
    };

    window.zoomCarouselImage = function(previewElement) {
        const img = previewElement.querySelector('.js-slide-preview-img');
        const zoomModal = document.getElementById('carousel-zoom-modal');
        const zoomTargetImg = document.getElementById('zoom-modal-target-img');
        if (img && !img.classList.contains('hidden') && img.src) {
            zoomTargetImg.src = img.src;
            if (zoomModal) { zoomModal.classList.add('active'); }
        }
    };

    window.closeZoomModal = function() {
        const zoomModal = document.getElementById('carousel-zoom-modal');
        if (zoomModal) { zoomModal.classList.remove('active'); }
    };

    uploadImageFromDevice = function(droppedFiles = null) {
        const fileInput = document.getElementById('modal-file-input');
        const errorMsg = document.getElementById('upload-error-msg');
        const modalContainer = document.getElementById('media-modal');

        const filesSource = droppedFiles || (fileInput ? fileInput.files : null);
        if (!filesSource || filesSource.length === 0) return;
        if (errorMsg) { errorMsg.classList.add('hidden'); }

        const targetFolder = modalContainer ? modalContainer.getAttribute('data-folder') : '';
        const uploadToRoot = modalContainer ? modalContainer.getAttribute('data-root') === '1' : false;
        const csrfInput = document.querySelector('form input[name="csrf_token"]');
        const secureToken = csrfInput ? csrfInput.value : '';

        const formData = new FormData();
        formData.append('carousel_file', filesSource[0]);
        formData.append('ajax_upload_carousel_img', '1');
        formData.append('target_folder', targetFolder);
        formData.append('upload_to_root', uploadToRoot ? '1' : '0');
        formData.append('csrf_token', secureToken);

        fetch(window.location.href, { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const grid = document.getElementById('media-grid');
                const emptyMsg = document.getElementById('empty-grid-msg');
                if (emptyMsg) emptyMsg.remove();

                const newCard = document.createElement('div');
                newCard.className = "media-preview-item group js-media-item";
                newCard.setAttribute('data-url', data.url);

                // Правильный путь для превью
                const imgSrc = data.url.startsWith('images/') ? '../' + data.url : '/' + data.url;
                const safeImgSrc = escapeHtml(imgSrc);

                newCard.innerHTML = `
                    <img src="${safeImgSrc}" class="w-full h-full object-cover group-hover:scale-105 transition-all duration-300">
                    <div class="absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 transition-all flex items-center justify-center">
                        <span class="px-2 py-1 bg-white text-slate-800 text-[10px] font-bold rounded-lg shadow border border-slate-100">Выбрать</span>
                    </div>
                `;
                if (grid) { grid.insertBefore(newCard, grid.firstChild); }
                selectImageForTarget(data.url);
                if (fileInput) fileInput.value = '';
            } else {
                if (errorMsg) { errorMsg.innerText = data.error; errorMsg.classList.remove('hidden'); }
            }
        })
        .catch(() => {
            if (errorMsg) { errorMsg.innerText = 'Системная ошибка соединения.'; errorMsg.classList.remove('hidden'); }
        });
    };

    // Делегирование кликов по плиткам медиа-модалки
    document.addEventListener('DOMContentLoaded', function() {
        const grid = document.getElementById('media-grid');
        if (!grid) return;

        grid.addEventListener('click', function(e) {
            const item = e.target.closest('.js-media-item');
            if (!item) return;
            const url = item.getAttribute('data-url');
            if (url) {
                window.selectImageForTarget(url);
            }
        });
    });

    // Drag & Drop
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.querySelector('.modal-upload-inline-bar');
        if (!dropZone) return;

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(eventName) {
            dropZone.addEventListener(eventName, function(e) { e.preventDefault(); }, false);
            document.body.addEventListener(eventName, function(e) { e.preventDefault(); }, false);
        });
        ['dragenter', 'dragover'].forEach(function(eventName) {
            dropZone.addEventListener(eventName, function() { dropZone.classList.add('drag-over'); }, false);
        });
        ['dragleave', 'drop'].forEach(function(eventName) {
            dropZone.addEventListener(eventName, function() { dropZone.classList.remove('drag-over'); }, false);
        });
        dropZone.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files && files.length > 0) { uploadImageFromDevice(files); }
        }, false);
    });

    // Выносим функции в глобальную область
    window.openMediaModal = openMediaModal;
    window.closeMediaModal = closeMediaModal;
    window.uploadImageFromDevice = uploadImageFromDevice;
})();