<?php

namespace App\UseCases\Line;

use Illuminate\Support\Facades\DB;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use App\Models\User;
use App\UseCases\Line\Share\ApiRequest;

class ReplyMusic
{
    // 1 minute ＝ 60000 msecond
    public const ONEMINUTE_TO_MSEC = 60000;
    // 60000 +- TOLERANCE_MSEC を許容する
    public const TOLERANCE_MSEC = 5000;

    /**
     * ReplyMessageを送信する
     *
     * @param mixed $event
     * @return \Illuminate\Http\Client\Response
     */
    public function invoke($event)
    {
        User::where('id', $event->getUserId())
            ->increment('used_count');

        $request_minutes = str_replace('分', '', $event->getText());
        $tracks = DB::table('tracks')
            ->select('external_url')
            ->where('isrc', 'like', 'jp%')
            ->whereBetween('duration_ms', [self::ONEMINUTE_TO_MSEC * $request_minutes - self::TOLERANCE_MSEC, self::ONEMINUTE_TO_MSEC * $request_minutes + self::TOLERANCE_MSEC])
            ->get();

        $api = new ApiRequest();
        if (count($tracks->all()) > 0) {
            return $api->replyMessage(
                $event->getReplyToken(),
                new TextMessageBuilder($tracks->random()->external_url)
            );
        } else {
            return $api->replyMessage(
                $event->getReplyToken(),
                new TextMessageBuilder("該当の曲が見つかりませんでした")
            );
        }
    }
}
