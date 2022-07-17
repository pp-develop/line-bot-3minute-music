<?php
require(__DIR__ . './../vendor/autoload.php');
include(__DIR__ . '/include.php');

function main($db)
{
    dbUtill::deleteMusicData($db);
    $search_result = execSearchApi(getRandomSearchQuery(), 'track', ['market' => 'JP']);
    saveTrack($db, $search_result->tracks);

    $next_url = $search_result->tracks->next;
    while (!is_null($next_url)) {
        $next_url_result = execURL($next_url);
        $result_obj = json_decode(json_encode($next_url_result));
        saveTrack($db, $result_obj->tracks);

        $next_url = $result_obj->tracks->next;
    }
    $db = null;
}

function execSearchApi($q, $type, $option)
{
    $session = new SpotifyWebAPI\Session(
        Env::getValue('SPOTIFY_CLIENT_ID'),
        Env::getValue('SPOTIFY_CLIENT_SECRET')
    );
    $api = new SpotifyWebAPI\SpotifyWebAPI();
    $session->requestCredentialsToken();
    $accessToken = $session->getAccessToken();
    $api->setAccessToken($accessToken);

    $result = $api->search($q, $type, $option);
    return $result;
}

function execURL($url)
{
    $session = new SpotifyWebAPI\Session(
        Env::getValue('SPOTIFY_CLIENT_ID'),
        Env::getValue('SPOTIFY_CLIENT_SECRET')
    );
    $session->requestCredentialsToken();
    $accessToken = $session->getAccessToken();

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $result = json_decode($response, true);
    return $result;
}

function getRandomSearchQuery()
{
    $str = 'abcdefghijklmnopqrstuvwxyz';
    $shuffled_str = substr(str_shuffle($str), 0, 1);

    $num = '01';
    $shuffled_num = substr(str_shuffle($num), 0, 1);

    $randomSearch = '';
    switch ($shuffled_num) {
        case 0:
            $randomSearch = $shuffled_str . '%';
            break;
        case 1:
            $randomSearch = '%' . $shuffled_str . '%';
            break;
    }
    return $randomSearch;
}

function saveTrack($db, $tracks)
{
    $items = $tracks->items;
    if (is_null($items)) {
        return;
    }

    foreach ($items as $item) {
        if (
            isIsrcJp($item->external_ids->isrc)
            && validateTime($item->duration_ms)
        ) {
            $artists = '';
            $uri = $item->external_urls->spotify;
            foreach ($item->artists as $artist) {
                $artists .= $artist->name . ',';
            }
            $popularity = $item->popularity;
            $duration_ms = $item->duration_ms;
            $isrc = $item->external_ids->isrc;
            dbUtill::insertMusicData($db, $uri, rtrim($artists, ','), $popularity, $duration_ms, $isrc);
        }
    }
}

function validateTime($val)
{
    // 1min = 60000ms
    $ms_list = [60000, 120000, 180000, 240000, 300000, 360000, 420000, 480000];
    foreach ($ms_list as $ms) {
        if (($ms - 5000) <= $val && $val <= ($ms + 5000)) {
            return true;
        }
    }
    return false;
}

function isIsrcJp($isrc)
{
    if (substr($isrc, 0, 2) === 'JP') {
        return true;
    }
    return false;
}

main($db);
