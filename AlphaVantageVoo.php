<?php
/* API KEY 申請連結（免費）https://www.alphavantage.co/support/#api-key */
/*
 * 用法:直接用 require 或 include 來載入本類別
 * 呼叫
 * $close = AlphaVantageVoo::getClose();
 * 可以直接取得最新的資料並回傳收盤價(float, 浮點數)
 */

class AlphaVantageVoo {
    private static string $apiKey = '';
    /* 本地資料存放位置 */
    private static string $localDataPath = './alpha_vantage_voo.json';
    /* 查詢代號 */
    private static string $symbol = 'VOO';

    /* 常數 */
    /* 欄位 資料日期 */
    private const FIELD_FETCH_DATE =  'fetch_date';
    /* 層二：Meta Data */
    private const SUB_META_DATA = 'Meta Data';
    /* 層三：Meta Data -> 3. Last Refreshed（日期: Y-m-d）*/
    private const META_DATA_LAST_REFRESHED = '3. Last Refreshed';
    /* 層二：Time Series (Daily) */
    private const SUB_TIME_SERIES = 'Time Series (Daily)';
    /* Time Series 之下是一個以「日期」為鍵值對應到每日資料「物件」的「字典」結構 */
    private const TIME_SERIES_OPEN =    '1. open';
    private const TIME_SERIES_HIGH =    '2. high';
    private const TIME_SERIES_LOW =     '3. low';
    private const TIME_SERIES_CLOSE =   '4. close';
    private const TIME_SERIES_VOLUME =  '5. volume';

    /* 公開方法 */
    /* 取得收盤價 */
    public static function getClose(): float|null {
        /* 收盤價 讀取本地資料 */
        $close = self::load_local_data();
        if ($close === null) {
            /* 讀取線上資料 */
            $close = self::load_online_data();
        }
        return $close;
    }

    private static function load_online_data(): float|null {
        try {
            $close = null;
            $json = file_get_contents('https://www.alphavantage.co/query?function=TIME_SERIES_DAILY&datatype=json&symbol=' . self::$symbol . '&apikey=' . self::$apiKey);
            $data = json_decode($json, true);
            /* 註記本日日期 */
            $data[self::FIELD_FETCH_DATE] = date('Y-m-d');

            /* 寫入本地資料 */
            file_put_contents(self::$localDataPath, json_encode($data));

            $lastRefreshed = $data[self::SUB_META_DATA][self::META_DATA_LAST_REFRESHED];
            $close = self::find_latest_time_series_close($lastRefreshed, $data[self::SUB_TIME_SERIES]);

        } finally {
            return $close;
        }
    }

    private static function load_local_data(): float|null {
        try {
            $close = null;
            
            if (!file_exists(self::$localDataPath)) {
                return null;
            } else {
                /* 讀取本地資料 */
                $localData = json_decode(file_get_contents(self::$localDataPath), true);
                /* 判斷資料日期是否為最新資料 */
                if ($localData[self::FIELD_FETCH_DATE] == date('Y-m-d')) {
                    $lastRefreshed = $localData[self::SUB_META_DATA][self::META_DATA_LAST_REFRESHED];
                    $close = self::find_latest_time_series_close($lastRefreshed, $localData[self::SUB_TIME_SERIES]);
                }
            }

        } finally {
            return $close;
        }
    }

    private static function find_latest_time_series_close(string $lastRefreshed, array $timeSeries): float|null {
        $close = null;
        try {
            if (key_exists($lastRefreshed, $timeSeries)) {
                $close = floatval($timeSeries[$lastRefreshed][self::TIME_SERIES_CLOSE]);
            }
            /* 依鍵值由大到小排序 */
            ksort($timeSeries);
            /* 由於 ksort() 排序後，陣列順序便會是 newest -> oldest */
            while (!empty($timeSeries)) {
                /* 使用 array_shift() 從第 0 位彈出元素 */
                $daily = array_shift($timeSeries);
                $close = floatval($daily[self::TIME_SERIES_CLOSE]);
            }
        } finally {
            return $close;
        }
    }
}
