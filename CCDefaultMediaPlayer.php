<?php

// Make it really easy to play videos by providing functions for the Chromecast Default Media Player

class CCDefaultMediaPlayer
{
	
	public $chromecast; // The chromecast the initiated this instance.
	public $mediaid; // The media session id.
	
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
				sleep(1);
			}
			$this->chromecast->connect();
		}
	}
	
	public function play($url,$streamType,$contentType,$autoPlay,$currentTime) {
		// Start a playing
		// First ensure there's an instance of the DMP running
		$this->launch();
		$json = '{"type":"LOAD","media":{"contentId":"' . $url . '","streamType":"' . $streamType . '","contentType":"' . $contentType . '"},"autoplay":' . $autoPlay . ',"currentTime":' . $currentTime . ',"requestId":921489134}';
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.media", $json);
		$r = "";
		while (!preg_match("/\"playerState\":\"PLAYING\"/",$r)) {
			$r = $this->chromecast->getCastMessage();
			sleep(1);
		}
		// Grab the mediaSessionId
		preg_match("/\"mediaSessionId\":([^\,]*)/",$r,$m);
		$this->mediaid = $m[1];
	}
	
	public function pause() {
		// Pause
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.media",'{"type":"PAUSE", "mediaSessionId":' . $this->mediaid . ', "requestId":1}');
		$this->chromecast->getCastMessage();
	}

	public function restart() {
		// Restart (after pause)
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.media",'{"type":"PLAY", "mediaSessionId":' . $this->mediaid . ', "requestId":1}');
		$this->chromecast->getCastMessage();
	}
	
	public function seek($secs) {
		// Seek
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.media",'{"type":"SEEK", "mediaSessionId":' . $this->mediaid . ', "currentTime":' . $secs . ',"requestId":1}');
		$this->chromecast->getCastMessage();
	}
	
	public function stop() {
		// Stop
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.media",'{"type":"STOP", "mediaSessionId":' . $this->mediaid . ', "requestId":1}');
		$this->chromecast->getCastMessage();
	}
	
	public function getStatus() {
		// Stop
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.media",'{"type":"GET_STATUS", "mediaSessionId":' . $this->mediaid . ', "requestId":1}');
		$r = $this->chromecast->getCastMessage();
		preg_match("/{\"type.*/",$r,$m);
		return json_decode($m[0]);
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
	
	public function SetVolume($volume) {
		// Mute a video
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.receiver", '{"type":"SET_VOLUME", "volume": { "level": ' . $volume . ' }, "requestId":1 }');
		$this->chromecast->getCastMessage();
	}
}

?>