//jquery scrollTo plugin.
$.fn.scrollTo = function( target, options, callback ){
    if(typeof options == 'function' && arguments.length == 2){ callback = options; options = target; }
    var settings = $.extend({
        scrollTarget  : target,
        offsetTop     : 50,
        duration      : 500,
        easing        : 'swing'
    }, options);
    return this.each(function(){
        var scrollPane = $(this);
        var scrollTarget = (typeof settings.scrollTarget == "number") ? settings.scrollTarget : $(settings.scrollTarget);
        var scrollY = (typeof scrollTarget == "number") ? scrollTarget : scrollTarget.offset().top + scrollPane.scrollTop() - parseInt(settings.offsetTop);
        scrollPane.animate({scrollTop : scrollY }, parseInt(settings.duration), settings.easing, function(){
            if (typeof callback == 'function') { callback.call(this); }
        });
    });
}
$(function(){
	var DELIVERY_AJAX_URL = $('#delivery_url').text();
	$('.for_click').click(function(){
		var e = $(this);
		var deliveryId = e.data('logistics');
		var content = '';
		if(whetherExit(e)){
			hideDiv(e)
		}else{
			$.ajax({
				url:DELIVERY_AJAX_URL,
				type:'post',
				data:{delivery_id:deliveryId},
				dataType:'json',
				success:function(jsondata){
					if(jsondata.success){
						content = jsondata.data;
						var tempStr = '';
						for(var i = 0;i<content.data.length;i++){
							tempStr += content.data[i].time + '&nbsp;&nbsp;&nbsp;&nbsp;' + content.data[i].context + '<br/>';
						}
						showDiv(e,tempStr);
					}else{
						showDiv(e,jsondata.error);
					}
				},
				error:function(){
					showDiv(e,'：( 抱歉，快递公司的服务器不给力，请稍后重试手气吧！');
				}
			});	
		}
	});
	function showDiv(target,content){
		target.parent().parent('.mage-body').after('<div class="for_div welfare-wl">'+ content +'</div>');
	}
	function whetherExit(target){
		return target.parent().parent().next().is('.for_div');
	}
	function hideDiv(target){
		target.parent().parent().next('.for_div').remove();
	}
	function getWiller(){
		var AJAX_URL = $('#birth_for_url').text();
		$.ajax({
	        url:AJAX_URL,
	        type:'post',
	        data:{},
	        dataType:'json',
	        success:function(jsonData){
	            if(jsonData.success){
	                 $('#willer_num').text(jsonData.data.length);
	                for(var i = 0,len = jsonData.data.length; i < len; i++){
	                    var targetName = (i + 1 >= len) ? (jsonData.data[i].name) : (jsonData.data[i].name+'、');
	                    $('#birth_sendwiller').append(targetName);
	                }
	            }else{
	                $('#willer_div').text(jsonData.error)
	            }
	        },
	        error:function(){
	            $('#willer_div').text('网络连接错误，请刷新页面重试')
	        }
	    })
	}
	getWiller();
    //personal page show navigator for new user.
})
