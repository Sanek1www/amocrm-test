<?php

declare(strict_types=1);

require './vendor/autoload.php';

$config = require_once './config.php';

$apiClient = new \AmoCRM\Client\AmoCRMApiClient(
    $config['client_id'],
    $config['client_secret'],
    $config['redirect_url']
);
$apiClient->setAccountBaseDomain($config['subdomain']);
$accessToken = $apiClient->getOAuthClient()->getAccessTokenByCode($config['auth_code']);

$apiClient->setAccessToken($accessToken);


$amoCRMService = new \App\AmoCRMService($apiClient);

$leadsCollection = $amoCRMService->createThreeRelatedEntities(10);
$multiSelectdModel = $amoCRMService->addMultiSelectFieldToLeads();
$amoCRMService->updateMultiselectValueInLeads($multiSelectdModel);
