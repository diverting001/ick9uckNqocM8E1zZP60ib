var limit_str = function(str,limit_size){
    str = str.replace(/(^\s*)|(\s*$)/g,'');
    var limit_str = "";
    var length = 0;
    for(i = 0; i < str.length; i++) {
        iCode = str.charCodeAt(i);
        if((iCode >= 0 && iCode <= 255) || (iCode >= 0xff61 && iCode <= 0xff9f)) {
            length += 1;
        } else {
            length += 2;
        }
        if(length > limit_size){
            limit_str += "...";
            return limit_str;
        }else{
            limit_str += str.charAt(i);
        }
    }
    return limit_str;
 };
 var selectCheckbox = function(target, allTarget) {
	target.click(function() {
		if ($(this).hasClass('selected')) {
			$(this).removeClass('selected');
			$(this).find('i').addClass('dis');
		} else {
			$(this).addClass('selected');
			$(this).find('i').removeClass('dis');
		}
	});
	var i = 0;
	allTarget.click(function() {
		if (i % 2) {
			target.removeClass('selected');
			target.find('i').addClass('dis');
		} else {
			target.addClass('selected');
			target.find('i').removeClass('dis');
		}
		i++;
	})
};
var layer = function(obj, target, width) {
	var adapt = function(){
		var window_hg = $(window).height();
		var dialog_hg = target.children('.layer-dialog').height(); 
		if((dialog_hg+60)<window_hg){
			target.children('.layer-backdrop').height(window_hg);
		}else{
			target.children('.layer-backdrop').height(dialog_hg+60);
		}
	}
	var show = function() {
		target.css('display', 'block');
		if (navigator.userAgent.indexOf("Mac OS X") > 0) {
			$('body').addClass('os-body');
		}else{
			//navigator.userAgent.indexOf("Window")
			$('body').addClass('win-body');
		}
	};
	var hide = function() {
		target.css('display', 'none');
		if (navigator.userAgent.indexOf("Mac OS X") > 0) {
			$('body').removeClass('os-body');
		} else {
			$('body').removeClass('win-body');
		}
	}
	target.find('.layer-dialog').width(width);
	target.find('.close').click(function() {
		hide();
	}); 
	obj.click(function() {
		show();
		adapt();
	});
	window.onresize = function(){
		adapt();
	};
};
