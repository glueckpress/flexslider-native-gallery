;(function($){
	/* Initialize FlexSlider */
	$(window).on('load',function() {
		$.fsargs = {
			 animation:		"slide"
			,controlNav:	false
			,animationLoop:	true
			,slideshow:		false
			,touch:			true
			,start: function(slider){
				$.fs = { // get shortcode params
					 slider				: slider
					,scale				: slider.container.data('scale')
					,translate			: slider.container.data('translate')
					,opacity_duration	: slider.container.data('opacity-duration')
					,transform_duration : slider.container.data('transform-duration')
					,animation			: slider.container.data('animation')
				}
				slider.fsdimensions();
				slider.height($.fs.maxheight)
				.find('.slides').height($.fs.maxheight)
				.find('.focus-shift').height($.fs.maxheight);
				if( $.fs.animation == 'kb') {
					slider.fskenburns();
				}
				slider.removeClass('fsloading').addClass('fsready');
			}
			,before: function(slider){
				slider.find('img.kb').removeClass('kb').attr('style', '');
			}
			,after: function(slider){
				$.fs.slider = slider;
				if( $.fs.animation == 'kb') {
					slider.fskenburns();
				}
			}
		}
		$('.flex-container > .flexslider').flexslider($.fsargs);
	});
	
	/* Image dimension classes & container max-height */
	$.fn.fsdimensions = function(options){
		settings = $.extend({}, $.fs, options);
		this.each(function(){
			$.fs.heights = new Array();
			$(this).find('img').each(function(i){
				var img = new Array($(this).width(), $(this).height()),
					w = img[0],
					h = img[1];
				if( h > w ) {
					$(this).addClass('pt'); // .pt = portrait
				} else if( h < w ) {
					$(this).addClass('ls'); // .ls = landscape
					$.fs.heights.push(h); //  push these to future max-height
				} else {
					$(this).addClass('sq');	// .sq = square
				}
			});
			// Set smallest img.ls height as max-height for container
			$.fs.maxheight = Math.min.apply(Math, $.fs.heights);

			if( settings.animation == 'kb') {
				var $focusshift = settings.slider.find('.focus-shift');
				var $focuswidth = $focusshift.width() - parseInt( settings.translate.replace('px','') );
				$focusshift.css({
					 'width' : $focuswidth + 'px'
					,'-webkit-transform': 'translate(' + settings.translate + ')'
					,'-moz-transform': 'translate(' + settings.translate + ')'
					,'-ms-transform': 'translate(' + settings.translate + ')'
					,'-o-transform': 'translate(' + settings.translate + ')'
					,'transform': 'translate(' + settings.translate + ')'
				});
			}
		});
		return false;
	}
	
	/* Ken Burns effect */
	$.fn.fskenburns = function(options){
		settings = $.extend({}, $.fs, options);
		this.each(function(){
			var $currimg = $(settings.slider.slides[settings.slider.currentSlide]).find('img');
			$currimg.addClass('kb').css({			
				 '-webkit-transition-duration': settings.transform_duration
				,'-moz-transition-duration': settings.transform_duration
				,'-ms-transition-duration': settings.transform_duration
				,'-o-transition-duration': settings.transform_duration
				,'transition-duration': settings.transform_duration
				,'-webkit-transform': 'scale(' + settings.scale + ')'
				,'-moz-transform': 'scale(' + settings.scale + ')'
				,'-ms-transform': 'scale(' + settings.scale + ')'
				,'-o-transform': 'scale(' + settings.scale + ')'
				,'transform': 'scale(' + settings.scale + ')'
			});
			return false;
		});
	}
}(jQuery))