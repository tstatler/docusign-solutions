<?php

require_once('vendor/autoload.php');
require_once('vendor/docusign/esign-client/autoload.php');

function get_envelope_form_data(){
    $accessToken = '{ACCESS_TOKEN}';
    $accountId = '{ACCOUNT ID}';
    $envelopeId = '{ENVELOPE ID}';

    # The API base_path
    $basePath = 'https://demo.docusign.net/restapi';

    # configure the EnvelopesApi object
    $config = new DocuSign\eSign\Configuration();
    $config->setHost($basePath);
    $config->addDefaultHeader("Authorization", "Bearer " . $accessToken);
    $apiClient = new DocuSign\eSign\ApiClient($config);
    $envelopeApi = new DocuSign\eSign\Api\EnvelopesApi($apiClient);

    #
    #  Request envelope form data:
    #
    $results = $envelopeApi->getFormData($accountId, $envelopeId);
    return $results;
};

# Mainline
try {
    $results = get_envelope_form_data();
    ?>
<html lang="en">
    <body>
        <h4>Results of EnvelopesApi.getFormdData()</h4>
        <p><code><pre><?= print_r ($results) ?></pre></code></p>
    </body>
</html>
    <?php
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
    if ($e instanceof DocuSign\eSign\ApiException) {
        print ("\nDocuSign API error information: \n");
        var_dump ($e->getResponseBody());
    }
}
