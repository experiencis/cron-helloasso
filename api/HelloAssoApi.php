<?php
require_once 'ApiCore.php';

class HelloAssoAPi extends ApiCore {
    /**
     * Permet de récupérer le token de HelloAsso
     * @return string
     * @throws ErrorException
     */
    static public function getTokenHelloasso(): string
    {
        $header = ["Content-Type: application/x-www-form-urlencoded"];
        $response = self::callApi("POST", "https://api.helloasso.com/oauth2/token", $header, [
            "client_id" => $_ENV["HELLOASSO_CLIENT_ID"],
            "client_secret" => $_ENV["HELLOASSO_SECRET"],
            "grant_type" => "client_credentials"
        ]);

        if (empty($response["data"]))
            throw new ErrorException("Erreur lors de la récupération du token", $response["http_code"]);
        elseif ($response["http_code"] >= 400 && $response["http_code"] <= 500)
            throw new ErrorException($response["data"]["error_description"] ?? $response["data"]["message"] ?? "", $response["http_code"]);
        elseif (!isset($response["data"]["access_token"]))
            throw new ErrorException("Le token de Helloasso est manquant", $response["http_code"]);

        return $response["data"]["access_token"];
    }

    /**
     * Permet de récupérer la liste des adhérents de HelloAsso
     * @param string $accessToken
     * @param int $page
     * @return array
     * @throws ErrorException
     */
    static public function getAdherentsHelloasso(string $accessToken, int $page = 1): array
    {
        $header = ["Content-Type: application/x-www-form-urlencoded", "Authorization: Bearer ".$accessToken];
        $response = self::callApi("GET", "https://api.helloasso.com/v5/organizations/flupa/orders?from=2021-02-22&withDetails=true&formTypes=Membership&pageIndex=$page&pageSize=99", $header);

        if (empty($response["data"]))
            throw new ErrorException("Erreur lors de la récupération des adhérents", $response["http_code"]);
        elseif ($response["http_code"] >= 400 && $response["http_code"] <= 500)
            throw new ErrorException($response["data"]["error_description"] ?? $response["data"]["message"] ?? "", $response["http_code"]);

        return $response["data"]["data"];
    }
}