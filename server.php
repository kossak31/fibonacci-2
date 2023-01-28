<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;



$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('rpc_queue', false, false, false, false);

echo " [x] Requesting fib\n";

$callback = function ($req) {

    $data = json_decode($req->body, true);
    echo " [.] fib(", $data, ")\n";
    $msg = new AMQPMessage(
        json_encode(fib($data)),
        array('correlation_id' => $req->get('correlation_id'))
    );


    $req->delivery_info['channel']->basic_publish($msg, '', $req->get('reply_to'));

    $req->delivery_info['channel']->basic_ack($req->delivery_info['delivery_tag']);
};
$channel->basic_qos(null, 1, null);
$channel->basic_consume('rpc_queue', '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();

function fib($n)
{
    if ($n <= 0) return 0;
    if ($n == 1) return 1;
    return fib($n-1) + fib($n-2);
  
}
