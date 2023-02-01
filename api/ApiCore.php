<?php

class ApiCore {
    /**
     * Permet de faire un appel à une API
     * @param string $method
     * @param string $url
     * @param null|array|string $payload
     * @param array|null $header
     * @return array
     * @throws ErrorException
     */
    static protected function callApi(string $method, string $url, array $header = [], $payload = null): array
    {
        $curl = curl_init();

        $curlData = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $header,
        ];

        if (!empty($payload) && is_array($payload)) {
            $stringParams = [];
            foreach($payload as $param => $value) {
                $stringParams[] = "$param=$value";
            }
            $payload = implode('&', $stringParams);
        }
        if (strlen($payload) > 0)
            $curlData[CURLOPT_POSTFIELDS] = $payload;

        curl_setopt_array($curl, $curlData);
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_error($curl))
            throw new ErrorException(curl_error($curl), $httpCode);

        curl_close($curl);

        return [
            "data" => json_decode($response, true),
            "http_code" => $httpCode,
        ];
    }
}