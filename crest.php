<?php
class CRest
{
    const BATCH_SIZE = 50;

    protected static $clientId = 'local.66e95a625d0773.28570757'; // Thay bằng CLIENT_ID của bạn
    protected static $clientSecret = 'FQZkGDCOSfzYW9XDqkuU4h3X2hNqQHr4t2Q6gsmySSpaM6dMbR'; // Thay bằng CLIENT_SECRET của bạn

    public static function call($method, $params = [])
    {
        $auth = self::getAuth();

        if (!$auth) {
            return ['error' => 'No auth data'];
        }

        $queryUrl = $auth['client_endpoint'] . $method;
        $queryData = http_build_query(array_merge($params, ['auth' => $auth['access_token']]));

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $queryUrl,
            CURLOPT_POSTFIELDS => $queryData,
        ]);

        $result = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($result, true);

        // Kiểm tra xem token có hết hạn không
        if (isset($result['error']) && $result['error'] == 'expired_token') {
            if (self::getNewAuth()) {
                return self::call($method, $params);
            }
        }

        return $result;
    }
   // Phương thức getAuth() đã được cập nhật
   protected static function getAuth()
   {
       if (isset($_SESSION['AUTH'])) {
           return $_SESSION['AUTH'];
       } elseif (isset($_REQUEST['AUTH_ID']) && isset($_REQUEST['REFRESH_ID'])) {
           $_SESSION['AUTH'] = [
               'access_token' => $_REQUEST['AUTH_ID'],
               'refresh_token' => $_REQUEST['REFRESH_ID'],
               'client_endpoint' => ($_REQUEST['PROTOCOL'] == 1 ? 'https' : 'http') . '://' . $_REQUEST['DOMAIN'] . '/rest/',
           ];
           return $_SESSION['AUTH'];
       } else {
           return false;
       }
   }

    public static function callBatch($calls = [])
    {
        $results = [];
        $auth = self::getAuth();

        if (!$auth) {
            return ['error' => 'No auth data'];
        }

        $chunks = array_chunk($calls, self::BATCH_SIZE, true);

        foreach ($chunks as $chunk) {
            $batch = [];
            foreach ($chunk as $key => $call) {
                $batch['cmd'][$key] = $call['method'] . '?' . http_build_query($call['params']);
            }

            $result = self::call('batch', $batch);

            if (isset($result['result'])) {
                $results = array_merge($results, $result['result']['result']);
            }
        }

        return $results;
    }

    protected static function getNewAuth()
    {
        if (!isset($_SESSION['AUTH']['refresh_token'])) {
            return false;
        }

        $queryUrl = 'https://oauth.bitrix.info/oauth/token/';
        $queryData = http_build_query([
            'grant_type' => 'refresh_token',
            'client_id' => self::$clientId,
            'client_secret' => self::$clientSecret,
            'refresh_token' => $_SESSION['AUTH']['refresh_token'],
        ]);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $queryUrl,
            CURLOPT_POSTFIELDS => $queryData,
        ]);

        $result = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($result, true);

        if (isset($result['access_token'])) {
            $_SESSION['AUTH'] = $result;
            return true;
        } else {
            return false;
        }
    }
}
?>
