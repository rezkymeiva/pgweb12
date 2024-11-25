<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />

    <title>Web-GIS with GeoServer and Leaflet</title>

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        #map {
            width: 100%;
            height: 100vh;
        }

        /* Style untuk legenda agar lebih baik */
        .legend {
            background-color: rgba(255, 255, 255, 0.7);
            padding: 10px;
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            width: 230px;
            font-size: 14px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
        }

        .legend img {
            max-width: 100%;
            border-radius: 4px;
        }

        .legend-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
            color: #333;
        }

        /* Styling untuk layer control */
        .leaflet-control-layers {
            background-color: rgba(255, 255, 255, 0.7);
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
            font-size: 14px;
            width: 250px;
        }

        .leaflet-control-layers label {
            display: flex;
            align-items: center;
            margin: 5px 0;
        }

        .leaflet-control-layers img {
            width: 25px;
            height: 25px;
            margin-right: 10px;
        }

        .layer-control-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }
    </style>
</head>

<body>
    <div id="map"></div>
</body>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<script>
    // Inisialisasi peta
    var map = L.map("map").setView([-8.0, 110.5], 10);

    // Tambahkan base map OpenStreetMap
    var osm = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    });

    // Tambahkan WMS layer dari GeoServer (Batas Administrasi Desa)
    var wmsLayer = L.tileLayer.wms("http://localhost:8080/geoserver/pg_web/wms", {
        layers: "pg_web:513284_Kecamatan_GK", // Workspace:LayerName
        format: "image/png",
        transparent: true,
        attribution: "GeoServer WMS Layer",
    });

    // Overlay layer: WMS (Jalan dari GeoPortal Sleman)
    var jalan = L.tileLayer.wms("http://peta.gunungkidulkab.go.id:8080/geoserver/peta-gunungkidul/wms", {
        layers: "peta-gunungkidul:JALAN_LN_50K", // Ganti dengan layer yang sesuai
        format: "image/png",
        transparent: true,
        attribution: "GeoPortal Sleman WMS Layer - Jalan",
    });

    // Fungsi untuk menambahkan WFS layer menggunakan Leaflet (tanpa L.Geoserver.js)
    function addWFSLayer() {
        var wfsUrl = "http://localhost:8080/geoserver/pg_web/ows?service=WFS&version=1.1.0&request=GetFeature&typeName=pg_web:513284_Kecamatan_GK&outputFormat=application/json";

        // Ambil data WFS dalam format GeoJSON
        $.getJSON(wfsUrl, function(data) {
            var geojsonLayer = L.geoJSON(data, {
                style: {
                    color: "black",
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.3,
                    fillColor: "red",
                },
                onEachFeature: function(feature, layer) {
                    var popupContent = "<strong>Desa:</strong> " + (feature.properties.DESAIN || "Nama desa tidak tersedia") +
                        "<br><strong>Jenis Bangunan:</strong> " + (feature.properties.TYPE || "Tidak ada informasi jenis bangunan") +
                        "<br><strong>Keterangan:</strong> " + (feature.properties.DESCRIPTION || "Tidak ada keterangan");

                    layer.bindPopup(popupContent);
                }
            });
            geojsonLayer.addTo(map);
        });
    }

    // Tambahkan base map OpenStreetMap dan WMS layer ke peta
    osm.addTo(map);
    wmsLayer.addTo(map);
    jalan.addTo(map); // Menambahkan jalur (jalan) ke peta

    // Kontrol Layer untuk menambahkan kemampuan toggle layer
    var layerControl = L.control.layers({
        "OpenStreetMap": osm, // Base layer
    }, {
        "Batas Administrasi Desa (WMS)": wmsLayer, // WMS layer
        "Jalan (WMS)": jalan, // WMS layer untuk jalan
    }, {
        collapsed: false, // By default, the control layer is expanded
    }).addTo(map);

    // Add title to layer control
    var layerControlTitle = L.DomUtil.create('div', 'layer-control-title');
    layerControlTitle.innerHTML = 'Layer Control'; // Set the title
    layerControl.getContainer().insertBefore(layerControlTitle, layerControl.getContainer().firstChild);

    // Menambahkan WFS layer setelah base map dimuat
    addWFSLayer();

    // Menambahkan legenda untuk WMS layer di kiri bawah
    var layerLegend = L.control({
        position: "bottomleft" // Ganti posisi legenda ke kiri bawah
    });
    layerLegend.onAdd = function() {
        var div = L.DomUtil.create("div", "info legend legend-scrollable");

        // Add title for the legend
        div.innerHTML = '<div class="legend-title">Legenda</div>';

        // Add image for the legend
        div.innerHTML += `
            <img src="http://localhost:8080/geoserver/pg_web/wms?service=WMS&version=1.1.1&request=GetLegendGraphic&layer=pg_web:513284_Kecamatan_GK&format=image/png" alt="Legend">
            <span>Batas Administrasi Desa</span>
            <img src="http://localhost:8080/geoserver/peta-gunungkidul/wms?service=WMS&version=1.1.1&request=GetLegendGraphic&layer=peta-gunungkidul:JALAN_LN_50K&format=image/png" alt="Legend">
            <span>Jalan</span>
        `;

        return div;
    };
    layerLegend.addTo(map);

    // Menambahkan logo pada kontrol layer
    var logoUrl = "https://www.example.com/logo.png"; // Ganti dengan URL logo yang Anda inginkan
    var customControl = L.control({
        position: "topright"
    });
    customControl.onAdd = function() {
        var div = L.DomUtil.create("div", "custom-control");
        div.innerHTML = `<img src="${logoUrl}" alt="Logo">`;
        return div;
    };
    customControl.addTo(map);
</script>

</html>
