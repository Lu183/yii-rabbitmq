<?php
namespace app\controllers;

use yii\web\Controller;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
/**
 * Created by PhpStorm.
 * User: pandeng
 * Date: 2017-07-26
 * Time: 21:51
 */
class MqController extends Controller
{
    const exchange = 'router-yii-db';
    const queue = 'msgs-yii-db';
    public static  function actionMessage()
    {
        $connection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest', '/');
        $channel = $connection->channel();

        $channel->queue_declare(self::queue, false, true, false, false);
        $channel->exchange_declare(self::exchange, 'direct', false, true, false);
        $channel->queue_bind(self::queue, self::exchange);
        $messageBody = 'hellow world!';
        $message = new AMQPMessage($messageBody, array('content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
        // $channel->basic_publish($message, self::exchange);
        for($i=0;$i<10000;$i++){
             $channel->basic_publish($message, self::exchange);
        }
        $channel->close();
        $connection->close();
        return "ok";
    }
}
