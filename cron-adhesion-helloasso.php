<?php
require __DIR__."/vendor/autoload.php";
require './api/SendinblueApi.php';
require './api/HelloAssoApi.php';
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$logger = new Logger("logs");
$logger->pushHandler(new StreamHandler(__DIR__."/logs/dev.log", Logger::DEBUG));

/**
 * Main
 */
try {
    $accessToken = HelloAssoAPi::getTokenHelloasso();

    $oneYearOneMonth = new DateTime('-11 months');
    $oneYearEightDays = new DateTime('-1 year +7 days');

    $contactsHelloasso = [];
    $page = 1;

    do {
        $adherents = HelloAssoAPi::getAdherentsHelloasso($accessToken, $page);

        foreach ($adherents as $adherent) {
            $lastAdhesionIndex = count($adherent["items"]) - 1;
            $adhesion = $adherent["items"][$lastAdhesionIndex];

            // Si l'adhésion n'a pas été validée on passe à l'adhérent suivant
            if ($adhesion["state"] !== "Processed" || !isset($adhesion["customFields"])) {
                continue;
            }

            $customFieldEmailIndex = array_search("Email", array_column($adhesion["customFields"], "name"));
            $email = $adhesion["customFields"][$customFieldEmailIndex]["answer"];

            // Renouvellement à faire dans un mois
            if (((new DateTime($adherent["date"]))->format("Y-m-d") === $oneYearOneMonth->format("Y-m-d")))
                $renouvellementTiming = "month";
            // Renouvellement à faire dans une semaine
            elseif ((new DateTime($adherent["date"]))->format("Y-m-d") === $oneYearEightDays->format("Y-m-d"))
                $renouvellementTiming = "week";
            else
                continue;

            $contactsHelloasso[] = [
                "email" => $email,
                "firstname" => $adhesion["user"]["firstName"],
                "lastname" => $adhesion["user"]["lastName"],
                "renouvellementTiming" => $renouvellementTiming
            ];
        }

        $page += 1;
    } while (count($adherents) > 0);

    $nbrMailsSent = 0;
    $contactsSendinblue = SendinblueApi::getContactsSendinblue();

    foreach ($contactsHelloasso as $contact) {
        $contactSendinblueAlreadyAddedIndex = in_array($contact["email"], array_column($contactsSendinblue, "email"));

        // Si l'utilisateur n'est pas présent sur Sendinblue on l'ajoute
        if (!$contactSendinblueAlreadyAddedIndex) {
            $dataContact = [
                "attributes" => [
                    "NOM" => $contact["lastname"],
                    "PRENOM" => $contact["firstname"],
                ],
                "email" => $contact["email"],
                "updateEnabled" => false,
            ];
            try {
                SendinblueApi::createContactSendinblue($dataContact);
            } catch (ErrorException|Exception $e) {
                $logger->error($e);
                continue;
            }
        }

        // On envoie le mail de renouvellement
        $mailContent = [
            "to" => [["email" => $contact["email"], "name" => $contact["lastname"]]],
            "templateId" => (int) $_ENV["SENDINBLUE_TEMPLATE_ID"],
            "params" => [
                "timing" => $contact["renouvellementTiming"] === "month" ? "un mois" : "une semaine"
            ]
        ];
        try {
            SendinblueApi::sendEmailRenouvellementSendinblue($mailContent);
            $nbrMailsSent++;
        } catch (ErrorException|Exception $e) {
            $logger->error($e);
            continue;
        }
    }

    if ($nbrMailsSent > 0) {
        $logger->info($nbrMailsSent." mails ont été envoyés");
    }
} catch (ErrorException|Exception $e) {
    $logger->error($e);
}