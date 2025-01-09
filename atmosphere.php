<?php

$ch = curl_init();

$ip = $_SERVER['REMOTE_ADDR'];
$ip_api = "http://ip-api.com/json/" . $ip;

curl_setopt($ch, CURLOPT_URL, $ip_api);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_PROXY, 'www-cache:3128');
curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
} else {
    $data = json_decode($response);
    if (($data->status == "fail") || ($data->city != "Nancy")) {
        $ville = "Nancy";
        $lat = "48.692054";
        $lon = "6.184417";
    } else {
        $ville = $data->city;
        $lat = $data->lat;
        $lon = $data->lon;
    }
}

$api_meteo = "https://www.infoclimat.fr/public-api/gfs/xml?_ll=" . $lat . "," . $lon . "&_auth=ARsDFFIsBCZRfFtsD3lSe1Q8ADUPeVRzBHgFZgtuAH1UMQNgUTNcPlU5VClSfVZkUn8AYVxmVW0Eb1I2WylSLgFgA25SNwRuUT1bPw83UnlUeAB9DzFUcwR4BWMLYwBhVCkDb1EzXCBVOFQoUmNWZlJnAH9cfFVsBGRSPVs1UjEBZwNkUjIEYVE6WyYPIFJjVGUAZg9mVD4EbwVhCzMAMFQzA2JRMlw5VThUKFJiVmtSZQBpXGtVbwRlUjVbKVIuARsDFFIsBCZRfFtsD3lSe1QyAD4PZA%3D%3D&_c=19f3aa7d766b6ba91191c8be71dd1ab2";

curl_setopt($ch, CURLOPT_URL, $api_meteo);

$xml_content = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Erreur: ' . curl_error($ch);
} else {
    $xml = new SimpleXMLElement($xml_content);
}

curl_close($ch);

$xsl = new DOMDocument();
$xsl->load('meteo.xsl');


$xsltproc = new XSLTProcessor();
$xsltproc->importStylesheet($xsl);
$meteo_fragement = $xsltproc->transformToXml($xml);


$api_circulation = "https://carto.g-ny.org/data/cifs/cifs_waze_v2.json";

curl_setopt($ch,CURLOPT_URL, $api_circulation);

$incidents = json_decode(curl_exec($ch));

$api_adresse = "https://api-adresse.data.gouv.fr/search/?q=boulevard+charlemagne+nancy";
curl_setopt($ch, CURLOPT_URL, $api_adresse);
$adresse = json_decode(curl_exec($ch))->features[0]->geometry->coordinates;

$marqueurs_fragement = "";

$marqueurs_fragement .= "L.marker([$adresse[1], $adresse[0]]).addTo(map).bindPopup('Boulevard Charlemagne');";

foreach ($incidents->incidents as $incident) {
    $lat_lon = explode(" ", $incident->location->polyline);

    $dateDeb = new DateTime($incident->starttime);
    $formattedDateDeb = $dateDeb->format('d-m-Y');

    $dateFin = new DateTime($incident->endtime);
    $formattedDateFin = $dateFin->format('d-m-Y');

    $popupContent = "<b>Type:</b> $incident->type<br><b>Description:</b> $incident->description<br><b>Start Time:</b>$formattedDateDeb<br><b>End Time:</b> $formattedDateFin    ";
    $popupContent = addslashes($popupContent);
    $marqueurs_fragement .= "L.marker([$lat_lon[0] , $lat_lon[1]]).addTo(map).bindPopup('$popupContent');";
}

$api_air = "https://services3.arcgis.com/Is0UwT37raQYl9Jj/arcgis/rest/services/ind_grandest/FeatureServer/0/query?where=1%3D1&objectIds=&time=&geometry=&geometryType=esriGeometryEnvelope&inSR=&spatialRel=esriSpatialRelIntersects&resultType=none&distance=0.0&units=esriSRUnit_Meter&returnGeodetic=false&outFields=*&returnGeometry=true&featureEncoding=esriDefault&multipatchOption=xyFootprint&maxAllowableOffset=&geometryPrecision=&outSR=&datumTransformation=&applyVCSProjection=false&returnIdsOnly=false&returnUniqueIdsOnly=false&returnCountOnly=false&returnExtentOnly=false&returnQueryGeometry=false&returnDistinctValues=false&cacheHint=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&having=&resultOffset=&resultRecordCount=&returnZ=false&returnM=false&returnExceededLimitFeatures=true&quantizationParameters=&sqlFormat=none&f=pjson&token=";
curl_setopt($ch, CURLOPT_URL, $api_air);
$air = json_decode(curl_exec($ch));
$air_length = count($air->features);
$air = $air->features[$air_length -1 ]->attributes->lib_qual;

$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Interoperabilité</title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="atmosphere.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin="">
    </script>
</head>
<body>
    <h1>Météo à $ville</h1>
    $meteo_fragement
    <h1>Qualité de l'air</h1>
    <p class="qualite-air">$air</p>
    <h1>Incidents de circulation</h1>
    <div id="map" class="map"></div>
    <script>
        var map = L.map('map').setView([$lat, $lon], 13);
    
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
        
        $marqueurs_fragement
    </script>
    <h1>Sources</h1>
    <ul>
        <li><a href="http://ip-api.com/json/$ip">Localisation</a></li>
        <li><a href="https://www.infoclimat.fr/public-api/gfs/xml?_ll=$lat,$lon&_auth=ARsDFFIsBCZRfFtsD3lSe1Q8ADUPeVRzBHgFZgtuAH1UMQNgUTNcPlU5VClSfVZkUn8AYVxmVW0Eb1I2WylSLgFgA25SNwRuUT1bPw83UnlUeAB9DzFUcwR4BWMLYwBhVCkDb1EzXCBVOFQoUmNWZlJnAH9cfFVsBGRSPVs1UjEBZwNkUjIEYVE6WyYPIFJjVGUAZg9mVD4EbwVhCzMAMFQzA2JRMlw5VThUKFJiVmtSZQBpXGtVbwRlUjVbKVIuARsDFFIsBCZRfFtsD3lSe1QyAD4PZA%3D%3D&_c=19f3aa7d766b6ba91191c8be71dd1ab2">Météo</a></li>
        <li><a href="https://carto.g-ny.org/data/cifs/cifs_waze_v2.json">Circulation</a></li>
        <li><a href="https://api-adresse.data.gouv.fr/search/?q=boulevard+charlemagne+nancy">Adresse</a></li>
        <li><a href="https://services3.arcgis.com/Is0UwT37raQYl9Jj/arcgis/rest/services/ind_grandest/FeatureServer/0/query?where=1%3D1&objectIds=&time=&geometry=&geometryType=esriGeometryEnvelope&inSR=&spatialRel=esriSpatialRelIntersects&resultType=none&distance=0.0&units=esriSRUnit_Meter&returnGeodetic=false&outFields=*&returnGeometry=true&featureEncoding=esriDefault&multipatchOption=xyFootprint&maxAllowableOffset=&geometryPrecision=&outSR=&datumTransformation=&applyVCSProjection=false&returnIdsOnly=false&returnUniqueIdsOnly=false&returnCountOnly=false&returnExtentOnly=false&returnQueryGeometry=false&returnDistinctValues=false&cacheHint=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&having=&resultOffset=&resultRecordCount=&returnZ=false&returnM=false&returnExceededLimitFeatures=true&quantizationParameters=&sqlFormat=none&f=pjson&token=">Qualité de l'air</a></li>
    </ul>
</body>
</html>
HTML;

echo $html;
