<?php
require "/opt/php56-edomi/src/aws-sdk-php/aws.phar";

date_default_timezone_set('Europe/Berlin');

logging('Starting Amazon Polly Script');

// if command line parameter is missing return error
if (count($argv) != 2) {
    echo base64_encode(serialize(json_encode(array(
        1 => "ERROR",
        2 => '',
        3 => '',
        4 => 0
    ))));
    die();
}
 
logging($argv[1]);

// read json command line parameter
$params = json_decode(unserialize(base64_decode($argv[1])), true);

// create AWS credentials
$credentials = new Aws\Credentials\Credentials($params['awsAccessKeyId'], $params['awsSecretKey']);

try {
// create polly client instance
$client = new Aws\Polly\PollyClient(array(
    'version' => 'latest',
    'debug' => false,
    'credentials' => $credentials,
    'region' => $params['awsRegion']
));

// generate audio via polly API call
$response = $client->synthesizeSpeech(array(
    'OutputFormat' => $params['outputFormat'],
    'Text' => $params['text'],
    'TextType' => $params['textType'],
    'VoiceId' => $params['voiceId']
));
} catch (Exception $e) {
    logging('Error: '.$e->getMessage());
    echo base64_encode(serialize(json_encode(array(
        1 => "ERROR",
        2 => '',
        3 => '',
        4 => 0
    ))));
    die();
}

// retrieve audio data from polly
if ($response->get('Error'))
    logging("ERROR");
else
    logging("SUCCESS");

$audioData = $response->get('AudioStream')->getContents();
$numChars = $response->get('RequestCharacters');

logging('NumChars: ' . $numChars);

// Writing audio data to file
if ($params['filename'] == 'auto')
    $filename = date("Y-m-d_H-i-s") . '-AWS-Polly';
else
    $filename = $params['filename'];

// add filename extension
if ($params['outputFormat'] == 'ogg_vorbis')
    $ext = 'ogg';
else
    $ext = $params['outputFormat'];
$filename .= '.' . $ext;

// set full filename incl. path
$filename = preg_replace('/[^A-Za-z0-9 _ .-]/', '', $filename);
$path = '/usr/local/edomi/www/data/tmp';
$fullFilename = $path . '/' . $filename;

// write audio file
$file_ok = file_put_contents($fullFilename, $audioData);

// return result
if ($file_ok === FALSE) {
    $result = base64_encode(serialize(json_encode(array(
        1 => 'ERROR',
        2 => '',
        3 => '',
        4 => 0
    ))));
} else {
    $result = base64_encode(serialize(json_encode(array(
        1 => 'OK',
        2 => $fullFilename,
        3 => 'http://' . $params['ip'] . '/data/tmp/' . $filename,
        4 => $numChars
    ))));
}

echo $result;

function logging($message)
{
    file_put_contents('/usr/local/edomi/www/data/log/amazon_polly.log', $message . "\n", FILE_APPEND);
}
?>