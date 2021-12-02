<?php

use JMS\Serializer\SerializerBuilder;
use RZ\MixedFeed\Response\FeedItemResponse;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Stopwatch\Stopwatch;

if (PHP_VERSION_ID < 70400) {
    $message = 'Your PHP version is '.phpversion().'.'.PHP_EOL;
    $message .= 'You need a least PHP version 7.4.0';
    throw new \RuntimeException($message);
}

require dirname(__DIR__).'/vendor/autoload.php';

$cache = new ArrayAdapter();
$feed = new \RZ\MixedFeed\MixedFeed([
    // Add some providers here
]);

$sw = new Stopwatch();
$sw->start('fetch');
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
header('X-Generator: rezozero/mixedfeed');
$serializer = SerializerBuilder::create()->build();
$feedItems = $feed->getAsyncCanonicalItems(20);
$event = $sw->stop('fetch');
$feedItemResponse = new FeedItemResponse($feedItems, [
    'time'   => $event->getDuration(),
    'memory' => $event->getMemory(),
]);
$jsonContent = $serializer->serialize($feedItemResponse, 'json');
echo $jsonContent;
