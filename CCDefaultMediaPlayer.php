<?php

// Make it really easy to play videos by providing functions for the Chromecast Default Media Player

class CCDefaultMediaPlayer
{
	
	public $chromecast; // The chromecast the initiated this instance.
	
	public function __construct($hostchromecast) {
		$this->chromecast = $hostchromecast;
	}

	public function launch() {
		// Launch the player or connect to an existing instance if one is already running
		// First connect to the chromecast
		$this->chromecast->transportid = "";
		$this->chromecast->cc_connect();
		$s = $this->chromecast->getStatus();
		// Grab the appid
		preg_match("/\"appId\":\"([^\"]*)/",$s,$m);
		$appid = $m[1];
		if ($appid == "CC1AD845") {
			// Default Media Receiver is live
			$this->chromecast->getStatus();
			$this->chromecast->connect();
		} else {
			// Default Media Receiver is not currently live, start it.
			$this->chromecast->launch("CC1AD845");
			$this->chromecast->transportid = "";
			$r = "";
			while (!preg_match("/Ready to Cast/",$r) && !preg_match("/Default Media Receiver/",$r)) {
				$r = $this->chromecast->getStatus();
				//if (preg_match("/urn:x-cast:com.google.cast.tp.heartbeat/",$r) && preg_match("/\"PING\"/",$r)) {
			//		$this->chromecast->pong();
				//}
				sleep(1);
			}
		}
	}
	
	public function playVideo($url,$streamType,$contentType,$autoPlay,$currentTime) {
		// Start a video playing
		// First ensure there's an instance of the DMP running
		$this->launch();
		$json = '{"type":"LOAD","media":{"contentId":"' . $url . '","streamType":"' . $streamType . '","contentType":"' . $contentType . '"},"autoplay":' . $autoPlay . ',"currentTime":' . $currentTime . ',"requestId":921489134}';
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.media", $json);
		$r = "";
		while (!preg_match("/\"playerState\":\"PLAYING\"/",$r)) {
			$r = $this->chromecast->getCastMessage();
			// If this is a ping, then pong
			//if (preg_match("/urn:x-cast:com.google.cast.tp.heartbeat/",$r) && preg_match("/\"PING\"/",$r)) {
			//	$this->chromecast->pong();
			//}
			sleep(1);
		}
	}
	
	public function Mute() {
		// Mute a video
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.receiver", '{"type":"SET_VOLUME", "volume": { "muted": true }, "requestId":1 }');
		$this->chromecast->getCastMessage();
	}
	
	public function UnMute() {
		// Mute a video
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.receiver", '{"type":"SET_VOLUME", "volume": { "muted": false }, "requestId":1 }');
		$this->chromecast->getCastMessage();
	}
}

?>