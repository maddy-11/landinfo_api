<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parcels Map</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            overflow: hidden;
        }
        .container {
            display: flex;
            height: 100vh;
        }
        .sidebar {
            width: 300px;
            background: #f8f9fa;
            border-right: 1px solid #dee2e6;
            padding: 20px;
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .sidebar h2 {
            margin-bottom: 20px;
            color: #333;
            font-size: 20px;
        }
        .filter-group {
            margin-bottom: 20px;
        }
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
            font-size: 14px;
        }
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            background: white;
        }
        .filter-group select:disabled,
        .filter-group input:disabled {
            background: #e9ecef;
            cursor: not-allowed;
        }
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        .clear-filters {
            width: 100%;
            padding: 10px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }
        .clear-filters:hover {
            background: #5a6268;
        }
        #map {
            flex: 1;
            height: 100vh;
        }
        .info-panel {
            position: absolute;
            top: 80px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            max-width: 250px;
            border: 1px solid #e5e7eb;
        }
        .info-panel h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        .info-panel p {
            margin: 5px 0;
            font-size: 14px;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 10px;
            color: #6c757d;
            font-size: 14px;
        }

        /* Floating Search Bubble */
        .search-bubble {
            position: fixed;
            right: 20px;
            top: 20px;
            z-index: 1500;
        }

        .search-bubble-btn {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #2c3e50;
            border: 2px solid #34495e;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            z-index: 2;
        }

        .search-bubble-btn:hover {
            background: #34495e;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            transform: translateY(-2px);
        }

        .search-bubble-btn.active {
            background: #1a252f;
            border-color: #2c3e50;
        }

        .search-icon {
            width: 22px;
            height: 22px;
            stroke: white;
            fill: none;
            stroke-width: 2;
            transition: transform 0.3s ease;
        }

        .search-bubble-btn.active .search-icon {
            transform: rotate(90deg);
        }

        .search-panel {
            position: absolute;
            right: 0;
            top: 60px;
            transform: translateY(-10px) scale(0.95);
            transform-origin: top right;
            width: 320px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.05);
            opacity: 0;
            visibility: hidden;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            z-index: 1;
        }

        .search-panel.active {
            transform: translateY(0) scale(1);
            opacity: 1;
            visibility: visible;
        }

        .search-panel-content {
            padding: 24px;
            background: white;
        }

        .search-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .search-panel-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            letter-spacing: -0.3px;
        }

        .search-panel-close {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            border: none;
            background: #f3f4f6;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .search-panel-close:hover {
            background: #e5e7eb;
        }

        .search-panel-close svg {
            width: 16px;
            height: 16px;
            stroke: #4b5563;
            fill: none;
            stroke-width: 2;
        }

        .search-filter-group {
            margin-bottom: 18px;
            animation: slideIn 0.3s ease forwards;
            opacity: 0;
        }

        .search-filter-group:nth-child(1) { animation-delay: 0.05s; }
        .search-filter-group:nth-child(2) { animation-delay: 0.1s; }
        .search-filter-group:nth-child(3) { animation-delay: 0.15s; }
        .search-filter-group:nth-child(4) { animation-delay: 0.2s; }
        .search-filter-group:nth-child(5) { animation-delay: 0.25s; }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .search-filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
            font-size: 13px;
            letter-spacing: 0.2px;
        }

        .search-filter-group select {
            width: 100%;
            padding: 10px 36px 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            transition: all 0.2s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 10 10'%3E%3Cpath fill='%236b7280' d='M5 7L1 3h8z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
        }

        .search-filter-group select:disabled {
            background: #f9fafb;
            cursor: not-allowed;
            opacity: 0.5;
        }

        .search-filter-group select:focus {
            outline: none;
            border-color: #4b5563;
            box-shadow: 0 0 0 3px rgba(75, 85, 99, 0.1);
        }

        .search-clear-btn {
            width: 100%;
            padding: 11px;
            background: #374151;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 4px;
        }

        .search-clear-btn:hover {
            background: #4b5563;
        }

        .search-clear-btn:active {
            background: #1f2937;
        }

        .search-panel.active .search-filter-group {
            animation-play-state: running;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2>Filters</h2>
            <div class="loading" id="loading">Loading...</div>
            
            <div class="filter-group">
                <label for="sidebar-district">District</label>
                <select id="sidebar-district" name="district">
                    <option value="">All Districts</option>
                    @foreach($districts as $district)
                        <option value="{{ $district }}">{{ $district }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <label for="sidebar-tehsil">Tehsil</label>
                <select id="sidebar-tehsil" name="tehsil" disabled>
                    <option value="">All Tehsils</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="sidebar-mauza">Mauza</label>
                <select id="sidebar-mauza" name="mauza" disabled>
                    <option value="">All Mauzas</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="sidebar-khasra">Khasra</label>
                <select id="sidebar-khasra" name="khasra" disabled>
                    <option value="">All Khasras</option>
                </select>
            </div>

            <button class="clear-filters" onclick="clearFilters('sidebar')">Clear All Filters</button>
        </div>

        <div id="map"></div>
        <div class="info-panel">
            <h3>Parcels Map</h3>
            <p><strong>Total Parcels:</strong> <span id="parcel-count">{{ count($parcelsGeoJson['features']) }}</span></p>
        </div>
    </div>

    <!-- Floating Search Bubble -->
    <div class="search-bubble">
        <button class="search-bubble-btn" id="searchBubbleBtn" onclick="toggleSearchPanel()">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <div class="search-panel" id="searchPanel">
            <div class="search-panel-content">
                <div class="search-panel-header">
                    <h3>Filter Parcels</h3>
                    <button class="search-panel-close" onclick="toggleSearchPanel()">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 6L6 18M6 6L18 18" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
                
                <div class="search-filter-group">
                    <label for="bubble-district">District</label>
                    <select id="bubble-district" name="district">
                        <option value="">All Districts</option>
                        @foreach($districts as $district)
                            <option value="{{ $district }}">{{ $district }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="search-filter-group">
                    <label for="bubble-tehsil">Tehsil</label>
                    <select id="bubble-tehsil" name="tehsil" disabled>
                        <option value="">All Tehsils</option>
                    </select>
                </div>

                <div class="search-filter-group">
                    <label for="bubble-mauza">Mauza</label>
                    <select id="bubble-mauza" name="mauza" disabled>
                        <option value="">All Mauzas</option>
                    </select>
                </div>

                <div class="search-filter-group">
                    <label for="bubble-khasra">Khasra</label>
                    <select id="bubble-khasra" name="khasra" disabled>
                        <option value="">All Khasras</option>
                    </select>
                </div>

                <button class="search-clear-btn" onclick="clearFilters('bubble')">Clear All Filters</button>
            </div>
        </div>
    </div>

    <script>
        const parcelsGeoJson = @json($parcelsGeoJson);
        let map, parcelLayer;
        let currentFilters = {
            district: '',
            tehsil: '',
            mauza: '',
            khasra: ''
        };

        // Initialize map
        map = L.map('map').setView([33.85, 70.17], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // Toggle search panel
        function toggleSearchPanel() {
            const panel = document.getElementById('searchPanel');
            const btn = document.getElementById('searchBubbleBtn');
            panel.classList.toggle('active');
            btn.classList.toggle('active');
        }

        // Initialize parcel layer
        function updateParcelLayer(geoJson) {
            if (parcelLayer) {
                map.removeLayer(parcelLayer);
            }

            parcelLayer = L.geoJSON(geoJson, {
                style: function(feature) {
                    let color = '#3388ff';
                    let fillColor = '#3388ff';
                    
                    if (feature.properties.KhasraType) {
                        const type = feature.properties.KhasraType.toLowerCase();
                        if (type.includes('agricultural')) {
                            color = '#00aa00';
                            fillColor = '#00aa00';
                        } else if (type.includes('abaadi')) {
                            color = '#ff6600';
                            fillColor = '#ff6600';
                        } else if (type.includes('canal')) {
                            color = '#0066ff';
                            fillColor = '#0066ff';
                        }
                    }
                    
                    return {
                        color: color,
                        fillColor: fillColor,
                        fillOpacity: 0.3,
                        weight: 2,
                        opacity: 0.8
                    };
                },
                onEachFeature: function(feature, layer) {
                    const props = feature.properties;
                    let popupContent = '<div style="min-width: 200px;">';
                    popupContent += '<strong>Khasra No:</strong> ' + (props.Khassra_No || 'N/A') + '<br>';
                    popupContent += '<strong>Mauza:</strong> ' + (props.Mauza_Name || 'N/A') + '<br>';
                    popupContent += '<strong>PC Name:</strong> ' + (props.PC_Name || 'N/A') + '<br>';
                    if (props.KhasraType) {
                        popupContent += '<strong>Type:</strong> ' + props.KhasraType + '<br>';
                    }
                    if (props.Khassra_Ar) {
                        popupContent += '<strong>Area:</strong> ' + props.Khassra_Ar + '<br>';
                    }
                    if (props.Status) {
                        popupContent += '<strong>Status:</strong> ' + props.Status + '<br>';
                    }
                    popupContent += '</div>';
                    
                    layer.bindPopup(popupContent);
                    
                    layer.on({
                        mouseover: function(e) {
                            const layer = e.target;
                            layer.setStyle({
                                weight: 4,
                                opacity: 1,
                                fillOpacity: 0.5
                            });
                        },
                        mouseout: function(e) {
                            parcelLayer.resetStyle(e.target);
                        }
                    });
                }
            }).addTo(map);

            // Update parcel count
            document.getElementById('parcel-count').textContent = geoJson.features.length;

            // Fit map bounds
            if (parcelLayer.getLayers().length > 0) {
                map.fitBounds(parcelLayer.getBounds(), {
                    padding: [50, 50]
                });
            }
        }

        // Initial load
        updateParcelLayer(parcelsGeoJson);

        // Filter functions
        function showLoading() {
            document.getElementById('loading').style.display = 'block';
        }

        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }

        function syncFilters(source, target) {
            // Sync values between sidebar and bubble filters
            const sourcePrefix = source === 'sidebar' ? 'sidebar-' : 'bubble-';
            const targetPrefix = target === 'sidebar' ? 'sidebar-' : 'bubble-';
            
            ['district', 'tehsil', 'mauza', 'khasra'].forEach(field => {
                const sourceEl = document.getElementById(sourcePrefix + field);
                const targetEl = document.getElementById(targetPrefix + field);
                if (sourceEl && targetEl) {
                    targetEl.value = sourceEl.value;
                }
            });
        }

        function setupFilterListeners(prefix) {
            const districtEl = document.getElementById(prefix + 'district');
            const tehsilEl = document.getElementById(prefix + 'tehsil');
            const mauzaEl = document.getElementById(prefix + 'mauza');
            const khasraEl = document.getElementById(prefix + 'khasra');

            districtEl.addEventListener('change', function() {
                const district = this.value;
                currentFilters.district = district;
                currentFilters.tehsil = '';
                currentFilters.mauza = '';
                currentFilters.khasra = '';
                
                const otherPrefix = prefix === 'sidebar-' ? 'bubble-' : 'sidebar-';
                document.getElementById(otherPrefix + 'tehsil').value = '';
                document.getElementById(otherPrefix + 'mauza').value = '';
                document.getElementById(otherPrefix + 'khasra').value = '';
                tehsilEl.value = '';
                mauzaEl.value = '';
                khasraEl.value = '';
                
                syncFilters(prefix.replace('-', ''), otherPrefix.replace('-', ''));
                loadTehsils(district, prefix);
                loadFilteredParcels();
            });

            tehsilEl.addEventListener('change', function() {
                const tehsil = this.value;
                const district = districtEl.value;
                currentFilters.tehsil = tehsil;
                currentFilters.mauza = '';
                currentFilters.khasra = '';
                
                const otherPrefix = prefix === 'sidebar-' ? 'bubble-' : 'sidebar-';
                document.getElementById(otherPrefix + 'mauza').value = '';
                document.getElementById(otherPrefix + 'khasra').value = '';
                mauzaEl.value = '';
                khasraEl.value = '';
                
                syncFilters(prefix.replace('-', ''), otherPrefix.replace('-', ''));
                loadMauzas(district, tehsil, prefix);
                loadFilteredParcels();
            });

            mauzaEl.addEventListener('change', function() {
                const mauza = this.value;
                const district = districtEl.value;
                const tehsil = tehsilEl.value;
                currentFilters.mauza = mauza;
                currentFilters.khasra = '';
                
                const otherPrefix = prefix === 'sidebar-' ? 'bubble-' : 'sidebar-';
                document.getElementById(otherPrefix + 'khasra').value = '';
                khasraEl.value = '';
                
                syncFilters(prefix.replace('-', ''), otherPrefix.replace('-', ''));
                loadKhasras(district, tehsil, mauza, prefix);
                loadFilteredParcels();
            });

            khasraEl.addEventListener('change', function() {
                currentFilters.khasra = this.value;
                const otherPrefix = prefix === 'sidebar-' ? 'bubble-' : 'sidebar-';
                syncFilters(prefix.replace('-', ''), otherPrefix.replace('-', ''));
                loadFilteredParcels();
            });
        }

        function loadTehsils(district, prefix) {
            const tehsilSelect = document.getElementById(prefix + 'tehsil');
            const mauzaSelect = document.getElementById(prefix + 'mauza');
            const khasraSelect = document.getElementById(prefix + 'khasra');
            const otherPrefix = prefix === 'sidebar-' ? 'bubble-' : 'sidebar-';
            const otherTehsilSelect = document.getElementById(otherPrefix + 'tehsil');
            const otherMauzaSelect = document.getElementById(otherPrefix + 'mauza');
            const otherKhasraSelect = document.getElementById(otherPrefix + 'khasra');

            if (!district) {
                tehsilSelect.disabled = true;
                tehsilSelect.innerHTML = '<option value="">All Tehsils</option>';
                otherTehsilSelect.disabled = true;
                otherTehsilSelect.innerHTML = '<option value="">All Tehsils</option>';
                currentFilters.tehsil = '';
                currentFilters.mauza = '';
                currentFilters.khasra = '';
                mauzaSelect.innerHTML = '<option value="">All Mauzas</option>';
                khasraSelect.innerHTML = '<option value="">All Khasras</option>';
                mauzaSelect.disabled = true;
                khasraSelect.disabled = true;
                otherMauzaSelect.innerHTML = '<option value="">All Mauzas</option>';
                otherKhasraSelect.innerHTML = '<option value="">All Khasras</option>';
                otherMauzaSelect.disabled = true;
                otherKhasraSelect.disabled = true;
                return;
            }

            showLoading();
            fetch(`/api/tehsils?district=${encodeURIComponent(district)}`)
                .then(response => response.json())
                .then(data => {
                    tehsilSelect.innerHTML = '<option value="">All Tehsils</option>';
                    otherTehsilSelect.innerHTML = '<option value="">All Tehsils</option>';
                    data.forEach(tehsil => {
                        [tehsilSelect, otherTehsilSelect].forEach(select => {
                            const option = document.createElement('option');
                            option.value = tehsil;
                            option.textContent = tehsil;
                            select.appendChild(option);
                        });
                    });
                    tehsilSelect.disabled = false;
                    otherTehsilSelect.disabled = false;
                    hideLoading();
                })
                .catch(error => {
                    console.error('Error loading tehsils:', error);
                    hideLoading();
                });
        }

        function loadMauzas(district, tehsil, prefix) {
            const mauzaSelect = document.getElementById(prefix + 'mauza');
            const khasraSelect = document.getElementById(prefix + 'khasra');
            const otherPrefix = prefix === 'sidebar-' ? 'bubble-' : 'sidebar-';
            const otherMauzaSelect = document.getElementById(otherPrefix + 'mauza');
            const otherKhasraSelect = document.getElementById(otherPrefix + 'khasra');

            if (!district || !tehsil) {
                mauzaSelect.disabled = true;
                mauzaSelect.innerHTML = '<option value="">All Mauzas</option>';
                otherMauzaSelect.disabled = true;
                otherMauzaSelect.innerHTML = '<option value="">All Mauzas</option>';
                currentFilters.mauza = '';
                currentFilters.khasra = '';
                khasraSelect.innerHTML = '<option value="">All Khasras</option>';
                khasraSelect.disabled = true;
                otherKhasraSelect.innerHTML = '<option value="">All Khasras</option>';
                otherKhasraSelect.disabled = true;
                return;
            }

            showLoading();
            let url = `/api/mauzas?district=${encodeURIComponent(district)}`;
            if (tehsil) {
                url += `&tehsil=${encodeURIComponent(tehsil)}`;
            }
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    mauzaSelect.innerHTML = '<option value="">All Mauzas</option>';
                    otherMauzaSelect.innerHTML = '<option value="">All Mauzas</option>';
                    data.forEach(mauza => {
                        [mauzaSelect, otherMauzaSelect].forEach(select => {
                            const option = document.createElement('option');
                            option.value = mauza;
                            option.textContent = mauza;
                            select.appendChild(option);
                        });
                    });
                    mauzaSelect.disabled = false;
                    otherMauzaSelect.disabled = false;
                    hideLoading();
                })
                .catch(error => {
                    console.error('Error loading mauzas:', error);
                    hideLoading();
                });
        }

        function loadKhasras(district, tehsil, mauza, prefix) {
            const khasraSelect = document.getElementById(prefix + 'khasra');
            const otherPrefix = prefix === 'sidebar-' ? 'bubble-' : 'sidebar-';
            const otherKhasraSelect = document.getElementById(otherPrefix + 'khasra');

            if (!district || !tehsil || !mauza) {
                khasraSelect.disabled = true;
                khasraSelect.innerHTML = '<option value="">All Khasras</option>';
                otherKhasraSelect.disabled = true;
                otherKhasraSelect.innerHTML = '<option value="">All Khasras</option>';
                currentFilters.khasra = '';
                return;
            }

            showLoading();
            let url = `/api/khasras?district=${encodeURIComponent(district)}`;
            if (tehsil) {
                url += `&tehsil=${encodeURIComponent(tehsil)}`;
            }
            if (mauza) {
                url += `&mauza=${encodeURIComponent(mauza)}`;
            }
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    khasraSelect.innerHTML = '<option value="">All Khasras</option>';
                    otherKhasraSelect.innerHTML = '<option value="">All Khasras</option>';
                    data.forEach(khasra => {
                        [khasraSelect, otherKhasraSelect].forEach(select => {
                            const option = document.createElement('option');
                            option.value = khasra;
                            option.textContent = khasra;
                            select.appendChild(option);
                        });
                    });
                    khasraSelect.disabled = false;
                    otherKhasraSelect.disabled = false;
                    hideLoading();
                })
                .catch(error => {
                    console.error('Error loading khasras:', error);
                    hideLoading();
                });
        }

        function loadFilteredParcels() {
            showLoading();
            const params = new URLSearchParams();
            if (currentFilters.district) params.append('district', currentFilters.district);
            if (currentFilters.tehsil) params.append('tehsil', currentFilters.tehsil);
            if (currentFilters.mauza) params.append('mauza', currentFilters.mauza);
            if (currentFilters.khasra) params.append('khasra', currentFilters.khasra);

            fetch(`/api/parcels/filtered?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    updateParcelLayer(data);
                    hideLoading();
                })
                .catch(error => {
                    console.error('Error loading filtered parcels:', error);
                    hideLoading();
                });
        }

        function clearFilters(source) {
            const prefix = source === 'sidebar' ? 'sidebar-' : 'bubble-';
            const otherPrefix = source === 'sidebar' ? 'bubble-' : 'sidebar-';
            
            document.getElementById(prefix + 'district').value = '';
            document.getElementById(prefix + 'tehsil').value = '';
            document.getElementById(prefix + 'mauza').value = '';
            document.getElementById(prefix + 'khasra').value = '';
            document.getElementById(prefix + 'tehsil').disabled = true;
            document.getElementById(prefix + 'mauza').disabled = true;
            document.getElementById(prefix + 'khasra').disabled = true;
            document.getElementById(prefix + 'tehsil').innerHTML = '<option value="">All Tehsils</option>';
            document.getElementById(prefix + 'mauza').innerHTML = '<option value="">All Mauzas</option>';
            document.getElementById(prefix + 'khasra').innerHTML = '<option value="">All Khasras</option>';
            
            document.getElementById(otherPrefix + 'district').value = '';
            document.getElementById(otherPrefix + 'tehsil').value = '';
            document.getElementById(otherPrefix + 'mauza').value = '';
            document.getElementById(otherPrefix + 'khasra').value = '';
            document.getElementById(otherPrefix + 'tehsil').disabled = true;
            document.getElementById(otherPrefix + 'mauza').disabled = true;
            document.getElementById(otherPrefix + 'khasra').disabled = true;
            document.getElementById(otherPrefix + 'tehsil').innerHTML = '<option value="">All Tehsils</option>';
            document.getElementById(otherPrefix + 'mauza').innerHTML = '<option value="">All Mauzas</option>';
            document.getElementById(otherPrefix + 'khasra').innerHTML = '<option value="">All Khasras</option>';
            
            currentFilters = { district: '', tehsil: '', mauza: '', khasra: '' };
            updateParcelLayer(parcelsGeoJson);
        }

        // Setup event listeners for both sidebar and bubble filters
        setupFilterListeners('sidebar-');
        setupFilterListeners('bubble-');
    </script>
</body>
</html>
