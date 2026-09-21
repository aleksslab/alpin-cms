/**
 * Яндекс.Карты — скрипты для сайта
 */
(function() {
    'use strict';

    function initMap() {
        const containers = document.querySelectorAll('.map-container');
        
        containers.forEach(function(container) {
            const mapId = container.id;
            const centerLat = parseFloat(container.getAttribute('data-center-lat')) || 55.7558;
            const centerLng = parseFloat(container.getAttribute('data-center-lng')) || 37.6176;
            const zoom = parseInt(container.getAttribute('data-zoom')) || 15;
            const enableClustering = container.getAttribute('data-enable-clustering') === 'true';
            let activeMarkerIndex = parseInt(container.getAttribute('data-active-marker'));
            if (isNaN(activeMarkerIndex)) {
                activeMarkerIndex = -1;
            }
            let items = [];
            
            try {
                items = JSON.parse(container.getAttribute('data-items')) || [];
            } catch(e) {
                items = [];
            }
            
            if (typeof ymaps === 'undefined') {
                container.innerHTML = '<div class="flex items-center justify-center h-full text-slate-400">Яндекс.Карты не загружены</div>';
                return;
            }
            
            ymaps.ready(function() {
                try {
                    const loader = container.querySelector('.map-loading');
                    if (loader) {
                        loader.style.display = 'none';
                    }
                    
                    const map = new ymaps.Map(mapId, {
                        center: [centerLat, centerLng],
                        zoom: zoom,
                        controls: ['zoomControl', 'fullscreenControl']
                    });
                    
                    const placemarks = [];
                    const placemarkData = [];
                    
                    // ===== КАСТОМНЫЙ МАКЕТ ХИНТА =====
                    const HintLayout = ymaps.templateLayoutFactory.createClass(
                        '<div class="custom-hint">' +
                            '<div class="custom-hint-name">{{ properties.hintName }}</div>' +
                            '<div class="custom-hint-address">{{ properties.hintAddress }}</div>' +
                        '</div>',
                        {
                            getShape: function() {
                                var el = this.getElement();
                                var result = null;
                                if (el) {
                                    var firstChild = el.firstChild;
                                    result = new ymaps.shape.Rectangle(
                                        new ymaps.geometry.pixel.Rectangle([
                                            [0, 0],
                                            [firstChild.offsetWidth, firstChild.offsetHeight]
                                        ])
                                    );
                                }
                                return result;
                            }
                        }
                    );
                    
                    items.forEach(function(item, index) {
                        const lat = parseFloat(item.lat);
                        const lng = parseFloat(item.lng);
                        const name = item.name || '';
                        const address = item.address || '';
                        const phone = item.phone || '';
                        const email = item.email || '';
                        const workHours = item.work_hours || '';
                        const icon = item.icon || '';
                        
                        if (isNaN(lat) || isNaN(lng)) return;
                        
                        placemarkData.push({
                            lat: lat,
                            lng: lng,
                            name: name,
                            address: address,
                            phone: phone,
                            email: email,
                            workHours: workHours,
                            icon: icon,
                            index: index
                        });
                        
                        // Кастомный контент балуна с иконками из шрифта
                        let balloonContent = '<div class="custom-balloon">' +
                            '<div class="custom-balloon-icon">' +
                                (icon ? '<span class="' + icon + '"></span>' : '') +
                            '</div>' +
                            '<div class="custom-balloon-content">';
                        
                        if (name) balloonContent += '<div class="custom-balloon-title">' + name + '</div>';
                        if (address) balloonContent += '<div class="custom-balloon-address"><span class="icon-map-pin"></span> ' + address + '</div>';
                        if (phone) balloonContent += '<div class="custom-balloon-contact"><span class="icon-phone"></span> ' + phone + '</div>';
                        if (email) balloonContent += '<div class="custom-balloon-contact"><span class="icon-mail"></span> ' + email + '</div>';
                        if (workHours) balloonContent += '<div class="custom-balloon-contact"><span class="icon-clock"></span> ' + workHours + '</div>';
                        
                        balloonContent += '</div></div>';
                        
                        // Хинт: название + адрес
                        let hintName = name || '';
                        let hintAddress = address || '';
                        
                        const placemark = new ymaps.Placemark(
                            [lat, lng],
                            {
                                balloonContent: balloonContent,
                                balloonContentHeader: '',
                                balloonContentFooter: '',
                                hintName: hintName,
                                hintAddress: hintAddress,
                                placemarkIndex: index
                            },
                            {
                                preset: 'islands#redCircleIcon',
                                iconColor: '#10B981',
                                balloonPanelMaxMapArea: 0,
                                hideIconOnBalloonOpen: false,
                                balloonOffset: [0, -30],
                                balloonMaxWidth: 300,
                                balloonMinWidth: 200,
                                balloonMinHeight: 60,
                                balloonAutoPan: true,
                                hintOffset: [0, -45],
                                hintLayout: HintLayout
                            }
                        );
                        
                        placemarks.push(placemark);
                    });
                    
                    // Кластеризация
                    if (enableClustering && placemarks.length > 1) {
                        const clusterer = new ymaps.Clusterer({
                            gridSize: 50,
                            clusterDisableClickZoom: false,
                            clusterHideIconOnBalloonOpen: true,
                            clusterIconColor: '#10B981',
                            clusterNumbers: [1, 5, 10, 20, 50, 100],
                            clusterIconLayout: 'default#pieChart',
                            clusterIconPieChartRadius: 30,
                            clusterBalloonContentLayout: 'cluster#balloonTwoColumns',
                            clusterBalloonContent: function(properties) {
                                const geoObjects = properties.geoObjects;
                                let content = '<div class="cluster-balloon"><div class="cluster-balloon-title">Метки на карте</div>';
                                geoObjects.each(function(obj) {
                                    const content = obj.properties.get('balloonContent') || '';
                                    const match = content.match(/<div class="custom-balloon-title">([^<]*)<\/div>/);
                                    const name = match ? match[1] : '';
                                    content += '<div class="cluster-balloon-item">' + name + '</div>';
                                });
                                content += '</div>';
                                return content;
                            }
                        });
                        clusterer.add(placemarks);
                        map.geoObjects.add(clusterer);
                    } else {
                        placemarks.forEach(function(pm) {
                            map.geoObjects.add(pm);
                        });
                    }
                    
                    // Активная метка
                    if (activeMarkerIndex >= 0 && placemarks[activeMarkerIndex]) {
                        setTimeout(function() {
                            const targetPlacemark = placemarks[activeMarkerIndex];
                            const coords = [placemarkData[activeMarkerIndex].lat, placemarkData[activeMarkerIndex].lng];

                            map.panTo(coords, {
                                flying: true,
                                duration: 600
                            });

                            setTimeout(function() {
                                map.balloon.close();

                                if (enableClustering && placemarks.length > 1) {
                                    let clusterer = null;
                                    map.geoObjects.each(function(obj) {
                                        if (obj && typeof obj.getClusterOfObject === 'function') {
                                            clusterer = obj;
                                        }
                                    });

                                    if (clusterer) {
                                        const cluster = clusterer.getClusterOfObject(targetPlacemark);
                                        if (cluster) {
                                            cluster.balloon.open();
                                            return;
                                        }
                                    }
                                }

                                targetPlacemark.balloon.open();
                            }, 400);
                        }, 500);
                    } else if (placemarks.length > 0 && activeMarkerIndex === -1) {
                        setTimeout(function() {
                            map.setCenter([placemarkData[0].lat, placemarkData[0].lng], zoom, {
                                duration: 600
                            });
                        }, 300);
                    }
                    
                    // Клик по карточкам
                    const placemarkItems = document.querySelectorAll('.map-placemark-item');
                    placemarkItems.forEach(function(item, index) {
                        item.addEventListener('click', function() {
                            const data = placemarkData[index];
                            if (!data) return;
                            
                            map.panTo([data.lat, data.lng], {
                                flying: true,
                                duration: 600,
                                useMapMargin: true
                            });
                            
                            setTimeout(function() {
                                map.balloon.close();
                                if (placemarks[index]) {
                                    placemarks[index].balloon.open();
                                }
                            }, 400);
                            
                            placemarkItems.forEach(function(el) {
                                el.classList.remove('active-placemark');
                            });
                            item.classList.add('active-placemark');
                        });
                    });
                    
                    map.container.fitToViewport();
                    
                    let resizeTimeout;
                    window.addEventListener('resize', function() {
                        clearTimeout(resizeTimeout);
                        resizeTimeout = setTimeout(function() {
                            map.container.fitToViewport();
                        }, 300);
                    });
                } catch(e) {
                    console.error('Map error:', e);
                    container.innerHTML = '<div class="flex items-center justify-center h-full text-rose-500">Ошибка загрузки карты. Проверьте настройки.</div>';
                }
            });
        });
    }

    function loadYandexMaps() {
        const containers = document.querySelectorAll('.map-container');
        if (containers.length === 0) return;
        
        if (typeof ymaps !== 'undefined') {
            initMap();
            return;
        }
        
        if (document.querySelector('script[src*="api-maps.yandex.ru"]')) {
            const checkYmaps = setInterval(function() {
                if (typeof ymaps !== 'undefined') {
                    clearInterval(checkYmaps);
                    initMap();
                }
            }, 200);
            return;
        }
        
        const container = containers[0];
        const apiKey = container.getAttribute('data-api-key') || '';
        
        if (!apiKey) {
            containers.forEach(function(c) {
                c.innerHTML = '<div class="flex items-center justify-center h-full text-amber-500">API ключ Яндекс.Карт не указан. Добавьте ключ в настройках модуля.</div>';
            });
            return;
        }
        
        const script = document.createElement('script');
        script.src = 'https://api-maps.yandex.ru/2.1/?apikey=' + encodeURIComponent(apiKey) + '&lang=ru_RU';
        script.async = true;
        script.onload = function() {
            initMap();
        };
        script.onerror = function() {
            containers.forEach(function(c) {
                c.innerHTML = '<div class="flex items-center justify-center h-full text-rose-500">Ошибка загрузки карты. Проверьте API ключ.</div>';
            });
        };
        document.head.appendChild(script);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadYandexMaps);
    } else {
        loadYandexMaps();
    }
})();