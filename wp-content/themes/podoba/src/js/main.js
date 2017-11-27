'use strict';

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