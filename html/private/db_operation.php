<?php

$dsn = 'mysql:host=mysql;dbname=' . Conf::getValue('db', 'db') . ';charset=utf8mb4';
$db = new PDO($dsn, Conf::getValue('db', 'user'), Conf::getValue('db', 'password'));

class dbUtill
{
    public static function insertMusicData($db, $uri, $artists, $popularity, $duration_ms, $isrc)
    {
        try {
            $sql = 'select * from music_data where uri = ?';
            $stmt = $db->prepare($sql);
            $stmt->execute([$uri]);
            $result = $stmt->fetchALL(PDO::FETCH_ASSOC);

            if (count($result) == 0) {
                $sql = 'insert into music_data(uri,artists,popularity,duration_ms,isrc,registdate,updatedate) VALUES(?,?,?,?,?,NOW(),NOW())';
                $stmt = $db->prepare($sql);
                $stmt->execute([$uri, $artists, $popularity, $duration_ms, $isrc]);
            }
        } catch (PDOException $e) {
            var_dump($e);
        }
    }

    public static function registerUser($db, $userid){
        try {
            $sql = "INSERT INTO users (userid, used_count, registdate, updatedate)
                    VALUES (?, 0, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE used_count = used_count + 1, updatedate = VALUES(updatedate)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$userid]);
        } catch (PDOException $e) {
            var_dump($e);
        }
    }

    public static function getMusic($db, $text, $jp_flag = false)
    {
        try {
            if ($jp_flag) {
                $minute = substr($text, 0, 1);
                $sql = 'select * from music_data where duration_ms between (60000 * ? - 5000) and (60000 * ? + 5000) and isrc like ' . '"jp%"';
                $stmt = $db->prepare($sql);
                $stmt->execute([$minute, $minute]);
                $result = $stmt->fetchALL(PDO::FETCH_ASSOC);
            } else {
                $num = mt_rand(80, 100);
                $sql = 'select * from music_data where popularity >= ?';
                $stmt = $db->prepare($sql);
                $stmt->execute([$num]);
                $result = $stmt->fetchALL(PDO::FETCH_ASSOC);
            }
            $response = $result[array_rand($result)];
            return $response['uri'];
        } catch (PDOException $e) {
            var_dump($e);
            return null;
        }
    }

    public static function deleteMoreThan30dayAgoData($db, $target_day)
    {
        try {
            $sql = "delete from music_data where date_format( ?, '%Y-%m-%d') >= date_format( registdate, '%Y-%m-%d')";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$target_day]);
            return $result;
        } catch (PDOException $e) {
            var_dump($e);
            return null;
        }
    }

}