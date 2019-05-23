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
	public function push($device_id, $device_type, $content, $title, $extras, $sendno = null) {
		$di = \PhalApi\DI();
		$rs = array(
			'code' => 0,
			'msg' => '',
			'data' => null
		);
		if(!empty($device_id)) {
			$pusher = $this->client->push();
			//$cid = md5(implode(',', $extras));
			//$pusher->setCid($cid);
			$pusher->addRegistrationId(array($device_id));
			if(strpos('android', strtolower($device_type)) !== false) {
				$pusher->setPlatform('android');
				$android_notification = array(
					'title' => $title,
					//'category' => $cid,
					'extras' => $extras
				);
				$pusher->androidNotification($content, $android_notification);
			} else if(strpos('ios', strtolower($device_type)) !== false) {
				$pusher->setPlatform('ios');
				$ios_notification = array(
					//'alert' => $content,
					'badge' => '+1',
					//'thread-id' => $cid,
					'extras' => $extras
				);
				$pusher->iosNotification($content, $ios_notification);
			} else {
				$pusher->setPlatform('all');
              	$android_notification = array(
					'title' => $title,
					//'category' => $cid,
					'extras' => $extras
				);
				$pusher->androidNotification($content, $android_notification);
				$ios_notification = array(
					//'alert' => $content,
					'badge' => '+1',
					//'thread-id' => $cid,
					'extras' => $extras
				);
				$pusher->iosNotification($content, $ios_notification);
            }
			$options = array(
				'time_to_live' => 0,
			);
			if(!empty($sendno)) {
				$options['sendno'] = $sendno;
			}
			try {
				$response = $pusher->options($options)->send();
              	if($response['http_code'] === 200) {
					$rs['code'] = 1;
					$rs['msg'] = 'success';
					$rs['data'] = $response['body'];
					if($di->debug) $di->logger->log('JPush','push', array('rs' => $response['body']));
				}
			} catch (\JPush\Exceptions\APIConnectionException $e) {
				$rs['code'] = -1;
				$rs['msg'] = $e->__toString();
				if($di->debug) $di->logger->log('JPush','getMessageStatus', array('error' => $e->__toString()));
			} catch (\JPush\Exceptions\APIRequestException $e) {
				$rs['code'] = -2;
				$rs['msg'] = $e->__toString();
				if($di->debug) $di->logger->log('JPush','getMessageStatus', array('error' => $e->__toString()));
			}
		} else {
			$rs['code'] = -3;
			$rs['msg'] = 'Empty Params';
		}
		return $rs;
	}

	/**
	 * 获取送达统计
	 */
	public function getReceived($msg_id) {
		if(!empty($device_id)) {
			$report = $this->client->report();
			try {
				return $report->getReceived($msg_id);
			} catch (\JPush\Exceptions\APIConnectionException $e) {
				\PhalApi\DI()->logger->log('JPush','getReceived', array('error' => $e->__toString()));
			} catch (\JPush\Exceptions\APIRequestException $e) {
				\PhalApi\DI()->logger->log('JPush','getReceived', array('error' => $e->__toString()));
			}
		}
		return -1;
	}

	/**
	 * 获取消息统计
	 */
	public function getMessages($msg_id) {
		if(!empty($device_id)) {
			$report = $this->client->report();
			try {
				return $report->getMessages($msg_id);
			} catch (\JPush\Exceptions\APIConnectionException $e) {
				\PhalApi\DI()->logger->log('JPush','getReceived', array('error' => $e->__toString()));
			} catch (\JPush\Exceptions\APIRequestException $e) {
				\PhalApi\DI()->logger->log('JPush','getReceived', array('error' => $e->__toString()));
			}
		}
		return -1;
	}

	/**
	 * 送达状态查询
	 */
	public function getMessageStatus($msgId, $deviceId) {
		$di = \PhalApi\DI();
		$rs = array(
			'code' => 0,
			'msg' => '',
			'data' => null
		);
		if(!empty($msgId) && !empty($deviceId)) {
			try {
				$report = $this->client->report();
				$response = $report->getMessageStatus(intval($msgId), $deviceId);
				$rs['code'] = 1;
				$rs['msg'] = 'success';
				$rs['data'] = $response;
			} catch (\JPush\Exceptions\APIConnectionException $e) {
				$rs['code'] = -1;
				$rs['msg'] = $e->__toString();
				if($di->debug) $di->logger->log('JPush','getMessageStatus', array('error' => $e->__toString()));
			} catch (\JPush\Exceptions\APIRequestException $e) {
				$rs['code'] = -2;
				$rs['msg'] = $e->__toString();
				if($di->debug) $di->logger->log('JPush','getMessageStatus', array('error' => $e->__toString()));
			}
		} else {
			$rs['code'] = -3;
			$rs['msg'] = 'Empty Params';
		}
		return $rs;
	}
}