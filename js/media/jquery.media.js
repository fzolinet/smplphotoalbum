/*! FAKTOR VIER Media Controller v0.1.4 | (c) 2023 FAKTOR VIER GmbH | http://faktorvier.ch */

// Add post message event listener for vimeo
var media_postmessage_event_func = 'attachEvent';
var media_postmessage_event = 'onmessage';

if(window.addEventListener) {
	media_postmessage_event_func = 'addEventListener';
	media_postmessage_event = 'message';
}

(function($) {

	// Global object
	$.media = {		
		global : {
			event_suffix: '_media',
			type: 'video'
		},
		config : {
			attr_ready: 'data-media-ready',
			attr_playing: 'data-media-playing',
			attr_paused: 'data-media-paused'
		},		
	};

	/* PRIVATE FUNCTIONS */

	/* PUBLIC FUNCTIONS */

	// Get media config
	$.fn.getMediaConfig = function() {
		var $media = $(this).first();

		return ( typeof $media.data('media') != 'undefined' ? $media.data('media').config : null )
	}

	// Get media player
	$.fn.getMediaPlayer = function() {
		var $media = $(this).first();

		return ( typeof $media.data('media-player') != 'undefined' ?  $media.data('media-player') : null )
	}

	// Get media type
	$.fn.getMediaType = function() {
		var $media = $(this).first();

		if($media.prop('tagName').toLowerCase() == 'video') {
			media_type = 'video';
		} else{
			media_type = 'audio';
		}
		return media_type;
	}

	// Init
	$.fn.initMedia = function(config) {
		return this.each(function() {
			var $media = $(this);
			var media_type = $media.getMediaType();
			//var media_player = $media.getMediaPlayer();
			var media_config = $media.getMediaConfig();

			if(media_config == null) {
				media_config = $.extend($.extend({}, $.media.config), config);
				$media.data('media', { config : media_config });
			} else {
				console.warn('Player already initialized!');
				return false;
			}

			// HTML5
			if(media_type == 'video' || media_type == 'audio') {
				// Ready
				if($media.get(0).readyState == 4) {
					$media.attr(media_config.attr_ready, '');
					$media.trigger('ready' + $.media.global.event_suffix);
				} else {
					$media.get(0).addEventListener(
						'canplaythrough',
						function() {
							$media.attr(media_config.attr_ready, '');
							$media.trigger('ready' + $.media.global.event_suffix);
							$media.get(0).removeEventListener('canplaythrough', this);
						},
						false
					);
				}

				// Play
				$media.bind('play', function() {
					$media.removeAttr(media_config.attr_paused);
					$media.attr(media_config.attr_playing, '');

					$media.trigger('play' + $.media.global.event_suffix);
				});

				// Pause
				$media.bind('pause', function() {
					$media.removeAttr(media_config.attr_playing);
					$media.attr(media_config.attr_paused, '');

					$media.trigger('pause' + $.media.global.event_suffix);
				});

				// Finish
				$media.bind('ended', function() {
					$media.removeAttr(media_config.attr_playing);
					$media.removeAttr(media_config.attr_paused);

					$media.trigger('finish' + $.media.global.event_suffix);
				});

				$media.data('media-player', $media[0]);
			} else if(media_type == 'youtube') {
				// Youtube
				youtube_player_action(
					$media,
					function(player) {
						$media.trigger('ready' + $.media.global.event_suffix);
					}
				);
			} else if(media_type == 'vimeo') {
				// Vimeo
				vimeo_player_action(
					$media,
					function(player) {
						$media.trigger('ready' + $.media.global.event_suffix);
					}
				);
			}
		});
	}

	// Play
	$.fn.playMedia = function() {
		return this.each(function() {
			var $media = $(this);
			var media_type = $media.getMediaType();
			var media_player = $media.getMediaPlayer();

			// Check if player is initialized
			if(media_player == null) {
				console.warn('Player not initialized!');
				return false;
			}
			media_player.play();			
		});
	}

	// Pause
	$.fn.pauseMedia = function() {
		return this.each(function() {
			var $media = $(this);
			var media_type = $media.getMediaType();
			var media_player = $media.getMediaPlayer();

			// Check if player is initialized
			if(media_player == null) {
				console.warn('Player not initialized!');
				return false;
			}			
			// HTML5
			media_player.pause();

		});
	}

	// Stop
	$.fn.stopMedia = function() {
		return this.each(function() {
			var $media = $(this);
			var media_type = $media.getMediaType();
			var media_player = $media.getMediaPlayer();

			// Check if player is initialized
			if(media_player == null) {
				console.warn('Player not initialized!');
				return false;
			}
			media_player.pause();
			media_player.currentTime = 0;
		});
	}

	// Restart
	$.fn.restartMedia = function() {
		return this.each(function() {
			var $media = $(this);
			var media_type = $media.getMediaType();
			var media_player = $media.getMediaPlayer();
			var media_config = $media.getMediaConfig();

			// Check if player is initialized
			if(media_player == null) {
				console.warn('Player not initialized!');
				return false;
			}

			// HTML5
			media_player.currentTime = 0;
			media_player.play();
		});
	}

	// Mute
	$.fn.muteMedia = function() {
		return this.each(function() {
			var $media = $(this);
			var media_type = $media.getMediaType();
			var media_player = $media.getMediaPlayer();

			// Check if player is initialized
			if(media_player == null) {
				console.warn('Player not initialized!');
				return false;
			}

			media_player.muted = true;
		});
	}

	// Unmute
	$.fn.unmuteMedia = function() {
		return this.each(function() {
			var $media = $(this);
			var media_type = $media.getMediaType();
			var media_player = $media.getMediaPlayer();

			// Check if player is initialized
			if(media_player == null) {
				console.warn('Player not initialized!');
				return false;
			}

			media_player.muted = false;
		});
	}

	// Seek to
	$.fn.seekToMedia = function(seconds) {
		return this.each(function() {
			var $media = $(this);
			var media_type = $media.getMediaType();
			var media_player = $media.getMediaPlayer();

			// Check if player is initialized
			if(media_player == null) {
				console.warn('Player not initialized!');
				return false;
			}
			media_player.currentTime = seconds;
		});
	}

	// Destroy
	$.fn.destroyMedia = function() {
		return this.each(function() {
			var $media = $(this);
			var media_type = $media.getMediaType();
			var media_player = $media.getMediaPlayer();
			var media_config = $media.getMediaConfig();

			if(media_config != null) {
				// Remove data
				$media.removeData('media');
				$media.removeData('media-player');

				// Remove attrs
				$media.removeAttr(media_config.attr_ready);
				$media.removeAttr(media_config.attr_playing);
				$media.removeAttr(media_config.attr_paused);

				// Callback
				$media.trigger('destroy' + $.media.global.event_suffix);
			}
		});
	}

	// Add event
	$.fn.addMediaEvent = function(eventType, handler) {
		return this.each(function() {
			var $media = $(this);
			var media_type = $media.getMediaType();
			var media_player = $media.getMediaPlayer();

			// Trigger play event if html5 media autoplay
			if(eventType == 'play' && typeof $media.get(0).paused != 'undefined' && !$media.get(0).paused) {
				handler(null, $media, media_type, media_player);
			}

			$media.bind(
				eventType + $.media.global.event_suffix,
				function(e) {
					handler(e, $media, media_type, media_player);
				}
			);
		});
	}

	// Remove event
	$.fn.removeMediaEvent = function(eventType) {
		return this.each(function() {
			var $media = $(this);
			$media.unbind(eventType + $.media.global.event_suffix);
		});
	}

	// Main binding
	$.fn.media = function() {
		var media_action = 'init';
		var media_args = {};

		if(typeof arguments[0] == 'string') {
			media_action = arguments[0];
			media_args = arguments[1];
		} else {
			media_args = arguments[0]
		}

		// Bind each media
		return this.each(function() {
			var $media = $(this);

			switch(media_action) {
				case 'init':
					$media.initMedia(media_args);
					break;

				case 'play':
					$media.playMedia();
					break;

				case 'pause':
					$media.pauseMedia();
					break;

				case 'stop':
					$media.stopMedia();
					break;

				case 'restart':
					$media.restartMedia();
					break;

				case 'mute':
					$media.muteMedia();
					break;

				case 'unmute':
					$media.unmuteMedia();
					break;

				case 'seekTo':
					$media.seekToMedia(media_args);
					break;

				case 'destroy':
					$media.destroyMedia();
					break;

				case 'addEvent':
					$media.addMediaEvent(media_args[0], media_args[1]);
					break;

				case 'removeEvent':
					$media.removeMediaEvent(media_args[0]);
					break;

				default:
					console.warn('Media action "' + media_action + '" not found');
			}
		});
	};

}(jQuery));