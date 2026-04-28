<?php
function getWisataData() {
    $apiUrl = "https://webapi.bps.go.id/v1/api/view/domain/3519/model/statictable/lang/ind/id/719/key/eb4deec1dc4d8187872cfa1ee64576e5";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $data = json_decode($response, true);
        // BPS API usually stores the table body in this line
        return $data['data']['tbody'] ?? [];
    }
    return [];
}
?>