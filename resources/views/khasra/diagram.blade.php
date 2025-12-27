<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khasra {{ $khasra_no }} - Diagram</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }
        #map {
            height: 100vh;
            width: 100%;
        }
        .info-panel {
            position: absolute;
            top: 10px;
            right: 10px;
            background: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            z-index: 1000;
            max-width: 300px;
        }
        .info-panel h2 {
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        .info-panel p {
            margin: 5px 0;
            font-size: 14px;
        }
        .karam-list {
            max-height: 400px;
            overflow-y: auto;
            margin-top: 10px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .karam-item {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
            font-size: 12px;
        }
        .karam-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="info-panel">
        <h2>Khasra No: {{ $khasra_no }}</h2>
        <p><strong>Mauza:</strong> {{ $parcel['properties']['Mauza_Name'] }}</p>
        <p><strong>PC Name:</strong> {{ $parcel['properties']['PC_Name'] }}</p>
        <p><strong>Karams Found:</strong> {{ $count }}</p>
        @if($count > 0)
        <div class="karam-list">
            <strong>Karam Lengths:</strong>
            @foreach($karams as $karam)
            <div class="karam-item">
                Karam {{ $karam['properties']['Karam'] }}: {{ number_format($karam['properties']['length_meters'], 2) }} m ({{ number_format($karam['properties']['length_feet'], 2) }} ft)
            </div>
            @endforeach
        </div>
        @endif
    </div>
    <div id="map"></div>

    <script>
        const parcelData = @json($parcel);
        const karamsData = @json($karams);

        const map = L.map('map').setView([33.85, 70.17], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        const parcelLayer = L.geoJSON(parcelData, {
            style: {
                color: '#3388ff',
                fillColor: '#3388ff',
                fillOpacity: 0.3,
                weight: 3
            },
            onEachFeature: function(feature, layer) {
                layer.bindPopup(`
                    <strong>Khasra No:</strong> ${feature.properties.Khassra_No}<br>
                    <strong>Mauza:</strong> ${feature.properties.Mauza_Name}<br>
                    <strong>PC Name:</strong> ${feature.properties.PC_Name}
                `);
            }
        }).addTo(map);

        const karamsLayer = L.geoJSON(karamsData, {
            style: {
                color: '#ff0000',
                weight: 2,
                opacity: 0.8
            },
            onEachFeature: function(feature, layer) {
                const lengthM = feature.properties.length_meters || 0;
                const lengthF = feature.properties.length_feet || 0;
                layer.bindPopup(`
                    <strong>Karam:</strong> ${feature.properties.Karam}<br>
                    <strong>Karam Urdu:</strong> ${feature.properties.Karam_Urdu}<br>
                    <strong>Length:</strong> ${lengthM.toFixed(2)} m (${lengthF.toFixed(2)} ft)
                `);
            }
        }).addTo(map);

        map.fitBounds(parcelLayer.getBounds().extend(karamsLayer.getBounds()));
    </script>
</body>
</html>

