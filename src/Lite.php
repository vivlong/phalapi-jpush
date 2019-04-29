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
}
