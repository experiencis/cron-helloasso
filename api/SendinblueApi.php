<?php
require_once 'ApiCore.php';

class SendinblueApi extends ApiCore {
    /**
     * Permet de récupérer la liste des contacts de l'association sur Sendinblue
     * @throws ErrorException
     */
    static public function getContactsSendinblue(): array
    {
        $header = ["Accept: application/json", "api-key: ".$_ENV["SENDINBLUE_KEY"]];
        $response = self::callApi("GET", "https://api.sendinblue.com/v3/contacts", $header);

        if (empty($response["data"]))
            throw new ErrorException("Erreur lors de la récupération des contacts Sendinblue", $response["http_code"]);
        elseif ($response["http_code"] >= 400 && $response["http_code"] <= 500)
            throw new ErrorException($response["data"]["error_description"] ?? $response["data"]["message"] ?? "", $response["http_code"]);
        elseif (empty($response["data"]["contacts"]))
            throw new ErrorException("La liste des contacts récupérées via Sendinblue est vide", $response["http_code"]);

        return $response["data"]["contacts"];
    }

    /**
     * Permet de créer un contact sur Sendinblue
     * @param array $data
     * @return void
     * @throws ErrorException
     */
    static public function createContactSendinblue(array $data)
    {
        $header = ["Accept: application/json", "Content-Type: application/json", "api-key: ".$_ENV["SENDINBLUE_KEY"]];
        $response = self::callApi("POST", "https://api.sendinblue.com/v3/contacts", $header, json_encode($data));

        if (empty($response["data"]))
            throw new ErrorException("Erreur lors de la création du contact ".$data["email"]." sur Sendinblue", $response["http_code"]);
        elseif ($response["http_code"] >= 400 && $response["http_code"] <= 500)
            throw new ErrorException($response["data"]["error_description"] ?? $response["data"]["message"] ?? "", $response["http_code"]);
    }

    /**
     * Envoie un mail de renouvellement au contact
     * @param array $data
     * @return void
     * @throws ErrorException
     */
    static public function sendEmailRenouvellementSendinblue(array $data)
    {
        $header = ["Accept: application/json", "Content-Type: application/json", "api-key: ".$_ENV["SENDINBLUE_KEY"]];
        $response = self::callApi("POST", "https://api.sendinblue.com/v3/smtp/email", $header, json_encode($data));

        if (empty($response["data"]))
            throw new ErrorException("Erreur lors de l'envoie du mail de renouvellement à ".$data["email"], $response["http_code"]);
        elseif ($response["http_code"] >= 400 && $response["http_code"] <= 500)
            throw new ErrorException($response["data"]["error_description"] ?? $response["data"]["message"] ?? "", $response["http_code"]);
    }
}