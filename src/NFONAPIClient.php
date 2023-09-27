<?php

namespace NFONAPIClient;

class client
{
    /**
     * getAuthentication: get the authentication data for the NFON administration portal REST API.
     *
     * @param string $path request path, i.e. /api/status
     * @param string $apiSecret the API secret
     * @param string $method GET|POST|PUT|DELETE
     * @param string $body POST- and PUT-body
     * @param string $contentType "application/json" per default
     * @param string $date optional
     * @return array[$date, $contentType, $contentMD5, $signature]
     */
    public static function getAuthentication(string $path, string $apiSecret, string $method = 'GET', string $body = '', string $contentType = 'application/json', string $date = ''): array
    {
        date_default_timezone_set('GMT');
        if ($date == '') {
            $date = date("D, d M Y H:i:s T");
        }

        $contentMD5 = md5($body);

        $stringToSign = $method . "\n" . $contentMD5 . "\n" . $contentType . "\n" . $date . "\n" . $path;
        // print "stringToSign: $stringToSign\n";

        $mac = hash_hmac('sha1', $stringToSign, $apiSecret, true);
        // print "mac: $mac\n";

        $signature = base64_encode($mac);
        // print "signature: $signature\n";

        return [$date, $contentType, $contentMD5, $signature];
    }

    /**
     * Summary of request
     * @param string $apiBasePath
     * @param string $apiKey
     * @param string $signature
     * @param string $method
     * @param string $path
     * @param string $apiDate
     * @param string $contentMD5
     * @param string $body
     * @param int $contentLength
     * @param string $contentType
     * @param array $apiHeaders
     * @return bool|string
     */
    public static function request(
        string $apiBasePath,
        string $apiKey,
        string $signature,
        string $method,
        string $path,
        string $apiDate,
        string $contentMD5,
        string $body,
        int $contentLength,
        string $contentType,
        array $apiHeaders
    ): array {
        $ch = curl_init($apiBasePath . $path);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_PUT, true);
                break;
        }

        // headers
        $reqHeaders = [
            "Content-MD5: $contentMD5",
            "Content-Length: $contentLength",
            "Authorization: NFON-API $apiKey:$signature",
            "x-nfon-date: $apiDate",
            "date: $apiDate",
            "Content-Type: $contentType",
            // "Accept: application/json",
        ];
        $headers = array_merge($reqHeaders, $apiHeaders);
        // print "[INFO] headers is " . print_r($headers, 1) . "\n";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // print "\n\n\n";
        // print_r($ch);
        // print "\n\n\n";

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            print "[ERROR] $error_msg\n";
            return [$error_msg, false];
        }
        curl_close($ch);

        // print "Response is " . print_r($response, 1) . "\n";

        $res = json_decode($response, 1);

        // the data is not well formed, make a datamap
        $dataMap = [];
        // single result?
        if (isset($res['data'])) {
            foreach ($res['data'] as $d) {
                $dataMap[$d['name']] = $d['value'];
            }
        }
        // or multi result?
        else if (isset($res['items'])) {
            $idx = 0;
            foreach ($res['items'] as $i) {
                foreach ($i['data'] as $d) {
                    $dataMap[$idx][$d['name']] = $d['value'];
                }
                $idx++;
            }
        }

        $res['dataMap'] = $dataMap;

        return [$res, true];
    }
}
