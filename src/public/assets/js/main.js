    "use strict";


    var windowOn = $(window);
  
    // PreLoader Js
    $(window).on('load', function(event) {
      $('.preloader').delay(500).fadeOut(500);
    });

    /*--
        Header Sticky
    -----------------------------------*/

    window.onscroll = function () {
        const left = document.getElementById("header");

        if (left.scrollTop > 50 || self.pageYOffset > 50) {
            left.classList.add("sticky")
        } else {
            left.classList.remove("sticky");
        }
    }  

     /*--    
        Tabs
    -----------------------------------*/  
    const tabs = document.querySelectorAll('[data-tab-target]')
    const tabContents = document.querySelectorAll('.edubin-tab-pane')

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = document.querySelector(tab.dataset.tabTarget)
            tabContents.forEach(tabContent => {
                tabContent.classList.remove('active')
            })
            tabs.forEach(tab => {
                tab.classList.remove('active')
            })
            tab.classList.add('active')
            target.classList.add('active')
        })
    })


  /*--
        Mobile Menu
  -----------------------------------*/
	$('#mobile-menu').meanmenu({
		meanMenuContainer: '.mobile-menu',
		meanScreenWidth: "991",
		meanExpand: ['<i class="fas fa-plus"></i>'],
	});

   /*--
        Sidebar Js
  -----------------------------------*/
	$(".sidebar-toggle-btn").on("click", function () {
		$(".sidebar__area").addClass("sidebar-opened");
		$(".body-overlay").addClass("opened");
	});
	$(".sidebar__close-btn").on("click", function () {
		$(".sidebar__area").removeClass("sidebar-opened");
		$(".body-overlay").removeClass("opened");
	});



	/*--
        Body overlay Js
  -----------------------------------*/
	$(".body-overlay").on("click", function () {
		$(".sidebar__area").removeClass("sidebar-opened");
		$(".body-overlay").removeClass("opened");
	});


    /*--
        Search Js
    -----------------------------------*/
	var $searchWrap = $('.search-wrap');
	var $navSearch = $('.search-btn');
	var $searchClose = $('#search-close');

	$('.search-btn').on('click', function (e) {
		e.preventDefault();
		$searchWrap.animate({ opacity: 'toggle' }, 500);
		$navSearch.add($searchClose).addClass("open");
	});

	$('.search-close').on('click', function (e) {
		e.preventDefault();
		$searchWrap.animate({ opacity: 'toggle' }, 500);
		$navSearch.add($searchClose).removeClass("open");
	});

	function closeSearch() {
		$searchWrap.fadeOut(200);
		$navSearch.add($searchClose).removeClass("open");
	}

	$(document.body).on('click', function (e) {
		closeSearch();
	});

	$(".search-btn, .main-search-input").on('click', function (e) {
		e.stopPropagation();
	});


  
    /*--
        Countdown
    -----------------------------------*/
    function makeTimer($endDate, $this, $format) {
      var today = new Date();
      var BigDay = new Date($endDate),
        msPerDay = 24 * 60 * 60 * 1000,
        timeLeft = (BigDay.getTime() - today.getTime()),
        e_daysLeft = timeLeft / msPerDay,
        daysLeft = Math.floor(e_daysLeft),
        e_hrsLeft = (e_daysLeft - daysLeft) * 24,
        hrsLeft = Math.floor(e_hrsLeft),
        e_minsLeft = (e_hrsLeft - hrsLeft) * 60,
        minsLeft = Math.floor((e_hrsLeft - hrsLeft) * 60),
        e_secsLeft = (e_minsLeft - minsLeft) * 60,
        secsLeft = Math.floor((e_minsLeft - minsLeft) * 60);
  
      var yearsLeft = 0;
      var monthsLeft = 0
      var weeksLeft = 0;
  
      if ($format != 'short') {
        if (daysLeft > 365) {
          yearsLeft = Math.floor(daysLeft / 365);
          daysLeft = daysLeft % 365;
        }
  
        if (daysLeft > 30) {
          monthsLeft = Math.floor(daysLeft / 30);
          daysLeft = daysLeft % 30;
        }
        if (daysLeft > 7) {
          weeksLeft = Math.floor(daysLeft / 7);
          daysLeft = daysLeft % 7;
        }
      }
  
      var yearsLeft = yearsLeft < 10 ? "0" + yearsLeft : yearsLeft,
        monthsLeft = monthsLeft < 10 ? "0" + monthsLeft : monthsLeft,
        weeksLeft = weeksLeft < 10 ? "0" + weeksLeft : weeksLeft,
        daysLeft = daysLeft < 10 ? "0" + daysLeft : daysLeft,
        hrsLeft = hrsLeft < 10 ? "0" + hrsLeft : hrsLeft,
        minsLeft = minsLeft < 10 ? "0" + minsLeft : minsLeft,
        secsLeft = secsLeft < 10 ? "0" + secsLeft : secsLeft,
        yearsText = yearsLeft > 1 ? 'Years' : 'year',
        monthsText = monthsLeft > 1 ? 'Months' : 'month',
        weeksText = weeksLeft > 1 ? 'Weeks' : 'week',
        daysText = daysLeft > 1 ? 'Days' : 'day',
        hourText = hrsLeft > 1 ? 'Hours' : 'Hr',
        minsText = minsLeft > 1 ? 'Mints' : 'min',
        secText = secsLeft > 1 ? 'Secs' : 'sec';
  
      var $markup = {
        wrapper: $this.find('.countdown__item'),
        year: $this.find('.yearsLeft'),
        month: $this.find('.monthsLeft'),
        week: $this.find('.weeksLeft'),
        day: $this.find('.daysLeft'),
        hour: $this.find('.hoursLeft'),
        minute: $this.find('.minsLeft'),
        second: $this.find('.secsLeft'),
        yearTxt: $this.find('.yearsText'),
        monthTxt: $this.find('.monthsText'),
        weekTxt: $this.find('.weeksText'),
        dayTxt: $this.find('.daysText'),
        hourTxt: $this.find('.hoursText'),
        minTxt: $this.find('.minsText'),
        secTxt: $this.find('.secsText')
      }
  
      var elNumber = $markup.wrapper.length;
      $this.addClass('item-' + elNumber);
      $($markup.year).html(yearsLeft);
      $($markup.yearTxt).html(yearsText);
      $($markup.month).html(monthsLeft);
      $($markup.monthTxt).html(monthsText);
      $($markup.week).html(weeksLeft);
      $($markup.weekTxt).html(weeksText);
      $($markup.day).html(daysLeft);
      $($markup.dayTxt).html(daysText);
      $($markup.hour).html(hrsLeft);
      $($markup.hourTxt).html(hourText);
      $($markup.minute).html(minsLeft);
      $($markup.minTxt).html(minsText);
      $($markup.second).html(secsLeft);
      $($markup.secTxt).html(secText);
  }
  
  $('.countdown').each(function () {
      var $this = $(this);
      var $endDate = $(this).data('countdown');
      var $format = $(this).data('format');
      setInterval(function () {
        makeTimer($endDate, $this, $format);
      }, 0);
  });

  /*--
		nice select
	-----------------------------------*/
	$('select').niceSelect();

  
    /*--    
      main Slider
    -----------------------------------*/  
    var swiper = new Swiper(".slider-active", {
      spaceBetween: 0,
      effect: "fade",
      loop: true,
      autoplay: {
        delay: 6000,
      },
      navigation: {
        nextEl: ".swiper-button-next",
        prevEl: ".swiper-button-prev",
      },
      thumbs: {
        swiper: swiper,
      },
    });


  /*--    
    Counter Up
  -----------------------------------*/  

    $('.counter').counterUp({
      delay: 10,
      time: 1500,
  });

  /*--    
    Progress Bar
  -----------------------------------*/  

  if($('.progress-line').length) {
    $('.progress-line').appear(function(){
        var el = $(this);
        var percent = el.data('width');
        $(el).css('width', percent+'%');
    }, {accY: 0});
  }

  /*--
		Mousemove Parallax
	-----------------------------------*/
  var b = document.getElementsByTagName("BODY")[0];  

  b.addEventListener("mousemove", function(event) {
  parallaxed(event);

  });

  function parallaxed(e) {
      var amountMovedX = (e.clientX * -0.3 / 8);
      var amountMovedY = (e.clientY * -0.3 / 8);
      var x = document.getElementsByClassName("parallaxed");
      var i;
      for (i = 0; i < x.length; i++) {
          x[i].style.transform='translate(' + amountMovedX + 'px,' + amountMovedY + 'px)'
      }
  }



  /*--    
        Brand Active
    -----------------------------------*/
    var swiper = new Swiper(".brand-active .swiper-container", {
      slidesPerView: 5,
      spaceBetween: 30,
      loop: true,
      breakpoints: {
        0: {
          slidesPerView: 1,
        },
        576: {
          slidesPerView: 2,
          spaceBetween: 20,
        },
        768: {
          slidesPerView: 3,
        },
        992: {
          slidesPerView: 5,
        },
      },
    });

    /*--    
        Brand Active
    -----------------------------------*/
    var swiper = new Swiper(".brand-2-active .swiper-container", {
      slidesPerView: 6,
      spaceBetween: 30,
      loop: true,
      breakpoints: {
        0: {
          slidesPerView: 1,
        },
        576: {
          slidesPerView: 2,
          spaceBetween: 20,
        },
        768: {
          slidesPerView: 3,
        },
        992: {
          slidesPerView: 6,
        },
      },
    });

  
  
   /*--    
        Category Active
    -----------------------------------*/
    var swiper = new Swiper(".category-active .swiper-container", {
      slidesPerView: 3,
      spaceBetween: 30,
      loop: true,
      navigation: {
          nextEl: '.category-active .swiper-button-next',
          prevEl: '.category-active .swiper-button-prev',
      },
      breakpoints: {
        0: {
          slidesPerView: 1,
        },
        576: {
          slidesPerView: 2,
          spaceBetween: 20,
        },
        768: {
          slidesPerView: 3,
        },
        992: {
          slidesPerView: 3,
        },
      },
  });

   /*--
        Testimonial Active
    -----------------------------------*/
    var swiper = new Swiper('.testimonial-active', {
      slidesPerView: 2,
      spaceBetween: 30,
      loop: true,
      pagination: {
          el: ".swiper-pagination",
          clickable: true,
      },
      breakpoints: {
        0: {
          slidesPerView: 1,
        },
        576: {
          slidesPerView: 1,
        },
        768: {
          slidesPerView: 1,
        },
        992: {
          slidesPerView: 2,
        }
      }
    });

  /*--
    Testimonial-2 Active
  -----------------------------------*/
  var swiper = new Swiper('.student-active', {
    slidesPerView: 1,
    spaceBetween: 30,
    loop: true,  
    pagination: {
      el: ".student-active .swiper-pagination",
      clickable: true,
    },      
  });

   /*--
        Testimonial-2 Active
	-----------------------------------*/
  var swiper = new Swiper('.testimonial-2-active', {
    slidesPerView: 3,
    spaceBetween: 20,
    loop: true,
    pagination: {
      el: ".testimonial-2-active .swiper-pagination",
      clickable: true,
    },
    breakpoints: {
      0: {
        slidesPerView: 1,
      },
      576: {
        slidesPerView: 1,
      },
      768: {
        slidesPerView: 2,
        centeredSlides: false,
      },
      992: {
        slidesPerView: 2,
      },
      1200: {
        slidesPerView: 3,
      }
    }
  });

 /*--
        Testimonial-3 Active
	-----------------------------------*/
  var swiper = new Swiper('.testimonial-3-active', {
    slidesPerView: 1,
    spaceBetween: 30,
    loop: true,        
    pagination: {
        el: ".testimonial-3-active .swiper-pagination",
        clickable: true,
    },
  });


  /*--    
        Testimonial Four Active
    -----------------------------------*/
    var swiper = new Swiper(".testimonial-04-active", {
      slidesPerView: 2,
      spaceBetween: 30,
      loop: true,
      pagination: {
          el: ".testimonial-04-active .swiper-pagination",
          clickable: true,
      },
      breakpoints: {
        0: {
          slidesPerView: 1,
        },
        576: {
          slidesPerView: 1,
          spaceBetween: 20,
        },
        768: {
          slidesPerView: 1,
        },
        992: {
          slidesPerView: 1,
        },
        1200: {
          slidesPerView: 2,
        },
      },
  });

  /*--
        Course Active
	-----------------------------------*/
  var swiper = new Swiper('.course-active', {
    slidesPerView: 3,
    spaceBetween: 20,
    loop: true,
    navigation: {
      nextEl: '.course-active .swiper-button-next',
      prevEl: '.course-active .swiper-button-prev',
    },
    breakpoints: {
      0: {
        slidesPerView: 1,
      },
      576: {
        slidesPerView: 1,
      },
      768: {
        slidesPerView: 2,
        centeredSlides: false,
      },
      992: {
        slidesPerView: 2,
      },
      1200: {
        slidesPerView: 3,
      }
    }
  });

   /*--    
    swiper thumb gallery
  -----------------------------------*/ 

  var Nav = new Swiper(".gallery-nav .swiper-container", {
    spaceBetween: 8,
    slidesPerView: 4,
    loop: true,
    navigation: {
    nextEl: ".gallery-nav .swiper-button-next",
    prevEl: ".gallery-nav .swiper-button-prev",
    },
  });

  var gallery = new Swiper(".gallery .swiper-container", {
      spaceBetween: 1,
      spaceBetween: 0,
      loop: false,
      navigation: false,
      thumbs: {
      swiper: Nav,
      },
  });


  /*--
    magnificPopup video view 
  -----------------------------------*/	
	$('.popup-video').magnificPopup({
		type: 'iframe'
	});

  /*--
      Quantity plus minus
    ----------------------------------------- */
    $(".cart-plus-minus").prepend('<div class="dec qtybutton">-</div>');
    $(".cart-plus-minus").append('<div class="inc qtybutton">+</div>');
    $(".qtybutton").on("click", function() {
        var $button = $(this);
        var oldValue = $button.parent().find("input").val();
        if ($button.text() == "+") {
            var newVal = parseFloat(oldValue) + 1;
        } 
        else {
            if (oldValue > 0) {
                var newVal = parseFloat(oldValue) - 1;
            } 
            else {
                newVal = 0;
            }
        }
        $button.parent().find("input").val(newVal);
    });

    /*--
        Year
    -----------------------------------*/  

    document.getElementById("year").textContent = new Date().getFullYear();

   /*--
        AOS
    -----------------------------------*/   
    AOS.init({
      duration: 1200,
      once: true,
  });
