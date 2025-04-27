
COOKIE = {
	del: function(name, path, domain){
		document.cookie = name + "=" +
			((path) ? "; path=" + path : "") +
			((domain) ? "; domain=" + domain : "") +
			"; expires=Thu, 01-Jan-70 00:00:01 GMT";
	},
	get: function(name){
		var v = document.cookie.match('(?:^|;)\\s*' + name + '=([^;]*)');
		return v ? decodeURIComponent(v[1]) : null;
	},
	set: function(name, value ,expires, path, domain){
		var str = name + "=" + encodeURIComponent(value);
		if (expires != null || expires != '') {
			if (expires == 0) {expires = 100*365*24*60;}
			var exp = new Date();
			exp.setTime(exp.getTime() + expires*60*1000);
			str += "; expires=" + exp.toGMTString();
		}
		if (path) {str += "; path=" + path;}
		if (domain) {str += "; domain=" + domain;}
		document.cookie = str;
	}
};
if(COOKIE.get('listUrl')!=window.location.href){
	COOKIE.del('listUrl')
}else{
	
}

function setAllCookie(f,s,t){
    COOKIE.set('listviewpos_first',f,600,'/');
    COOKIE.set('listviewpos_second',s,600,'/');
    COOKIE.set('listviewpos_third',t,600,'/');
}
function setAllInput(f,s,t,totalPage){
    $('input[name=first]').val(f);
    $('input[name=second]').val(s);
    $('input[name=third]').val(t);
    if(totalPage){$('input[name=totalPage]').val(totalPage);}
    
}

function getBrandDetail(t){
    $.ajax({
        url: '/B2c/getBrandInfo/third/'+t,
        type: 'post',
        success:function(date){
            $('.sellwell-logo ').remove();
            if(date){$('#all_ajax').prepend(date);}
            
        } 
    })   
}