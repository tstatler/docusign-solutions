<?php

require_once 'vendor/autoload.php';
require_once 'vendor/docusign/esign-client/autoload.php';

function send_document_for_signing()
{
    #
    # The document $fileNamePath will be sent to be signed by <signer_name>

    # Obtain an OAuth access token from https://developers.docusign.com/oauth-token-generator
    $accessToken = '{ACCESS_TOKEN}';
    # Obtain your accountId from demo.docusign.com -- the account id is shown in the drop down on the
    # upper right corner of the screen by your picture or the default picture.
    $accountId = '{ACCOUNT_ID}';
    # Recipient Information:
    $signerName = '{SIGNER_NAME}';
    $signerEmail = '{SIGNER_EMAIL}';
    $fileNamePath = 'demo_documents/decoded.docx';
    $salary=123000;

    # The API base_path
    $basePath = 'https://demo.docusign.net/restapi';

    # Constants
    $appPath = getcwd();
    #
    # Step 1. The envelope definition is created.
    #         One signHere tab is added.
    #         The document path supplied is relative to the working directory
    #
    # Create the component objects for the envelope definition...
    $contentBytes = file_get_contents($appPath . "/" . $fileNamePath);
    $base64FileContent = base64_encode($contentBytes);

    $document = new \DocuSign\eSign\Model\Document([# Create the DocuSign document object
        'document_base64' => $base64FileContent,
        'name' => 'Salary action', # Can be different from the actual file name
        'file_extension' => 'docx', # Many different document types are accepted
        'document_id' => 1, # A label used to reference the doc
    ]);
    # Create the signer recipient model
    $signer = new \DocuSign\eSign\Model\Signer([# The signer
    'email' => $signerEmail, 'name' => $signerName,
        'recipient_id' => "1", 'routing_order' => "1",
    ]);
    # Create a sign_here tab (field on the document)
    $sign_here = new \DocuSign\eSign\Model\SignHere([# DocuSign SignHere field/tab
    'anchor_string' => '/sn1/', 'anchor_units' => 'pixels',
        'anchor_y_offset' => '10', 'anchor_x_offset' => '20',
    ]);
    # Create the legal and familiar text fields.
    # Recipients can update these values if they wish
    $text_legal = new \DocuSign\eSign\Model\Text([
        'anchor_string' => '/legal/', 'anchor_units' => 'pixels',
        'anchor_y_offset' => '-9', 'anchor_x_offset' => '5',
        'font' => "helvetica", 'font_size' => "size11",
        'bold' => 'true', 'value' => $signerName,
        'locked' => 'false', 'tab_id' => 'legal_name',
        'tab_label' => 'Legal name']);
    $text_familiar = new \DocuSign\eSign\Model\Text([
        'anchor_string' => '/familiar/', 'anchor_units' => 'pixels',
        'anchor_y_offset' => '-9', 'anchor_x_offset' => '5',
        'font' => "helvetica", 'font_size' => "size11",
        'bold' => 'true', 'value' => $signerName,
        'locked' => 'false', 'tab_id' => 'familiar_name',
        'tab_label' => 'Familiar name']);
    # Create the salary field. It should be human readable, so
    # add a comma before the thousands number, a currency indicator, etc.
    $salary_readable = '$' . number_format($salary);
    $text_salary = new \DocuSign\eSign\Model\Text([
        'anchor_string' => '/salary/', 'anchor_units' => 'pixels',
        'anchor_y_offset' => '-9', 'anchor_x_offset' => '5',
        'font' => "helvetica", 'font_size' => "size11",
        'bold' => 'true', 'value' => $salary_readable,
        'locked' => 'true', # mark the field as readonly
        'tab_id' => 'salary', 'tab_label' => 'Salary',
    ]);

    # Add the tabs model (including the sign_here tab) to the signer.
    # The Tabs object wants arrays of the different field/tab types
    $signer->settabs(new \DocuSign\eSign\Model\Tabs(
        ['sign_here_tabs' => [$sign_here],
            'text_tabs' => [$text_legal, $text_familiar, $text_salary]]));
    # Create an envelope custom field to save the "real" (numeric)
    # version of the salary
    $salary_custom_field = new \DocuSign\eSign\Model\TextCustomField([
        'name' => 'salary',
        'required' => 'false',
        'show' => 'true', # Yes, include in the CoC
        'value' => $salary]);
    $custom_fields = new \DocuSign\eSign\Model\CustomFields([
        'text_custom_fields' => [$salary_custom_field]]);

    # Create the top level envelope definition and populate it
    $envelope_definition = new \DocuSign\eSign\Model\EnvelopeDefinition([
        'email_subject' => "Please sign this document sent from the PHP SDK",
        'documents' => [$document],
        # The Recipients object wants arrays for each recipient type
        'recipients' => new \DocuSign\eSign\Model\Recipients(['signers' => [$signer]]),
        'status' => "sent", # Requests that the envelope be created and sent
        'custom_fields' => $custom_fields,
    ]);

    #
    #  Step 2. Create/send the envelope.
    #
    $config = new DocuSign\eSign\Configuration();
    $config->setHost($basePath);
    $config->addDefaultHeader("Authorization", "Bearer " . $accessToken);
    $apiClient = new DocuSign\eSign\ApiClient($config);
    $envelopeApi = new DocuSign\eSign\Api\EnvelopesApi($apiClient);
    $results = $envelopeApi->createEnvelope($accountId, $envelope_definition);
    return $results;
};

# Mainline
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $results = send_document_for_signing();
        ?>
<html lang="en">
    <body>
    <h4>Results</h4>
    <p>Status: <?=$results['status']?>, Envelope ID: <?=$results['envelope_id']?></p>
    </body>
</html>
        <?php
} catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        if ($e instanceof DocuSign\eSign\ApiException) {
            print("\nDocuSign API error information: \n");
            var_dump($e->getResponseBody());
        }
    }
    die();
}
# Since it isn't a POST, print the form:
?>
<html lang="en">
    <body>
        <form method="post">
            <input type="submit" value="Send document signature request!"
                style="width:21em;height:2em;background:#1f32bb;color:white;font:bold 1.5em arial;margin: 3em;"/>
        </form>
    </body>
</html>

