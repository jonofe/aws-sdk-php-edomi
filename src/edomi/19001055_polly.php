<?php
//require "/opt/aws-sdk-php/vendor/autoload.php";
require "/opt/aws-sdk-php/aws.phar";

date_default_timezone_set('Europe/Berlin');

logging('Starting Amazon Polly Script ...');

// if command line parameter is missing return error
if (count($argv) != 2) {
    echo base64_encode(serialize(json_encode(array(
        0 => "ERROR",
        1 => '',
        2 => ''
    ))));
    die();
}

logging($argv[1]);

// read json command line parameter
$params = json_decode(unserialize(base64_decode($argv[1])), true);

// create AWS credentials
$credentials = new Aws\Credentials\Credentials($params['awsAccessKeyId'], $params['awsSecretKey']);

// create polly client instance
$client = new Aws\Polly\PollyClient(array(
    'version' => 'latest',
    'debug' => false,
    'credentials' => $credentials,
    'region' => $params['awsRegion']
));

// generate audio via polly API call
$speech = $client->synthesizeSpeech(array(
    'OutputFormat' => $params['outputFormat'],
    'Text' => $params['text'],
    'TextType' => $params['textType'],
    'VoiceId' => $params['voiceId']
));

// retrieve audio data from polly
$audioData = $speech->get('AudioStream')->getContents();

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
        3 => ''
    ))));
} else {
    $result = base64_encode(serialize(json_encode(array(
        1 => 'OK',
        2 => $fullFilename,
        3 => 'http://' . $params['ip'] . '/data/tmp/' . $filename
    ))));
}

echo $result;


function fail($message)
{
    file_put_contents('/usr/local/edomi/www/data/log/amazon_polly.log', $message . "\n", FILE_APPEND);
    error_log($message);
    die();
}

function logging($message)
{
    file_put_contents('/usr/local/edomi/www/data/log/amazon_polly.log', $message . "\n", FILE_APPEND);
}
?>