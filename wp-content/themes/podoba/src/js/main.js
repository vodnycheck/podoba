'use strict';

//albums
buildAlbumsUI();
function buildAlbumsUI() {
	var albumInfo = {},
	$originalAlbums = $('.albums__all .album'),
	$originalPhotos = $('.albums__list'),
	$newAlbums = $('.js-our-works-wrap'),
	$colorPicker = $('<ul class="color-picker"></ul>');

	$originalAlbums.each(function(index){
		albumInfo.name = $(this).find('.wppa-title a').text();
		albumInfo.number = parseInt($(this).attr('id').match(/\d+/)[0]) - 1;
		albumInfo.description = $(this).find('.wppa-box-text-desc').text();
		albumInfo.coverImageUrl = $(this).find('img').attr('src').replace('thumbs/', '');
		albumInfo.images = $originalPhotos.find('.wppa-container').eq(albumInfo.number).find('img');
		albumInfo.tags = [];

		var wrap = $('<div class="col-12 col-lg-6 set row"></div>');
		var $image = $('<div class="work-examples col-12" style="background-image: url(' + albumInfo.coverImageUrl + ');"></div>');
		var $description = $('<div class="work-examples-text col-11"><p class="js-show-more" data-max-height="60"><strong>' + albumInfo.name + ' </strong>' + albumInfo.description + '</p></div>');
		var $photos = getImagesArray(albumInfo.images, albumInfo.tags);

		function getImagesArray(images, tags){
			var result = $('<ul class="work-examples__album"></ul>');

			images.each(function(){
				var photoThumbSrc = $(this).attr('src'),
					photoTags = $(this).attr('data-tags'),
					photoDesc = $(this).attr('data-desc'),
					photoOriginSrc = photoThumbSrc.replace('thumbs/', ''),
					$newListItem = $('<li/>'),
					$newHref = $('<a/>'),
					$newImg = $('<img/>');

				$newHref.attr('href', photoOriginSrc);
				$newImg.attr('src', photoThumbSrc);
				$newImg.attr('data-tags', photoTags);
				$newImg.attr('data-desc', photoDesc);

				$newListItem.append($newHref);
				$newHref.append($newImg);
				result.append($newListItem);

				var isTagRepeat = false;
				tags.forEach(function(item){
					if (item === photoTags) isTagRepeat = true;
				});
				if (!isTagRepeat && photoTags !== '') {
					tags.push(photoTags);
				}
			});

			return result;
		};


		$newAlbums.append(wrap);
		wrap.append($image).append($description).append($photos);

		(function(array){
			$image.on('click', function() {
				$colorPicker.html('');
				$.fancybox.open($photos.find('a'));
				var tags = array;

				if (tags.length > 1) {
					tags.forEach(function(item){
						var $colorPickerElement,
							colorCode = item.replace(/c/i, '');

						if (item.search(/^c([a-f,0-9]{3}|[a-f,0-9]{6})$/i) !== -1) {
							$colorPickerElement = $('<li style="background-color: #' + colorCode + '" data-color="' + colorCode + '"></li>');
						} else {
							$colorPickerElement = $('<li class="non-color" data-color="' + colorCode + '"></li>');
						}

						$colorPickerElement.on('click', function(){
							var newImagesArray = [],
								colorCode = $(this).attr('data-color');

								$photos.find('img').each(function(index, item){
									if ($(item).attr('data-tags').replace(/c/i, '') === colorCode) {newImagesArray.push($(item).closest('a'));}
								});

								$.fancybox.close( true );
								$.fancybox.open( newImagesArray );

						});
						$colorPicker.append($colorPickerElement);
					});
				}

				$('body').append($colorPicker);
			})
		})(albumInfo.tags)
	});

	$(document).on('beforeClose.fb', function( e, instance, slide ) {
		$colorPicker.fadeOut();
	});

	$(document).on('beforeLoad.fb', function( e, instance, slide ) {
		$colorPicker.fadeIn();
	});
}

var slideIndex = 0;


function counter(i){
	slideIndex = slideIndex + i;
	return slideIndex;
}

(function hide(){
	var slides = $(".my-slides");
	for(var i=9; i<slides.children().length; i++){
		slides.children("a:eq("+i+")").css("display","none")
	}
})();

 $(document).ready(function(){
     var mySwiper = new Swiper ('.swiper-container', {
         slidesPerView: 3,
         slidesPerColumn: 3,
         spaceBetween: 20,
         //slidesPerGroup: 3,

         // Navigation arrows
         navigation: {
             nextEl: '.swiper-button-next',
             prevEl: '.swiper-button-prev',
         }
     })

     //$('body').append(`<style></style>`);
     $('.js-show-more').each(function(){
         var $mainBlock = $(this);
         var maxHeight = $mainBlock.attr('data-max-height');
         var initialTextHeight = 0;
         var $readMore = $('<a class="js-read-more" href="#">читать далее</a>');
         //var $readLess = $('<a class="js-read-less" href="#">скрыть</a>');
         var $overlapBlock = $('<div class="limit-text"></div>');
         var text = $mainBlock.html();

         $mainBlock.css({'height': maxHeight});
         $mainBlock.html($overlapBlock).append($readMore);
         $overlapBlock.html(text);
         //$overlapBlock.append($readLess);
         initialTextHeight = $overlapBlock.outerHeight();

         $readMore.on('mouseover', expand);
         //$readLess.on('click', shrink);
         $mainBlock.on('mouseleave', shrink);

         function expand(e) {
             e.preventDefault();
             //$readMore.hide();
             //$readLess.show();
             $overlapBlock.css('max-height', 250);
             $mainBlock.closest('.set').css({'z-index': 1, 'position': 'relative'});
         }
         function shrink(e) {
             e.preventDefault();
             //$readLess.hide();
             $readMore.show();
             $overlapBlock.css('max-height', '');
             setTimeout(function(){
                 $mainBlock.closest('.set').css({'z-index': '', 'position': ''});
             },300);
         }
     });
});


 ////////////////////////
(function ($) {
  'use strict';

  var defaults = {};

  function Sidenav (element, options) {
    this.$el = $(element);
    this.opt = $.extend(true, {}, defaults, options);

    this.init(this);
  }

  Sidenav.prototype = {
    init: function (self) {
      self.initToggle(self);
      self.initDropdown(self);
    },

    initToggle: function (self) {
      $(document).on('click', function (e) {
        var $target = $(e.target);

        if ($target.closest(self.$el.data('sidenav-toggle'))[0]) {
          self.$el.toggleClass('show');
          $('body').toggleClass('sidenav-no-scroll');

          self.toggleOverlay();

        } else if (!$target.closest(self.$el)[0]){
          self.$el.removeClass('show');
          $('body').removeClass('sidenav-no-scroll');

          self.hideOverlay();
        }
      });
        document.body.addEventListener('touchstart', function(e){
           
           var $target = $(e.target);

        if ($target.closest(self.$el.data('sidenav-toggle'))[0]) {
          self.$el.toggleClass('show');
          $('body').toggleClass('sidenav-no-scroll');

          self.toggleOverlay();

        } else if (!$target.closest(self.$el)[0]){
          self.$el.removeClass('show');
          $('body').removeClass('sidenav-no-scroll');

          self.hideOverlay();
            }

        }, false);
    },



    initDropdown: function (self) {
      self.$el.on('click', '[data-sidenav-dropdown-toggle]', function (e) {
        var $this = $(this);

        $this
          .next('[data-sidenav-dropdown]')
          .slideToggle('fast');

        $this
          .find('[data-sidenav-dropdown-icon]')
          .toggleClass('show');

        e.preventDefault();
      });
    },

    toggleOverlay: function () {
      var $overlay = $('[data-sidenav-overlay]');

      if (!$overlay[0]) {
        $overlay = $('<div data-sidenav-overlay class="sidenav-overlay"/>');
        $('body').append($overlay);
      }

      $overlay.fadeToggle('fast');
    },

    hideOverlay: function () {
      $('[data-sidenav-overlay]').fadeOut('fast');
    }
  };

  $.fn.sidenav = function (options) {
    return this.each(function() {
      if (!$.data(this, 'sidenav')) {
        $.data(this, 'sidenav', new Sidenav(this, options));
      }
    });
  };
})(window.jQuery);

$('[data-sidenav]').sidenav();