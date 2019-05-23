<?php
namespace PhalApi\Jpush;

use JPush\Client as JPush;

class Lite {

	protected $config;

	protected $client;

	public function __construct($config = NULL) {
        $this->config = $config;
		if (is_null($this->config)) {
			$this->config = \PhalApi\DI()->config->get('app.Jpush');
		}
		$log_path = API_ROOT.'/runtime/jpush/'.date('Ymd', time()).'.log';
		$this->client = new JPush($this->config['app_key'], $this->config['master_secret'], $log_path);
	}

	/**
	 * 推送接口
	 */
	public function sendPush($device_id, $content, $title, $extras) {
		if(!empty($device_id)) {
			$push = $this->client->push();
			$platform = array('all');
			$regId = array($device_id);
			$ios_notification = array(
				'badge' => '+1',
				'extras' => $extras
			);
			$android_notification = array(
				'title' => $title,
				'extras' => $extras
			);
			$options = array(
				'time_to_live' => 0,
			);
			try {
				$response = $push->setPlatform($platform)
					->addRegistrationId($regId)
					->iosNotification($content, $ios_notification)
					->androidNotification($content, $android_notification)
					->options($options)
					->send();
				return 1;
			} catch (\JPush\Exceptions\APIConnectionException $e) {
				\PhalApi\DI()->logger->log('JPush','APIConnectionException', array('error' => $e->__toString()));
			} catch (\JPush\Exceptions\APIRequestException $e) {
				\PhalApi\DI()->logger->log('JPush','APIRequestException', array('error' => $e->__toString()));
			}
		}
		return -1;
	}

	/**
	 * 推送接口
	 */
	public function push($device_id, $device_type, $content, $title, $extras) {
		if(!empty($device_id)) {
			$pusher = $this->client->push();
			$cid = md5(implode(',', $extras)); //用于防止 api 调用端重试造成服务端的重复推送而定义的一个标识符。
			//$pusher->setCid($cid);
			$pusher->addRegistrationId(array($device_id));
			$platform = array('all'); //推送平台设置
			if(strpos('android', strtolower($device_type))) {
				$platform = array('android');
				$android_notification = array(
					'title' => $title,
					'category' => $cid,
					'extras' => $extras
				);
				$pusher->androidNotification($content, $android_notification);
			} else if(strpos('ios', strtolower($device_type))) {
				$platform = array('ios');
				$ios_notification = array(
					'alert' => $content,
					'badge' => '+1',
					'thread-id' => $cid,
					'extras' => $extras
				);
				$pusher->iosNotification($content, $ios_notification);
			}
			$pusher->message($title, [
				'title' => $title,
				'msg_content' => $content,
				'content_type' => 'text',
				'extras' => $extras
			]);
			$pusher->setPlatform($platform);
			//推送参数
			$options = array(
				//'sendno' => md5(uniqid('', true)),
				'time_to_live' => 0,
			);
			try {
				$response = $pusher->options($options)->send();
				return 1;
			} catch (\JPush\Exceptions\APIConnectionException $e) {
				\PhalApi\DI()->logger->log('JPush','APIConnectionException', array('error' => $e->__toString()));
			} catch (\JPush\Exceptions\APIRequestException $e) {
				\PhalApi\DI()->logger->log('JPush','APIRequestException', array('error' => $e->__toString()));
			}
		}
		return -1;
	}
}
