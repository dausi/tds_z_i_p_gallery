/* global zg_messages, CCM_APPLICATION_URL, ConcreteAlert, decodeURIComponent */

/**
 * Swiper controller jQuery JavaScript Library for zipgallery
 *
 * Copyright 2017 - TDSystem Beratung & Training, Thomas Dausner (aka dausi)
 * 
 * Version for concrete5 in tds_z_i_p_gallery package.
 * 
 * @param {window.jQuery} $
 */
(function($) {
	ZIPGallery = {
		swiper: [],
		sw_run: []
	};

	/*
	 * test for mobile browser
	 */
	var isMobile = function() {
		var userAgent = navigator.userAgent || navigator.vendor || window.opera;
		return /android|ipad|iphone|ipod|windows phone/i.test(userAgent);
	};
	/*
	 * browser independent full screen toggle
	 */
	var setFullScreen = function(on) {
		if (isMobile()) {
			var doc = window.document;
			var docEl = doc.documentElement;

			var requestFullScreen = docEl.requestFullscreen || docEl.mozRequestFullScreen || docEl.webkitRequestFullScreen || docEl.msRequestFullscreen;
			var cancelFullScreen = doc.exitFullscreen || doc.mozCancelFullScreen || doc.webkitExitFullscreen || doc.msExitFullscreen;

			if (on) {
				if (!doc.fullscreenElement && !doc.mozFullScreenElement && !doc.webkitFullscreenElement && !doc.msFullscreenElement && requestFullScreen)
					requestFullScreen.call(docEl);
			}
			else if (cancelFullScreen) {
				cancelFullScreen.call(doc);
			}
		}
	};
	var thumbNails = {
		width: 50,
		height: 50
	};
	/*
	 * IPTC keys for image caption
	 * 
	 * Pseudo (non IPTC) keywords:
	 * 	filename             index                localised                
	 * 
	 * IPTC keywords:
	 *	authorByline         authorTitle          caption
	 *	captionWriter        category             cdate
	 *	city                 copyright            country
	 *	headline             OTR                  photoSource
	 *	source               specialInstructions  state
	 *	subcategories        subject              title
	 *	urgency
	 *
	 * Order and presence are defined in the 'dfltCaption' string. 
	 */
	var dfltCaption = '{%localised%&ensp;}{<b class="dim">&copy;%copyright% - }%index% - %filename%</b>';
	var dfltThumbSize = [ thumbNails.width, thumbNails.height ];
	/*
	 * make caption from template and iptc tags
	 */
	var get_title = function (iptc, captionTpl) {

		var caption = '';
		var groups = captionTpl.split(/[{}]/);
		for (var grp = 0; grp < groups.length; grp++) {
			var capGrp = groups[grp];
			if (capGrp !== '') {
				var keys = capGrp.match(/%\w+%/g);
				var keyFound = false;
				for (var j = 0; j < keys.length; j++) {
					var tag = keys[j].replace(/%/g, '');
					var re = new RegExp(keys[j]); 
					if (iptc[tag] !== undefined) {
						capGrp = capGrp.replace(re, iptc[tag]);
						keyFound = true;
					} else {
						capGrp = capGrp.replace(re, '');
					}
				}
				if (keyFound)
					caption += capGrp;
			}
		}
		return caption;
	};
	/*
	 * align image and text geometry
	 */
	var thumbsBorders = 2;
	var thumbsHeight = thumbNails.height + thumbsBorders;
	var vp = {};
    var alignGeometry = function(thumbsOff) {
    	if (thumbsOff === true) {
    		thumbsHeight = 0;
    		$('a.zg-download').addClass('no-thumbs');
    	}
    	else if (thumbsOff === false) {
    		thumbsHeight = thumbNails.height + thumbsBorders;
    		$('a.zg-download').removeClass('no-thumbs');
    	}
    	vp = {
    		fullHeight: document.documentElement.clientHeight,
    		width:      document.documentElement.clientWidth
    	};
		vp.height = vp.fullHeight - thumbsHeight;
		$('div.gallery-top div.swiper-slide').each(function() {
    		var $sl = $(this);
    		var img = {
    			orgHeight: $sl.data('height'),
    			orgWidth:  $sl.data('width'),
    			height: 0,
    			width: 0
    		};
	    	var ratio = img.orgWidth / img.orgHeight;
	    	var left, top;
	    	if (vp.height * ratio < vp.width) {
	    		img.width = vp.height * ratio;
	    		img.height = vp.height;
	    		left = (vp.width - img.width) / 2;
	    		top = 0;
	    	} else {
	    		img.width = vp.width;
	    		img.height = vp.width / ratio;
	    		left = 0;
	    		top = (vp.height - img.height) / 2;
	    	}
	    	$sl.css({
	    		top: top + 'px'
	    	});
	    	$('img', $sl).css({
	    		height: img.height + 'px',
	    		width: img.width + 'px'
	    	});
	    	$('div.text', $sl).css({
	    		left: left + 'px'
	    	});
	    	$('div.gallery-top').css({
	    		minHeight: vp.height
	    	});
    	});
    };
    /*
     * set download link
     */
    var setDownloadLink = function(slider) {
		var $img = $('img', slider.slides[slider.realIndex]); 
		var url = $img.data('src') === undefined ? $img.attr('src') : $img.data('src');
		var file = url.replace(/^.*file=/, '');
		$('a.zg-download').attr('href', url).attr('download', file);
    };
    /*
     * alert function
     */
    var _alert = function(msg, stack) {
		$('body, button').css('cursor', '');
		ConcreteAlert.dialog('Error', '<div class="alert alert-danger">' + msg + '</div>'
			+	'<p class="hidden" id="alert-stack"><input type="checkbox"><span>Show stack<span><p/>'
			+	'<p class="hidden" id="alert-stack-msg">' + stack + '</p>' 
		);
		$("#dialog-confirm").dialog('close');
		$(document).ready(function() {
			$('#alert-stack')
				.removeClass('hidden')
				.children('input')
					.change(function() {
						$('#alert-stack-msg').toggleClass('hidden');
					});
		});
    };
	/*
	 * Get HEAD information from server. On request done the "doneCallback" is fired.
	 * Argument to the callback is the response URL. On redirection (automatic follow of a 301 or 302) 
	 * the redirect URL is supplied.  
	 */
	var resolveUrl = function(url, doneCallback) {
		var queryStr = url.replace(/^.*\?/, '');
		if (queryStr === url)
			queryStr = '';
		else
			queryStr = '?' + queryStr;
		var http = new XMLHttpRequest();
		http.open('HEAD', url);
		http.onreadystatechange = function() {
		    if (this.readyState === this.DONE) {
		    	if (this.status === 200)
		    		doneCallback(this.responseURL + queryStr);
		    	else
					_alert(zg_messages.zg_resolve_err.replace(/%s/, url) + "\n" + this.statusText, '');
		    }
		};
		http.send();
	};
	/*
	 * setup swiper html from AJAX info
	 */
	var setupSwiper = function(info, imgBase, topSwiper, thumbSwiper, captionTpl, thumbStyle, slideWidth) {
		var dim = [];
		var lang = $('html').attr('lang');
		if (lang === undefined) {
			lang = lang || window.navigator.language || window.navigator.browserLanguage || window.navigator.userLanguage;
			lang = lang.substr(0, 2);
		}
		for (var idx = 0; idx < info.length; idx++) {
			var comp = info[idx].exif.COMPUTED;
			info[idx].iptc.index = idx + 1;
			var localised = info[idx].iptc.title;
			var caption = info[idx].iptc.caption;
			if (caption !== undefined) {
				var loc = caption.toString().split(/[{}]/);
				if (loc.length > 0 && loc.length % 2 === 1) {
					for (var lidx = 0; lidx < loc.length; lidx += 2) {
						if (loc[lidx].trim() === lang) {
							localised = loc[lidx + 1].trim();
							break;
						}
					}
				}
			}
			info[idx].iptc.localised = localised;
			var style = '';
			if (slideWidth > 0) {
				var imgHeight = slideWidth * comp.Height / comp.Width;
				style = 'style="height: ' + imgHeight + 'px" ';
			}
			$(topSwiper + ' .swiper-wrapper')
				.append('<div class="swiper-slide swiper-zoom-container"' + style
						+			'data-height="' + comp.Height + '" data-width="' + comp.Width + '">'
						+	'<img data-src="' + imgBase + '&file=' + info[idx].name + '" class="swiper-lazy">'
						+	'<div class="swiper-lazy-preloader"></div>'
						+	'<div class="text">'
						+		'<p>' + get_title(info[idx].iptc, captionTpl) + '</p>'
						+	'</div>'
						+ '</div>'
				);
			if (thumbSwiper !== null)
				$(thumbSwiper + ' .swiper-wrapper')
					.append('<div class="swiper-slide" style="' + thumbStyle 
								+ ' background-image:url(data:image/jpg;base64,' + info[idx].thumbnail + ')"></div>'
					);
			dim[idx] = {};
			dim[idx].height = parseInt(comp.Height);
			dim[idx].width  = parseInt(comp.Width);
		}
		dim.sort(function(a, b) { return a.height - b.height; });
		var med = parseInt(idx / 2);
		var medHeight, medWidth;
		if (idx % 2) {
			medHeight = (dim[med].height + dim[med + 1].height) / 2;
			medWidth  = (dim[med].width  + dim[med + 1].width)  / 2;
		} else {
			medHeight = dim[med].height;
			medWidth  = dim[med].width;
		}
		if (slideWidth > 0) {
			medHeight = slideWidth * medHeight / medWidth;
		}
		return medHeight;
	};
	/*
	 * check "running" galleries
	 */
	var setZIPGalleriesRunning = function(state) {
		for (var id in ZIPGallery.swiper) {
			var sw = ZIPGallery.swiper[id];
			if (sw.autoplaying)
				if (state)
					sw.startAutoplay();
				else
					sw.stopAutoplay();
		}
	};
	/*
	 * execute gallery
	 */
	var execGallery = function(zipUrl, $a, galleryName) {
		setFullScreen(true);
		//
		// initilaisation of caption and thumbs
		//
		var captionTpl = $a.data('caption') === undefined ? dfltCaption : $a.data('caption');
		if (captionTpl === '')
			captionTpl = dfltCaption;
		var tns = dfltThumbSize;
		if ($a.data('thumbsize') !== undefined) {
			tns = $a.data('thumbsize').split('x');
		}
		thumbNails.width  = parseInt(tns[0]);
		thumbNails.height = parseInt(tns[1]);
		
		var tg = $a.attr('target');
		var dnButton = !(tg !== undefined && tg.match(/^gallery-/));
		
		var initialSlide = parseInt($a.data('index')) - 1;
		var queryStr = zipUrl.replace(/^.*\?/, '');
		if (queryStr !== zipUrl) {
			zipUrl = zipUrl.replace(/\?.*/, '');
			var params = queryStr.split('\&');
			for (var i in params) {
				var tmp = params[i].split('=');
				if (tmp[0] === 'id')
					initialSlide = parseInt(tmp[1]) - 1;
			}
		}
		if (isNaN(initialSlide) || initialSlide < 0)
			initialSlide = 0;
		zipUrl = zipUrl.substr(CCM_APPLICATION_URL.length);
		var ccmUrl = CCM_APPLICATION_URL + '/index.php/ccm/tds_z_i_p_gallery/galleries/';
		var infoUrl = ccmUrl + 'getinfo?zip=' + zipUrl + '&tnw=' + thumbNails.width + '&tnh=' + thumbNails.height;
		var imgBase = ccmUrl + 'getimage?zip=' + zipUrl;
		//
		// get info
		//
		$('body, a').css('cursor', 'progress');
		$.ajax( {
			type: 'GET',
			url: infoUrl,
			dataType: 'json',
			success: function(info) {
				$('body, a').css('cursor', '');
				if (info.length === 0) {
					_alert(zg_messages.zg_no_images.replace(/%s/, galleryName), '');
				} else if (typeof info.error !== 'undefined') {
					_alert(zg_messages.zg_load_err.replace(/%s/, galleryName) + '<br/>' + decodeURIComponent(info.error.replace(/\+/g, ' ')));
				} else {
					//
					// prepare swiper gallery
					//
					$('body').append('<div id="zipGallery">'
									+	'<div class="zg-close"></div>'
									+	( dnButton ? '<a class="zg-download swiper-button-next swiper-button-white">&nbsp;</a>' : '' )
				    				+	'<div class="swiper-container gallery-top">'
						    		+		'<div class="swiper-wrapper"></div>'
						            + 		'<div class="swiper-button-next swiper-button-white"></div>'
						            + 		'<div class="swiper-button-prev swiper-button-white"></div>'
						            +	'</div>'
						            +	'<div class="swiper-container gallery-thumbs">'
						    		+		'<div class="swiper-wrapper"></div>'
						            +	'</div>'
						            + '</div>'
					);
					$('#zipGallery').on('contextmenu', function(e) {
						return dnButton;
					});
					var thumbStyle = 'width: ' + thumbNails.width + 'px; height:' + thumbNails.height + 'px;';
					
					setupSwiper(info, imgBase, '#zipGallery .gallery-top', '#zipGallery .gallery-thumbs', captionTpl, thumbStyle, 0);
					
					alignGeometry(false);
					/*
					 * open image gallery 
					 */
					var swOpts = {
						keyboardControl: true,
						mousewheelControl: true,
						initialSlide: initialSlide,
//not with thumbnails	loop: true,
						nextButton: '.swiper-button-next',
						prevButton: '.swiper-button-prev',
						zoom: true,
						zoomMax: 5,
						preloadImages: false,
						lazyLoading: true,
						lazyLoadingInPrevNext: true,
					    speed: 400,
					    spaceBetween: 10,
					    onInit: function(sw) {
					    	setDownloadLink(sw);
					    	setZIPGalleriesRunning(false);
					    },
					    onDestroy: function() {
					    	setZIPGalleriesRunning(true);
					    },
					    onSlideChangeEnd: setDownloadLink,
					    onTouchStart: function(swiper, event) {
					    	if (event.screenX === undefined) {
					    		event = event.changedTouches[0];
					    	}
					    	swOpts.touch.x = event.screenX;
					    	swOpts.touch.y = event.screenY;
					    },
					    onTouchEnd: function(swiper, event) {
					    	if (event.screenX === undefined) {
					    		event = event.changedTouches[0];
					    	}
					    	var dx = swOpts.touch.x - event.screenX;
					    	var dy = swOpts.touch.y - event.screenY;
					    	if (swOpts.touch.y > vp.height / 2 && Math.abs(dy) / vp.height >= 0.2) {
					    		// start gesture in lower half of viewport, min 20% offset
						    	var ratio = dy / Math.abs(dx);
						    	if (ratio < -2.) {
						    		// gesture down
						    		alignGeometry(true);
						    	} else if (ratio > 2.) {
						    		// gesture up
						    		alignGeometry(false);
						    	}
					    	}
					    },
					    touch: { x: 0, y: 0 }
					};
					if (!isMobile()) {
						swOpts.lazyLoadingInPrevNextAmount = 10;
						swOpts.slidesPerView = 'auto';
					}
					var galleryTop = new Swiper('#zipGallery .gallery-top', swOpts);
				    var galleryThumbs = new Swiper('#zipGallery .gallery-thumbs', {
				        centeredSlides: true,
						initialSlide: initialSlide,
				        slidesPerView: 'auto',
				        touchRatio: 0.8,
				        slideToClickedSlide: true
				    });
				    galleryTop.params.control = galleryThumbs;
				    galleryThumbs.params.control = galleryTop;
					/*
					 * gallery close click/escape handler
					 */
					$('#zipGallery div.zg-close').click(function() {
						$('#zipGallery').remove();
						setFullScreen(false);
					});
					$(document).keydown(function(evt) {
					    evt = evt || window.event;
					    var isEscape = false;
					    if ("key" in evt) {
					        isEscape = (evt.key === "Escape" || evt.key === "Esc");
					    } else {
					        isEscape = (evt.keyCode === 27);
					    }
					    if (isEscape) {
							$('#zipGallery').remove();
							setFullScreen(false);
					    }
					});
					/*
					 * window resize handler
					 */
					$(window).resize(alignGeometry);
				} 
			},
			error: function(xhr, statusText, err) {
				$('body, a').css('cursor', '');
				_alert(zg_messages.zg_load_err.replace(/%s/, zipUrl) + '<br/>' + statusText + '<br/>' + err.message, err.stack);
			}
		});
	};
	/*
	 * Initialise ZIP Image Gallery from a concrete5 block
	 */
	ZIPGallery.init = function(opts) {
//		var zipUrl = opts.url.substr(CCM_APPLICATION_URL.length);
//		var galleryName = zipUrl.replace('.zip', '');
		var ccmUrl = CCM_APPLICATION_URL + '/index.php/ccm/tds_z_i_p_gallery/galleries/';
//		var infoUrl = ccmUrl + 'getinfo?zip=' + zipUrl;
//		var imgBase = ccmUrl + 'getimage?zip=' + zipUrl;
		var infoUrl = ccmUrl + 'getinfo?zipId=' + opts.zipId;
		var imgBase = ccmUrl + 'getimage?zipId=' + opts.zipId;
		var galleryName = opts.gallery;
		//
		// get info
		//
		$('body, a').css('cursor', 'progress');
		$.ajax({
			type: 'GET',
			url: infoUrl,
			dataType: 'json',
			success: function(info) {
				$('body, a').css('cursor', '');
				if (info.length === 0) {
					alert(zg_messages.zg_no_images.replace(/%s/, galleryName));
				} else if (typeof info.error !== 'undefined') {
					_alert(zg_messages.zg_load_err.replace(/%s/, galleryName) + '<br/>' + decodeURIComponent(info.error.replace(/\+/g, ' ')));
				} else {
					
					var constructSwiper = function(opts) {
						$(opts.id).append('<div class="swiper-container gallery"><div class="swiper-wrapper"></div></div>');
						opts.swiperWidth  = $(opts.id).outerWidth();
						opts.swiperHeight = $(opts.id).outerHeight();
						if (opts.swiperHeight > 0) {
							opts.slideWidth = opts.swiperWidth;
							if (opts.numSlides > 1)
								opts.slideWidth = Math.ceil((opts.swiperWidth - opts.spaceBetween * (opts.numSlides - 1))/ opts.numSlides);
							opts.maxHeight = setupSwiper(info, imgBase + '&max-width=' + opts.slideWidth, '.gallery', null, opts.caption, '', opts.slideWidth);
							var initialSlide = 0;
							opts.cookieId = opts.id.replace(/#ccm-block-tds-/, '');
							if (opts.imgUnique) {
								var cookies = document.cookie.split('; ');
								for (var i in cookies) {
									if (cookies[i].replace(/=.*/, '') === opts.cookieId) {
										initialSlide = cookies[i].replace(/.*=/, '') - 1;
										break;
									}
								}
							}
							var setHeight = function() {
								$(opts.id + ' .gallery').css({
									maxHeight: opts.maxHeight
								});
							};
							var swOpts = {
								slidesPerView: opts.numSlides,
								initialSlide: initialSlide,
								keyboardControl: false,
								mousewheelControl: true,
								loop: true,
								preloadImages: true,
								lazyLoading: true,
								lazyLoadingInPrevNext: true,
								speed: 400,
								spaceBetween: opts.spaceBetween,
								onSlideChangeEnd: function() {
									if (opts.imgUnique) {
										var active = $('.swiper-slide.swiper-zoom-container.swiper-slide-active').data('swiper-slide-index') + 1;
										document.cookie = opts.cookieId + '=' + active + '; path=/;';
									}
								},
								onInit: function(sw) {
									setHeight();
									$(opts.id).hover(
										function() {
											sw.enableKeyboardControl();
											sw.stopAutoplay();
										},
										function() {
											sw.disableKeyboardControl();
											sw.startAutoplay();
										}
									);
								},
								onAfterResize: setHeight
							};
							opts.flipRate = parseInt(opts.flipRate);
							if (opts.flipRate > 0)
								swOpts.autoplay = opts.flipRate * 1000;
							var sw = new Swiper(opts.id + ' .gallery', swOpts);
							ZIPGallery.swiper[opts.id] = sw;
							if(opts.flipRate > 0 && $(opts.id).outerHeight() === 0) {
								sw.stopAutoplay();
							}
							if (opts.showit) {
								$(opts.id + ' .gallery .swiper-slide').addClass('clickable').click(function() {
									var active = $(this).data('swiper-slide-index') + 1;
									var WxH = opts.subWidth + 'x' + opts.subHeight;
									var dl = opts.inhibitDownload ? ' target="gallery-x3#zzun!"' : '';
									execGallery(opts.zipId, 
										$("<a data-caption='" + opts.subCaption.replace(/"/, '\&quot;')
												+ "' data-thumbsize='" + WxH + "'" + dl + " data-index='" + active + "' />"),
										galleryName);
								});
							}
						}
						ZIPGallery.swiper[opts.id].opts = opts;
					};
					constructSwiper(opts);
					/*
					 * window resize handler
					 */
					$(window).resize(function() {
						if (ZIPGallery && ZIPGallery.swiper) {
							for (var id in ZIPGallery.swiper) {
								//
								// replace max-width in img src attribnutes
								//
								var $sw = $(id);
								var opts = ZIPGallery.swiper[id].opts;
								var swiperWidth = $sw.outerWidth();
								if (opts.swiperWidth !== swiperWidth) {
									var swiperHeight = $sw.outerHeight();
									$sw.empty();
									if (swiperHeight > 0) {
										constructSwiper(opts);
										if (opts.flipRate > 0) {
											//
											var sw = ZIPGallery.swiper[id];
											if (!sw.autoplaying && swiperHeight > 0)
												sw.startAutoplay();
										}
									}

									opts.swiperWidth = swiperWidth;
								}
							}
						}
					});
				}
			}
		});
	};

	$(document).ready(function() {
		/*
		 * process all links identifying ZIP gallery files
		 */
		$('a.gallery, a[target^=gallery]').each(function() {
			//
			// remove file extension '.zip'
			//
			var $a = $(this);
			var zipUrl = $a.attr('href');
			var galleryName = zipUrl.replace('.zip', ''); 
			$a.attr('href', galleryName);
			//
			// set click handler
			//
			$a.click(function(e) {
				e.preventDefault();
				//
				// resolve zip path, if not *.zip
				//
				if (zipUrl === galleryName) {
					//
					// resolving zip url is used for concrete5 redactor editor entries
					//
					resolveUrl(zipUrl, function(url) {
						var galleryName = url.replace('.zip', ''); 
						$a.attr('href', galleryName);
						execGallery(url, $a, galleryName);
					});
				} else {
					execGallery(zipUrl, $a, galleryName);
				}
			});
		});
		if (location.search !== '') {
			var params = location.search.substring(1).split('\&');
			var param = {};
			for (var i = 0; i < params.length; i++) {
				var tmp = params[i].split('=');
				param[tmp[0]] = tmp[1];
			}
			if (typeof param.gallery !== 'undefined') {
				/*
				 * GET parameter keeps gallery link to execute
				 */
				var index = typeof param.index !== 'undefined' ? param.index : 1;
				var $a = $('<a data-index="' + index + '">');
				execGallery(CCM_APPLICATION_URL + '/' + param.gallery, $a, param.gallery.replace('.zip', ''));
			}
		}
	});
}) (window.jQuery);