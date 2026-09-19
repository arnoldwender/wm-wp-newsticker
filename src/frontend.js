/**
 * WM Newsticker - Frontend Script
 *
 * Handles all ticker animations and controls on the frontend.
 * Supports: scroll, fade, slide, and typing animations.
 *
 * @package WM_Newsticker
 * @since 1.4.6
 */

( function () {
	'use strict';

	/**
	 * Check if user prefers reduced motion.
	 *
	 * @return {boolean} True if reduced motion is preferred.
	 */
	function prefersReducedMotion() {
		return (
			window.matchMedia &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
		);
	}

	/**
	 * Update ARIA label on play/pause button.
	 *
	 * @param {HTMLElement} btn     The play/pause button.
	 * @param {boolean}     playing Whether currently playing.
	 */
	function updateAriaState( btn, playing ) {
		if ( btn ) {
			btn.setAttribute( 'aria-label', playing ? 'Pause' : 'Play' );
			btn.setAttribute( 'data-playing', playing ? 'true' : 'false' );
		}
	}

	/**
	 * Initialize a scroll-type ticker.
	 *
	 * @param {HTMLElement} wrapper     The ticker wrapper element.
	 * @param {boolean}     hasControls Whether the ticker has controls.
	 */
	function initScrollTicker( wrapper, hasControls ) {
		var track = wrapper.querySelector( '.wm-newsticker-track' );
		if ( ! track ) {
			return;
		}

		var playing = true;
		var manuallyPaused = false;
		var prog = wrapper.querySelector( '.wm-newsticker-progress-bar' );
		var pauseOnHover = wrapper.dataset.pauseOnHover === 'true';
		var playPauseBtn = null;
		var iconPause = null;
		var iconPlay = null;

		/**
		 * Update the play/pause icon visibility.
		 */
		function updateIcons() {
			if ( iconPause ) {
				iconPause.style.display = playing ? 'block' : 'none';
			}
			if ( iconPlay ) {
				iconPlay.style.display = playing ? 'none' : 'block';
			}
			updateAriaState( playPauseBtn, playing );
		}

		/**
		 * Pause the scroll animation.
		 */
		function pause() {
			track.style.animationPlayState = 'paused';
			if ( prog ) {
				prog.style.animationPlayState = 'paused';
			}
			playing = false;
			updateIcons();
		}

		/**
		 * Resume the scroll animation.
		 */
		function play() {
			track.style.animationPlayState = 'running';
			if ( prog ) {
				prog.style.animationPlayState = 'running';
			}
			playing = true;
			updateIcons();
		}

		if ( hasControls ) {
			playPauseBtn = wrapper.querySelector( '.wm-control-play-pause' );
			iconPause = playPauseBtn
				? playPauseBtn.querySelector( '.wm-icon-pause' )
				: null;
			iconPlay = playPauseBtn
				? playPauseBtn.querySelector( '.wm-icon-play' )
				: null;

			if ( playPauseBtn ) {
				playPauseBtn.addEventListener( 'click', function () {
					if ( playing ) {
						pause();
						manuallyPaused = true;
					} else {
						play();
						manuallyPaused = false;
					}
				} );
			}
		}

		if ( pauseOnHover ) {
			wrapper.addEventListener( 'mouseenter', function () {
				if ( playing ) {
					pause();
				}
			} );
			wrapper.addEventListener( 'mouseleave', function () {
				if ( ! manuallyPaused ) {
					play();
				}
			} );
		}
	}

	/**
	 * Initialize a slide-type ticker (fade, slide, typing).
	 *
	 * @param {HTMLElement} wrapper       The ticker wrapper element.
	 * @param {boolean}     hasControls   Whether the ticker has controls.
	 * @param {number}      duration      Duration per slide in milliseconds.
	 * @param {boolean}     reducedMotion Whether the user prefers reduced motion.
	 */
	function initSlideTicker( wrapper, hasControls, duration, reducedMotion ) {
		var slides = wrapper.querySelectorAll( '.wm-newsticker-slide' );
		var total = slides.length;

		if ( total < 2 ) {
			return;
		}

		var current = 0;
		var paused = reducedMotion;
		var manuallyPaused = false;
		var prog = wrapper.querySelector( '.wm-newsticker-progress-bar' );
		var pauseOnHover = wrapper.dataset.pauseOnHover === 'true';

		/**
		 * Show a specific slide by index.
		 *
		 * @param {number} idx The slide index to show.
		 */
		function show( idx ) {
			slides.forEach( function ( slide, i ) {
				slide.classList.remove( 'active', 'exit' );
				if ( i === idx ) {
					slide.classList.add( 'active' );
				} else if ( i === ( idx - 1 + total ) % total ) {
					slide.classList.add( 'exit' );
				}
			} );

			if ( prog ) {
				prog.style.animation = 'none';
				void prog.offsetHeight;
				prog.style.animation = '';
			}
		}

		/**
		 * Advance to the next slide.
		 */
		function next() {
			current = ( current + 1 ) % total;
			show( current );
		}

		/**
		 * Go to the previous slide.
		 */
		function prev() {
			current = ( current - 1 + total ) % total;
			show( current );
		}

		/**
		 * Interval tick handler.
		 */
		function tick() {
			if ( ! paused ) {
				next();
			}
		}

		setInterval( tick, duration );

		if ( hasControls ) {
			var playPauseBtn = wrapper.querySelector(
				'.wm-control-play-pause'
			);
			var prevBtn = wrapper.querySelector( '.wm-control-prev' );
			var nextBtn = wrapper.querySelector( '.wm-control-next' );
			var iconPause = playPauseBtn
				? playPauseBtn.querySelector( '.wm-icon-pause' )
				: null;
			var iconPlay = playPauseBtn
				? playPauseBtn.querySelector( '.wm-icon-play' )
				: null;

			/**
			 * Update icon visibility and ARIA state.
			 */
			function updateIcons() {
				if ( iconPause ) {
					iconPause.style.display = paused ? 'none' : 'block';
				}
				if ( iconPlay ) {
					iconPlay.style.display = paused ? 'block' : 'none';
				}
				updateAriaState( playPauseBtn, ! paused );
			}

			/**
			 * Pause the slide rotation.
			 */
			function pauseSlides() {
				paused = true;
				if ( prog ) {
					prog.style.animationPlayState = 'paused';
				}
				updateIcons();
			}

			/**
			 * Resume the slide rotation.
			 */
			function playSlides() {
				paused = false;
				if ( prog ) {
					prog.style.animationPlayState = 'running';
				}
				updateIcons();
			}

			if ( playPauseBtn ) {
				playPauseBtn.addEventListener( 'click', function () {
					if ( paused ) {
						playSlides();
						manuallyPaused = false;
					} else {
						pauseSlides();
						manuallyPaused = true;
					}
				} );
			}
			if ( prevBtn ) {
				prevBtn.addEventListener( 'click', function () {
					prev();
				} );
			}
			if ( nextBtn ) {
				nextBtn.addEventListener( 'click', function () {
					next();
				} );
			}

			if ( pauseOnHover ) {
				wrapper.addEventListener( 'mouseenter', function () {
					if ( ! paused ) {
						pauseSlides();
					}
				} );
				wrapper.addEventListener( 'mouseleave', function () {
					if ( ! manuallyPaused ) {
						playSlides();
					}
				} );
			}
		} else if ( pauseOnHover ) {
			wrapper.addEventListener( 'mouseenter', function () {
				paused = true;
			} );
			wrapper.addEventListener( 'mouseleave', function () {
				if ( ! manuallyPaused ) {
					paused = false;
				}
			} );
		}
	}

	/**
	 * Initialize a single ticker instance.
	 *
	 * @param {HTMLElement} wrapper       The ticker wrapper element.
	 * @param {boolean}     reducedMotion Whether user prefers reduced motion.
	 */
	function initTicker( wrapper, reducedMotion ) {
		var animationType = wrapper.dataset.animation || 'scroll';
		var hasControls = wrapper.dataset.hasControls === 'true';
		var speed = Math.max( 1, parseFloat( wrapper.dataset.speed ) || 5 );

		if ( animationType === 'scroll' ) {
			initScrollTicker( wrapper, hasControls );
		} else {
			initSlideTicker(
				wrapper,
				hasControls,
				speed * 1000,
				reducedMotion
			);
		}
	}

	/**
	 * Initialize all tickers on the page.
	 */
	function initAllTickers() {
		var reducedMotion = prefersReducedMotion();
		var tickers = document.querySelectorAll( '.wm-newsticker-wrapper' );
		tickers.forEach( function ( ticker ) {
			// Skip already initialized tickers.
			if ( ticker.dataset.initialized ) {
				return;
			}
			ticker.dataset.initialized = 'true';

			// If user prefers reduced motion, pause all CSS animations.
			if ( reducedMotion ) {
				var track = ticker.querySelector( '.wm-newsticker-track' );
				if ( track ) {
					track.style.animationPlayState = 'paused';
				}
			}

			initTicker( ticker, reducedMotion );
		} );
	}

	// Initialize when DOM is ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initAllTickers, { once: true } );
	} else {
		initAllTickers();
	}
} )();
