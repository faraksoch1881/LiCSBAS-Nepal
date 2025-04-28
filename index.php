<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaflet Map with Layer Controls</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        #map { height: calc(100vh - 60px); width: 85%; float: left; }
        .sidebar {
            width: 15%;
            height: 100%;
            position: fixed;
            right: 0;
            top: 0;
            background-color: #fff;
            border-left: 1px solid #ccc;
            overflow-y: auto;
            padding: 10px;
            box-shadow: -2px 0 5px rgba(0,0,0,0.1);
        }
        .sidebar table {
            width: 100%;
            border-collapse: collapse;
        }
        .sidebar th, .sidebar td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .sidebar th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .sidebar td i {
            cursor: pointer;
            font-size: 16px;
        }
        .sidebar td i.fa-eye, .sidebar td i.fa-eye-slash {
            color: #0078A8;
        }
        .bottom-bar {
            position: fixed;
            bottom: 0;
            width: 85%;
            height: 60px;
            background-color: #f8f9fa;
            border-top: 1px solid #ccc;
            padding: 10px;
        }
        #coords {
            width: 100%;
            height: 100%;
            resize: none;
            font-family: monospace;
            font-size: 14px;
            padding: 5px;
            border: 1px solid #ccc;
            background-color: #fff;
        }
    </style>
</head>
<body>
    <div id="map"></div>
    <div class="sidebar">
        <h5>Ascending</h5>
        <table>
            <thead>
                <tr><th>Name</th><th>Show</th></tr>
            </thead>
            <tbody id="ascending-layers"></tbody>
        </table>
        <h5 class="mt-3">Descending</h5>
        <table>
            <thead>
                <tr><th>Name</th><th>Show</th></tr>
            </thead>
            <tbody id="descending-layers"></tbody>
        </table>
    </div>
    <div class="bottom-bar">
        <div class="container-fluid h-100">
            <div class="row h-100">
                <div class="col-md-9">
                    <textarea id="coords" class="form-control h-100" readonly></textarea>
                </div>
                <div class="col-md-3 d-flex align-items-center">
                    <input type="file" id="kml-upload" class="d-none" accept=".kml">
                    <button class="btn btn-primary me-2" onclick="document.getElementById('kml-upload').click()">Upload KML</button>
                    <button class="btn btn-danger" id="kml-remove">Remove KML</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/leaflet-omnivore@0.3.4/leaflet-omnivore.min.js"></script>
    <script>
        // Initialize the map
        const map = L.map('map', {
            minZoom: 2,
            maxZoom: 17,
            worldCopyJump: true
        }).setView([0, 0], 2);

        // Basemaps
        const openStreetMap = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: 'Centre for Observation and Modelling of Earthquakes, Volcanoes and Tectonics (COMET). GNSS station data from Nevada Geodetic Laboratory (NGL)',
            maxZoom: 18
        }).addTo(map);

        const imageryMap = L.tileLayer('https://services.arcgisonline.com/arcgis/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Esri World Imagery',
            maxZoom: 18,
            opacity: 0.6
        });

        // Store KML layers
        const kmlLayers = [];

        // Load GeoJSON data
        let group1, group2;
        Promise.all([
            fetch('group1.json').then(res => res.json()),
            fetch('group2.json').then(res => res.json())
        ]).then(([g1, g2]) => {
            group1 = g1;
            group2 = g2;

            // GeoJSON layers
            const ascendingLayer = L.geoJSON(group1, {
                style: { color: '#000000', weight: 2, opacity: 0.65 },
                onEachFeature: function(feature, layer) {
                    layer.bindTooltip(`Frame ID: ${feature.properties.frame_id}<br>Products: ${feature.properties.products}`, { sticky: true, direction: 'top' });
                    layer.bindPopup(`
                        <strong>Frame ID: ${feature.properties.frame_id}</strong><br>
                        Direction: ${feature.properties.direction}<br>
                        Products: ${feature.properties.products}<br>
                        Epochs processed (%): ${feature.properties.epochs_processed}<br>
                        Maximum network length (years): ${feature.properties.network_length}<br>
                        GACOS (%): ${feature.properties.gacos}<br>
                        Download: <a href='${feature.properties.download}' target='_blank'>Link</a><br>
                        Network: <a href='${feature.properties.network_img}' target='_blank'><img src='${feature.properties.network_img}' width='100'></a>
                    `, { offset: [0, -10] });
                    feature.layer = layer; // Store layer reference
                }
            }).addTo(map);

            const descendingLayer = L.geoJSON(group2, {
                style: { color: '#ff0000', weight: 2, opacity: 0.65 },
                onEachFeature: function(feature, layer) {
                    layer.bindTooltip(`Frame ID: ${feature.properties.frame_id}<br>Products: ${feature.properties.products}`, { sticky: true, direction: 'top' });
                    layer.bindPopup(`
                        <strong>Frame ID: ${feature.properties.frame_id}</strong><br>
                        Direction: ${feature.properties.direction}<br>
                        Products: ${feature.properties.products}<br>
                        Epochs processed (%): ${feature.properties.epochs_processed}<br>
                        Maximum network length (years): ${feature.properties.network_length}<br>
                        GACOS (%): ${feature.properties.gacos}<br>
                        Download: <a href='${feature.properties.download}' target='_blank'>Link</a><br>
                        Network: <a href='${feature.properties.network_img}' target='_blank'><img src='${feature.properties.network_img}' width='100'></a>
                    `, { offset: [0, -10] });
                    feature.layer = layer; // Store layer reference
                }
            }).addTo(map);

            // Populate sidebar
            group1.features.forEach(feature => {
                $('#ascending-layers').append(`
                    <tr class="layer-row" data-frame-id="${feature.properties.frame_id}">
                        <td>${feature.properties.frame_id}</td>
                        <td><i class="fa-regular fa-eye layer-toggle" title="Hide layer"></i></td>
                    </tr>
                `);
            });

            group2.features.forEach(feature => {
                $('#descending-layers').append(`
                    <tr class="layer-row" data-frame-id="${feature.properties.frame_id}">
                        <td>${feature.properties.frame_id}</td>
                        <td><i class="fa-regular fa-eye layer-toggle" title="Hide layer"></i></td>
                    </tr>
                `);
            });

            // Toggle visibility
            $('.layer-toggle').on('click', function() {
                const icon = $(this);
                const frameId = icon.closest('tr').data('frame-id');
                const isAscending = icon.closest('tbody').attr('id') === 'ascending-layers';
                const features = isAscending ? group1.features : group2.features;
                const feature = features.find(f => f.properties.frame_id === frameId);
                const isVisible = icon.hasClass('fa-eye');
                if (isVisible) {
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                    icon.attr('title', 'Show layer');
                    map.removeLayer(feature.layer);
                } else {
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                    icon.attr('title', 'Hide layer');
                    map.addLayer(feature.layer);
                }
            });

            // Custom control for radio buttons and checkboxes
            const CustomControl = L.Control.extend({
                options: { position: 'topright' },
                onAdd: function(map) {
                    const container = L.DomUtil.create('div', 'leaflet-control-custom');
                    container.innerHTML = `
                        <div>
                            <input type="radio" id="openstreet" name="basemap" value="openstreet" checked>
                            <label for="openstreet">OpenStreet</label><br>
                            <input type="radio" id="imagery" name="basemap" value="imagery">
                            <label for="imagery">Imagery</label><br>
                            <input type="checkbox" id="ascending" name="overlay" value="ascending" checked>
                            <label for="ascending">Ascending</label><br>
                            <input type="checkbox" id="descending" name="overlay" value="descending" checked>
                            <label for="descending">Descending</label>
                        </div>
                    `;

                    L.DomEvent.on(container, 'change', function(e) {
                        const target = e.target;
                        if (target.name === 'basemap') {
                            if (target.value === 'openstreet') {
                                if (!map.hasLayer(openStreetMap)) {
                                    map.addLayer(openStreetMap);
                                    map.removeLayer(imageryMap);
                                }
                            } else if (target.value === 'imagery') {
                                if (!map.hasLayer(imageryMap)) {
                                    map.addLayer(imageryMap);
                                    map.removeLayer(openStreetMap);
                                }
                            }
                        } else if (target.name === 'overlay') {
                            if (target.id === 'ascending') {
                                if (target.checked) {
                                    map.addLayer(ascendingLayer);
                                    $(`#ascending-layers .layer-toggle`).removeClass('fa-eye-slash').addClass('fa-eye').attr('title', 'Hide layer');
                                } else {
                                    map.removeLayer(ascendingLayer);
                                    $(`#ascending-layers .layer-toggle`).removeClass('fa-eye').addClass('fa-eye-slash').attr('title', 'Show layer');
                                }
                            } else if (target.id === 'descending') {
                                if (target.checked) {
                                    map.addLayer(descendingLayer);
                                    $(`#descending-layers .layer-toggle`).removeClass('fa-eye-slash').addClass('fa-eye').attr('title', 'Hide layer');
                                } else {
                                    map.removeLayer(descendingLayer);
                                    $(`#descending-layers .layer-toggle`).removeClass('fa-eye').addClass('fa-eye-slash').attr('title', 'Show layer');
                                }
                            }
                        }
                    });

                    return container;
                }
            });

            map.addControl(new CustomControl());

            // Draw control
            const drawnItems = new L.FeatureGroup();
            map.addLayer(drawnItems);
            const drawControl = new L.Control.Draw({
                draw: {
                    rectangle: {
                        shapeOptions: { color: '#ff0000', weight: 2 }
                    },
                    polygon: false,
                    polyline: false,
                    circle: false,
                    marker: false,
                    circlemarker: false
                },
                edit: {
                    featureGroup: drawnItems,
                    remove: true
                }
            });
            map.addControl(drawControl);

            // Handle rectangle creation
            map.on(L.Draw.Event.CREATED, function(e) {
                const layer = e.layer;
                drawnItems.clearLayers();
                drawnItems.addLayer(layer);
                const bounds = layer.getBounds();
                const minLng = bounds.getWest().toFixed(6);
                const maxLng = bounds.getEast().toFixed(6);
                const minLat = bounds.getSouth().toFixed(6);
                const maxLat = bounds.getNorth().toFixed(6);
                const coords = `${minLng}/${maxLng}/${minLat}/${maxLat}`;
                document.getElementById('coords').value = coords;
            });

            // Clear coordinates when rectangle is deleted
            map.on(L.Draw.Event.DELETED, function() {
                document.getElementById('coords').value = '';
            });

            // Enable copying on click
            document.getElementById('coords').addEventListener('click', function() {
                this.select();
                document.execCommand('copy');
            });

            // KML upload
            document.getElementById('kml-upload').addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const kmlLayer = omnivore.kml.parse(e.target.result);
                        kmlLayer.setStyle({
                            color: '#FF00FF',
                            weight: 2,
                            opacity: 1,
                            fillOpacity: 0
                        });
                        kmlLayer.addTo(map);
                        kmlLayers.push(kmlLayer);
                        // map.fitBounds(kmlLayer.getBounds());
                    } catch (err) {
                        console.error('Error parsing KML:', err);
                    }
                };
                reader.readAsText(file);
            });

            // KML remove
            document.getElementById('kml-remove').addEventListener('click', function() {
                kmlLayers.forEach(layer => map.removeLayer(layer));
                kmlLayers.length = 0;
            });
        }).catch(err => console.error('Error loading GeoJSON:', err));

   
    </script>
</body>
</html>