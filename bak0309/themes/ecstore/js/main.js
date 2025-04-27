$(function(){
	var b = $('.userBar-out').width();
	$('.userBar-div').width(b-2);
	var out = $('.cart-out');
	var div = $('.cart-div');
	var cart = $('.cart');
	var uout = $('.userBar-out');
	var udiv = $('.userBar-div');
	var ubar = $('.userBar');
	out.mouseover(function(){
		div.show();
		cart.css({'background':'#fff','border-left':'1px solid #e7e7e7','border-right':'1px solid #e7e7e7','padding':'0 19px'});
	});
	out.mouseout(function(){
		div.hide();
		cart.css({'background':'none','border':'0','padding':'0 20px'});
	});
	uout.mouseover(function(){
		udiv.show();
		ubar.css({'border-left':'1px solid #e7e7e7','border-right':'1px solid #e7e7e7','padding':'0 19px'});
	});
	uout.mouseout(function(){
		udiv.hide();
		ubar.css({'border':'0','padding':'0 20px'});
	})
	$('.contact a').hover(function(){
		var b = $(this).children('img').attr('src');
		var bbb = b.slice(-4);
		$(this).children('img').attr('src',""+b.split('.',1)+1+b.slice(-4)+"")
	},function(){
		var b = $(this).children('img').attr('src');
		$(this).children('img').attr('src',""+b.split('1',1)+b.slice(-4)+"")
	});
	var up = $('.firm-info-2 .pack');
	up.on('click',function(){
		$(this).siblings('div').slideToggle(300,function(){
			$(this).siblings('h4').children('small').toggleClass('dis');
		});
		//alert($(this).html())
		if($(this).html()=='编辑'){
			$(this).html('收起')
		}else{
			$(this).html('编辑')
		}
	});
	/**退货协议check **/
	var $check = $('.return-check');
		$check.click(function(){
			if($check.is(":checked")){
				$('#returnBtn').click(function(){
					window.location.href='returnStep2.html'
				})
			}else{
				$('#returnBtn').off('click')
			}
		})
	/**退货单号验证**/
	$(".return-hydh input[name='hydh']").focus(function(){
			//$(this).val("");
		}).blur(function(){
			if($(this).val()==''){
				$(this).css({'border':'1px solid #e00505','background':'#fff3e4'});
				$('.return-hydh p').fadeIn(200);
	
			}else{
				$(this).css({'border':'1px solid #cfd1d8','background':'#fff'});
				$('.return-hydh p').fadeOut(200);
			}
		});
})