'use strict';

// podoba js


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

    $("#scroll-menu,#bottom-menu,#toggle-menu,#scroll").on("click","a", function (event) {
        event.preventDefault();
        var id  = $(this).attr('href'),
            top = $(id).offset().top;
        $('body,html').animate({scrollTop: top}, 1500);
    });

    $(".slideGallery").click(function(){
        // console.log("wwww");
        var slides = $(".my-slides");

        var increment = this.children[0].name;
        var slidesCount = counter(+increment);

        for(var i=0; i<slides.children().length; i++){
            slides.children("a:eq("+i+")").css("display","none")
        }
        var quantitySlides;
        if ($(window).width() < "1027"){
            quantitySlides = 8;
        } else {
            quantitySlides = 9;
        }

        for(var i=0; i<quantitySlides; i++){  
            var index = (slidesCount+i) % slides.children().length;
            slides.children("a:eq("+index+")").css("display","inline-block")
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







