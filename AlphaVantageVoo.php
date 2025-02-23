<?php

class AlphaVantageVoo {
    /* API KEY 申請連結（免費）https://www.alphavantage.co/support/#api-key */
    private static string $apiKey = '';
    /* 本地資料存放位置 */
    private static string $localDataPath = './alpha_vantage_voo.json';
    /* 查詢代號 */
    private static string $symbol = 'VOO';

    /* 常數 */
    /* 欄位 收盤價 */
    const FIELD_CLOSE = 'close';
    /* 欄位 資料日期 */
    const FIELD_DATE =  'date';
    /* 層二：Meta Data */
    const SUB_META_DATA = 'Meta Data';
    /* 層三：Meta Data -> 3. Last Refreshed（日期: Y-m-d）*/
    const META_DATA_LAST_REFRESHED = '3. Last Refreshed';
    /* 層二：Time Series (Daily) */
    const SUB_TIME_SERIES = 'Time Series (Daily)';
    /* Time Series 之下是一個以「日期」為鍵值對應到每日資料「物件」的「字典」結構 */
    const TIME_SERIES_OPEN =    '1. open';
    const TIME_SERIES_HIGH =    '2. high';
    const TIME_SERIES_LOW =     '3. low';
    const TIME_SERIES_CLOSE =   '4. close';
    const TIME_SERIES_VOLUME =  '5. volume';

    /* 公開方法 */
    /* 取得收盤價 */
    public static function getClose(): int|null {
        /* 收盤價 讀取本地資料 */
        $close = self::load_local_data();
        if (is_null($close)) {
            /* 讀取線上資料 */
            $close = self::load_online_data();
        }
        return $close;
    }

    private static function load_online_data(): int|null {
        try {
            $json = file_get_contents('https://www.alphavantage.co/query?function=TIME_SERIES_DAILY&datatype=json&symbol=' . self::$symbol . '&apikey=' . self::$apiKey);
            $data = json_decode($json, true);
            
            $json[self::FIELD_DATE] = date('Y-m-d');
            /* 寫入本地資料 */
            file_put_contents(self::$localDataPath, $json);

        } finally {
            return null;
        }
    }

    private static function load_local_data(): float|null {
        try {
            if (!file_exists(self::$localDataPath)) {
                return null;
            }

            /* 讀取本地資料 */
            $localData = json_decode(file_get_contents(self::$localDataPath));
            /* 判斷資料日期是否為最新資料 */
            if ($localData[self::FIELD_DATE] == date('Y-m-d')) {
                $lastRefreshed = $localData[self::SUB_META_DATA][self::META_DATA_LAST_REFRESHED];
                return self::find_latest_time_series_close($lastRefreshed, $localData[self::SUB_TIME_SERIES]);
            }

        } catch (Exception $e) {
            print_r($e->getTraceAsString());
            return null;
        } finally {
            return null;
        }
    }

    private function find_latest_time_series_close(string $lastRefreshed, array $timeSeries): float|null {
        try {
            if (key_exists($lastRefreshed, $timeSeries)) {
                return floatval($timeSeries[$lastRefreshed][self::TIME_SERIES_CLOSE]);
            }
        } finally {
            return null;
        }
    }
}

test();

function test() {
    $alphaVantageVoo = new AlphaVantageVoo();
    // var_dump($alphaVantageVoo->local_data_file_create_public());
}
