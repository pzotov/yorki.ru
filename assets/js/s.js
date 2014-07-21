$(function(){
	$(".dropdown").each(function(){
		$(this).parent().mouseenter(function(){
			$(".cmenu__item-a",this).addClass("cmenu__item-a_open");
			$(".dropdown",this).show();
		}).mouseleave(function(){
			$(".cmenu__item-a",this).removeClass("cmenu__item-a_open");
			$(".dropdown",this).hide();
		});
	});
	$(".cmenu__items").height( $(".cmenu__items").height()-40 );
	$(".news__item").mouseenter(function(){
		$(this).addClass("news__item_hover");
	}).mouseleave(function(){
		$(this).removeClass("news__item_hover");
	});

	$(".special__size-a").click(function(e){
		e.preventDefault();
		var box = $(this).parents(".special__item").first();
		$(".special__size-a",box).removeClass("special__size-a_active");
		$(this).addClass("special__size-a_active");
		$(".good-name",box).val( $(this).data("name") );
		$(".good-hash",box).val( $(this).data("hash") );
	});

	$(".tovar__size-a").click(function(e){
		e.preventDefault();
		$(".tovar__size-a").removeClass("tovar__size-a_active");
		$(this).addClass("tovar__size-a_active");
		$(".good-name").val( $(this).data("name") );
		$(".good-hash").val( $(this).data("hash") );
	});

	$(".podbor__radio").click(function(e){
		e.preventDefault();
		if( $(this).hasClass("podbor__radio_active") ){
			$(this).removeClass("podbor__radio_active");
			if( $(this).hasClass("podbor__radio_size") ) $("#s_size").val( 0 );
			if( $(this).hasClass("podbor__radio_status") ) $("#s_status").val( 0 );
		} else {
			$(this).parent().find(".podbor__radio").removeClass("podbor__radio_active");
			$(this).addClass("podbor__radio_active");
			if( $(this).hasClass("podbor__radio_size") ) $("#s_size").val( $(this).data("id") );
			if( $(this).hasClass("podbor__radio_status") ) $("#s_status").val( $(this).data("id") );
		}
	});

	var p = $(".podbor__row_prices");
	p.slider({
		range: true,
		min: p.data("min"),
		max: p.data("max"),
		values: [ parseInt( $("#s_price0").val() ), parseInt( $("#s_price1").val() ) ],
		slide: function(event, ui){
			$("#s_price0").val(ui.values[0]);
			$("#s_price1").val(ui.values[1]);
		}
	});

	if (SVG.supported) {
		$(".special__head").each(function(idx){
			var id = "special__head_"+idx,
				$t = $(this),
				text = $t.html(),
				size = $t.css("font-size"),
				family = $t.css("font-family"),
				w = $t.width(),
				h = $t.height()
				;
			$(this).attr("id",id).empty();

			var draw = SVG(id).size( w, h );
			var gradient = draw.gradient('linear', function(stop) {
				stop.at(.21, '#814a2d');
				stop.at(.24, '#fdd870');
				stop.at(.27, '#fef8d1');
				stop.at(.84, '#b27a32');
			}).from(0, 0).to(0, 1);
			draw.text(text).font({
				family: family
				, size: size
				, anchor: 'middle'
				, leading: 1
			}).fill(gradient).move(353,0);
		});
		$(".special__image_round").each(function(idx){
			var id = "special__image_"+idx,
				$t = $(this).attr("id",id),
				$im = $("img",this),
				imsrc = $im.attr("src"),
				w = $im.width()*625/$im.height(),
				h=625;

			$t.removeClass("special__image_round").empty();
			var draw = SVG(id);
			draw.viewbox(70,48,555,555)
				.size(200,200)
				.path("m 345.69585,51.426146 c -13.95603,-0.2825 -26.10076,7.95827 -36.81252,15.99751 -8.27509,6.25881 -17.72525,12.50557 -28.56108,11.83226 -15.57151,-0.67084 -31.05641,-6.82719 -46.68285,-3.10894 -13.21331,2.70159 -22.54533,13.70967 -28.64727,25.059704 -6.23263,10.94749 -11.29193,24.15196 -23.39829,29.92786 -14.22518,6.79324 -31.19003,7.18701 -43.94934,17.1717 -11.44724,8.3544 -15.7033,22.94708 -16.32864,36.50926 -0.95835,12.29682 0.3728,26.13563 -7.83892,36.39883 -9.65479,11.96196 -24.128694,19.62498 -31.538754,33.4905 -6.79444,11.6215 -5.80023,26.08995 -0.87875,38.20686 4.29211,12.1348 12.25981,24.01999 10.42519,37.43943 -2.509,15.1903 -12.62912,28.08197 -14.09164,43.57163 -1.80753,12.75528 3.88666,25.41476 12.80838,34.31691 8.41369,9.29759 20.200034,15.65525 26.227424,26.98529 5.70319,12.56441 2.91689,26.79794 5.45795,40.02947 1.72579,12.60748 8.65225,24.79318 20.02536,31.01314 12.7452,7.8238 28.43603,8.14608 41.41313,15.36322 11.52085,7.02612 15.7573,20.63093 22.65713,31.47067 6.61765,11.52692 17.45254,21.71877 31.12577,23.38656 15.26955,2.43256 30.02334,-4.14138 45.21138,-3.93235 12.93639,0.54898 22.97569,9.89029 33.24018,16.66893 9.61068,6.76011 20.943,12.37127 33.02288,11.22569 14.50873,-1.03243 26.37553,-10.34505 37.66864,-18.60186 7.51449,-5.66658 16.58718,-10.09987 26.23486,-9.1371 15.61814,1.13775 31.39472,7.08918 47.01621,2.62298 13.50332,-3.51795 22.27708,-15.51445 28.36759,-27.29784 5.60877,-10.53175 11.22286,-22.51713 22.77847,-27.65623 13.73523,-6.29355 29.87228,-6.84215 42.27267,-16.14006 11.34611,-7.85815 16.18569,-21.85075 17.03645,-35.11667 1.26207,-12.68706 -0.69123,-26.95384 7.48514,-37.78507 9.63868,-12.33331 24.46294,-20.04209 32.01092,-34.1381 6.829,-11.61218 5.7503,-26.0684 0.9135,-38.20056 -4.3949,-12.38135 -12.64943,-24.59767 -10.28543,-38.32006 2.95864,-15.46201 13.37333,-28.74857 14.14723,-44.77689 1.2418,-13.29483 -5.8538,-25.72826 -15.29789,-34.53871 -8.50661,-8.71987 -20.17112,-15.18261 -24.91105,-26.9499 -4.70165,-13.46209 -1.7559,-28.12923 -5.28708,-41.84604 -2.53217,-12.53309 -11.08893,-23.59767 -22.79084,-28.85661 -12.51025,-6.45302 -27.40459,-6.74856 -39.37484,-14.35812 -11.61186,-8.26181 -15.38251,-22.85816 -23.36032,-34.027574 -6.78396,-10.58069 -17.75906,-19.16024 -30.63507,-20.0934 -14.9387,-1.80345 -29.3055,4.521 -44.17118,4.09847 -12.29646,-0.76371 -21.90226,-9.55661 -31.71871,-16.028 -9.1251,-6.32361 -19.49535,-12.34572 -30.98599,-11.87679 z")
				.stroke({ color: '#fff', width: 7 })
				.fill(draw.image(imsrc, w, h).move( Math.round(383-w/2),0));
			;
		});
	}

	$(".otzyvy").each(function(){
		$(this).tabs({
			active: $(this).data("active")
		});
	});
/*
	var h =200,
		w = 200,
		n = 14,
		r = 100,
		r1 = 95,
		path = "",
		x0 = w/2,
		y0 = h/2,
		angle = Math.PI/ n,
		angle_shift = angle/3,
		x, y,
		px, py;

	for( i=0; i<n*2; i++ ){
		x = x0 + r*Math.cos(i*angle);
		y = y0 + r*Math.sin(i*angle);
		path += " M " + x + "," + y + " ";

	}
	path += " z";

	SVG('svg1')
		.size( w, h)
		.viewbox(0,0,w,h)
		.stroke({ color: '#fff', width: 3 })
		.path( path )
		;
*/
	$(".slides").each(function(){
		var slider = this,
			$items = $(".slides__item",this),
			cnt = $items.size(),
			current = -1,
			slider__timer = null,
			slider_hovered = false,
			slider_animated = false;

		$(this).mouseenter(function() {
			slider_hovered = true;
			clearTimeout( slider__timer );
		}).mouseleave(function() {
			slider_hovered = false;
			slider__timer = setTimeout( nextSlide, 3000 );
		});
		
		function showSlide(index){
			if( current == index ) return;
			clearTimeout( slider__timer );
			current = index;
			$items.removeClass("slides__item_active").eq(index).addClass("slides__item_active");
			$(".slides__arrow_right",slider).find("img").attr("src", $items.eq((index+1)%cnt).find(".slides__image").attr("src"));
			$(".slides__arrow_left",slider).find("img").attr("src", $items.eq((index-1+cnt)%cnt).find(".slides__image").attr("src"));
		}
		
		function nextSlide(){
			showSlide( (current+1)%cnt );
		}
		
		$(".slides__arrow", this).click(function(e){
			e.preventDefault();

			if( $(this).hasClass("slides__arrow_left") ) showSlide( (current-1+cnt)%cnt );
			else showSlide( (current+1)%cnt );
		});

		showSlide( 0 );
		//slider__timer = setTimeout( nextSlide, 3000 );
	});

	$(".fancybox").fancybox({
		afterLoad:function(){
			if(this.type=='ajax') this.content = this.content.replace(/\?isNaked=1/,'');
		},
		helpers: {
			overlay: {
				locked: false
			}
		}
	});
});

