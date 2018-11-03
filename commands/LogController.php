<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;
use Yii;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class LogController extends Controller
{

    const exchange = 'router-yii-db';
    const queue = 'msgs-yii-db';
    const consumerTag = 'consumer-yii-db';
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     * @return int Exit code
     */
    public function actionIndex($message = 'hello world')
    {
        echo $message . "\n";

        return ExitCode::OK;
    }

    function shutdown($channel, $connection)
    {
        $channel->close();
        $connection->close();
        Yii::trace("closed");
    }

    function process_message($message)
    {

        if ($message->body !== 'quit') {
            $obj = json_decode($message->body);
            if (0) {
                Yii::trace( "error data1:" . $message->body);
            } else {
                try {
                    // $db=MysqliDB::getIntance();
                    // $db->insert('log_cp',['time'=>date('Y-m-d H:i:s')]);
                    Yii::$app->db->createCommand()->insert('log_db', ['time' => date('Y-m-d H:i:s')])->execute();
                    Yii::$app->db->createCommand()->insert('log_yii', ['time' => date('Y-m-d H:i:s')])->execute();

                    Yii::trace("data:" . json_encode($message));
                } catch (\Think\Exception  $e) {
                    Yii::trace($e->getMessage());
                    Yii::trace(json_encode($message));
                } catch (\PDOException $pe) {
                    Yii::trace($pe->getMessage());
                    Yii::trace(json_encode($message));
                }
            }
        }
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        // Send a message with the string "quit" to cancel the consumer.
        if ($message->body === 'quit') {
            $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
        }
    }

    /**
     * 启动
     *
     * @return \think\Response
     */
    public function actionStart()
    {
        $connection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest', '/');
        $channel = $connection->channel();
        $channel->queue_declare(self::queue, false, true, false, false);
        $channel->exchange_declare(self::exchange, 'direct', false, true, false);
        $channel->queue_bind(self::queue, self::exchange);

        $channel->basic_consume(self::queue, self::consumerTag, false, false, false, false, array($this, 'process_message'));
        register_shutdown_function(array($this, 'shutdown'), $channel, $connection);
        while (count($channel->callbacks)) {
            $channel->wait();
        }
        Yii::trace("starting");
    }
}
