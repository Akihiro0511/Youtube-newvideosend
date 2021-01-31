<?php

class NewVideoCheck
{
    private function apikey()
    {
        /* ★Youtube APIキーを記載 */
        $apikey = "*******************************";
        return $apikey;
    }

    private function chatworks_send($videoid, $title)
    {
        // メッセージ（Youtubeのタイトル、URL）
        $msg = array(
            'body' => $title."\n".'https://www.youtube.com/watch?v='.$videoid
            );
    
        // ★任意のChatworks APIキーを指定
        $token = '*******************************';
        // ★任意のChatworks room_idを指定
        $room = '*******************************';

        
        $ch = curl_init();
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array('X-ChatWorkToken: '.$token)
        );
        curl_setopt($ch, CURLOPT_URL, "https://api.chatwork.com/v2/rooms/".$room."/messages");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($msg, '', '&'));
        $result = curl_exec($ch);
        curl_close($ch);
    }

    public function mysql_connect()
    {
        /* Mysql接続(サーバのIPアドレス、ユーザ情報、パスワードを記載してDBにアクセス) */
        // ★MySQL 任意のパスワード
        $link = mysqli_connect('127.0.0.1', 'root', '*******************************');
        if (!$link) {
            die("エラー：DBに接続できません");
        }


        /* テーブル選択 */
        $db_selected = mysqli_select_db($link, 'NEW_VIDEO_DATA');
        if (!$db_selected) {
            die("エラー：テーブルに接続できません");
        }

        return $link;
    }

    public function video_info($link)
    {
        /* ★channelIdには任意のチャンネルIDを記載する */
        $view_info_url = "https://www.googleapis.com/youtube/v3/search?part=snippet&channelId=*******************************&maxResults=1&order=date&type=video";
        /* YoutubeAPIキーを取得しURLに連結させる */
        $view_info_url .= "&key=".$this->apikey();

        /* Youtube APIを使い動画の情報取得 */
        $json = file_get_contents($view_info_url);
        $json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
        $arr = json_decode($json, true);
        $videoid = $arr["items"][0]["id"]["videoId"];
        $title = $arr["items"][0]["snippet"]["title"];
        $videoinfo[0] = $videoid;
        $videoinfo[1] = $title;

        /* テーブル内にある動画IDと比較し、違っていたらチャットを送信 */
        $select_sql = "select * from new_video_check limit 1";
        $select = mysqli_query($link, $select_sql);
        $tbl = mysqli_fetch_array($select);
        $new_videoid = $tbl[0];
        // 最新動画であればチャットを送信
        if($videoid != $new_videoid) {
        $this->chatworks_send($videoid, $title);
        var_dump($videoinfo);
        }

        return $videoinfo;
    }

    public function video_register($link, $videoinfo)
    {
        $delete_sql = "delete from new_video_check";
        $delete = mysqli_query($link, $delete_sql);

        $register_sql = "insert into new_video_check values('".$videoinfo[0]."', '".$videoinfo[1]."')";
        $register = mysqli_query($link, $register_sql);
    }
}

// Mysqlのデータベースへアクセスする
$newvideocheck = new NewVideoCheck();
$link = $newvideocheck->mysql_connect();

// チャンネル内の最新動画を調べる
$videoinfo = $newvideocheck->video_info($link);

// チャンネル内で最新動画あげたら最新動画を登録する
$newvideocheck->video_register($link, $videoinfo);
