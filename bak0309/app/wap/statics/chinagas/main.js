/**
 * 设备判断
 */
var isPhone = (window.navigator.platform != "Win32");                            //是否手机
var isAndroid = (window.navigator.userAgent.indexOf('Android') > -1)?true:false; //是否安卓
var isIPhone = (window.navigator.userAgent.indexOf('iPhone') > -1)?true:false;   //是否苹果
var isIPad = (window.navigator.userAgent.indexOf('iPad') > -1)?true:false;      // 是否iPad


var ToHeader20=1;   //头部是否有状态栏
if(ToHeader20){
    add20ToHeader();
}

function $$(id)
{
    return document.getElementById(id);
}

/**
 * @param String inWndName 新窗口名称
 * @param String html		新窗口路径
 * @param String inAniID	打开动画
 * @param String f
 */
function openNewWin(name,url,anim,f){  //url方式载入,全屏宽高，无扩展参数
    if(isAndroid){
        uexWindow.open(name,'0',url,anim, '','', '0',300);//由右往左切入;区分推入，默认260毫秒
    }else{
        uexWindow.open(name,'0',url,anim,'','',(f)?f:0,300);
    }
}

/**
 * 关闭窗口
 * @param string n 关闭窗口动画，默认-1
 */
function winClose(n){
    if(n===0){
        uexWindow.close(0,0);
    }
	uexWindow.close(n);	
}

/*
 * 关闭浮窗
 * @param string name 浮窗名  
 */
function popClose(name){
    uexWindow.closePopover(name);
}

/**
 * localStorage保存数据
 * @param String key  保存数据的key值
 * @param String value  保存的数据
 */
function setLocVal(key,value){
	window.localStorage[key] = value;
}

/**
 * 根据key取localStorage的值
 * @param Stirng key 保存的key值
 */
function getLocVal(key){
	if(window.localStorage[key])
		return window.localStorage[key];
	else
		return "";
}

/**
 * 清除缓存
 * @param Striong key  保存数据的key，如果不传清空所有缓存数据
 */
function clearLocVal(key){
	if(key)
		window.localStorage.removeItem(key);
	else
		window.localStorage.clear();
}

/**
 * alert 和 confirm 弹出框
 * @param String str 提示语
 * @param Function callback confirm的回调函数
 * @author hyt 2015/09/25 1:33pm
 */
function alert(str,callback){
	if(callback){
		appcan.window.confirm('提示',str,['确定','取消'],function(err,data,dataType,optId){
            if(data == 0)
                callback(err,data,dataType); 
        })
	}else{
	    appcan.window.alert('提示',str,'确定',function(){});
	}
		
}
/**
 * 在其他窗口中执行指定主窗口中的代码
 * @param String wn  需要执行代码窗口的名称
 * @param String scr 需要执行的代码
 */
function uescript(wn, scr){
	uexWindow.evaluateScript(wn,'0',scr);
}

/**
 * 在其他窗口中执行指定浮动窗口中的代码
 * @param String wn  需要执行代码浮动窗口所在的主窗口的名称
 * @param String pn  需要执行代码的浮动窗口的名称
 * @param String scr 需要执行的代码
 */
function ueppscript(wn, pn, scr){
	uexWindow.evaluatePopoverScript(wn,pn,scr);
}

/**
 * 判断是否是空
 * @param value
 */
function isDefine(value){
    if(value == null || value == "" || value == "undefined" || value == undefined || value == "null" || value == "(null)" || value == '--' || typeof(value) == 'undefined'){
        return false;
    }
    else{
		value = value+"";
        value = value.replace(/\s/g,"");
        if(value == ""){
            return false;
        }
        return true;
    }
}
function orEmpty(value){
    if(value == null || value == "" || value == "undefined" || value == undefined || value == "null" || value == "(null)" || value == '--' || typeof(value) == 'undefined'){
        return '';
    }
    else{
        value = value+"";
        value = value.replace(/\s/g,"");
        if(value == ""){
            return '';
        }
        return value;
    }
}



/**
 * 给DOM对象赋值innerHTML
 * @param String id 对象id或者对象
 * @param String html html字符串
 * @param String showstr 当html不存在时的提示语
 */
function setHtml(id, html,showstr) {
	var showval = isDefine(showstr)? showstr : "";
	if ("string" == typeof(id)) {
		var ele = $$(id);
		if (ele != null) {
			ele.innerHTML = isDefine(html) ? html : showval;
		}else{
			alert("没有id为"+id+"的对象");
		}
	} else if (id != null) {
		id.innerHTML = isDefine(html) ? html : showval;
	}
}

/**
 * 设置平台弹动效果
 * @param int sta   0=无弹动效果   1=默认弹动效果  2=设置图片弹动
 * @param Function downcb 顶部下拉下拉触发函数       传空即无下拉效果
 * @param Function upcb   底部上拉                传空即无上拉效果
 */
function setPageBounce(sta,downcb, upcb,upTime){
	if(sta == 0) return;
	var color = "#FFF";
	if(sta == 1){
		var s = ['0', '0'];
		var str = '';
		uexWindow.onBounceStateChange = function (type,status){
			if(downcb && type==0 && status==2) downcb();
			if(upcb && type==1 && status==2) upcb();
		}
		
		uexWindow.setBounce("1");
		
		if(downcb){
			s[0] = '1';
			uexWindow.notifyBounceEvent("0","1");
		}
		if(color){
			uexWindow.showBounceView("0",color,s[0]);
		}else{
			uexWindow.showBounceView("0","rgba(255,255,255,0)",s[0]);
		}
		
		
		if(upcb){
			s[1] = '1';
			uexWindow.notifyBounceEvent("1","1");
		}
		if(color){
			uexWindow.showBounceView("1",color,s[1]);
		}else{
			uexWindow.showBounceView("1","rgba(255,255,255,0)",s[1]);
		}
	//	uexWindow.showBounceView("1","rgba(255,255,255,0)",s[1]);
	}
	if(sta == 2){
		uexWindow.onBounceStateChange = function (type,status){
			uexLog.sendLog("type="+type+"||status="+status);
			if(downcb && type==0 && status==2) downcb();
			if(upcb && type==1 && status==2) upcb();
		}
		uexWindow.setBounce("1");
		var inJson ='{"imagePath":"res://loading.gif","textColor":"#530606","pullToReloadText":"拖动刷新","releaseToReloadText":"释放刷新", "levelText":"更新时间:'+upTime+'","loadingText":"加载中，请稍等1","loadingImagePath":"res://loading.gif"}'
		var inJson2 ='{"imagePath":"res://loading.gif","textColor":"#530606","pullToReloadText":"拖动刷新","releaseToReloadText":"释放刷新", "levelText":"","loadingText":"加载中，请稍等2","loadingImagePath":"res://loading.gif"}' ;//上拉加载
		if(downcb){
		    uexWindow.setBounceParams('0', '{"imagePath":"res://loading.gif","pullToReloadText":"下拉刷新","releaseToReloadText":"释放刷新","loadingText":"加载中，请稍候","loadingImagePath":"res://loading.gif"}', 'donghang');
			//uexWindow.setBounceParams('0',inJson2);
			uexWindow.showBounceView('0',color,1);
			uexWindow.notifyBounceEvent('0',1);	
		}
		if(upcb){
			var inJson3 ='{"imagePath":"res://loading.gif","textColor":"#530606","pullToReloadText":"拖动刷新","releaseToReloadText":"释放刷新","loadingText":"加载中，请稍等","loadingImagePath":"res://loading.gif"}';    //下拉刷新
			uexWindow.setBounceParams('1',inJson3)
			uexWindow.showBounceView('1',color,1);
			uexWindow.notifyBounceEvent('1',1);
		}
	}
}

/***
 * 使弹动重置为初始位置
 * @param String type 弹动的类型 0-顶部弹动  1-底部弹动 
 */
function resetBV(type){
	uexWindow.resetBounceView(type);   //一般用于downcb, upcb函数中，函数结束函数后清除弹动的加载中的动画
}

/**
 * 显示加载框
 * @param String mes 显示的提示语
 * @param String t  毫秒数 窗口存在时间 有的话显示框不显示那个“圈”，并且在t时间后消失
 */
function $toast(mes,t){
	// if(t){
		// toasterror(mes,t);
	// }else{
		// uexWindow.toast('1','5',mes,0);
	// }
	uexWindow.toast(t?'0':'1','5',mes,t?t:0);
}

/**
 * 手动关闭加载框
 */
function $closeToast(){
	uexWindow.closeToast();
}

/**
 * 浮动窗口移动动画函数
 * @param String 横向移动位移
 * @param String 纵向移动位移
 * @param Function 动画结束后的回调函数
 */
function disShowAnim(dx, dy, cb){
	uexWindow.beginAnimition();
	uexWindow.setAnimitionDuration('250');
	uexWindow.setAnimitionRepeatCount('0');
	uexWindow.setAnimitionAutoReverse('0');
	uexWindow.makeTranslation(dx,dy,'0');
	uexWindow.commitAnimition();
	if(cb) uexWindow.onAnimationFinish = cb;
}
/**
 * 创建DOM节点
 * @param String t
 */
function createEle(t){
	return document.createElement(t);
}
/**
 * 删除DOM节点
 * @param String id
 */
function removeNode(id){
	var e = $$(id);
	if(e) e.parentElement.removeChild(e);
}

/**
 * 调用本地浏览器打开网址
 * @param String url
 */
function loadLink(url){
	var appInfo = ''; 
	var filter = '';
	var dataInfo = url.toLowerCase();
	var pf = uexWidgetOne.platformName;
	if(pf=='android'){
		appInfo = 'android.intent.action.VIEW';
		filter = 'text/html';
	}
	if(dataInfo.indexOf('http://')<0 && dataInfo.indexOf('https://')<0){
		dataInfo = 'http://'+dataInfo;
	}
	uexWidget.loadApp(appInfo, filter, dataInfo);
}



/**
*检查网络
*返回值：-1=网络不可用  0=WIFI网络  1=3G网络  2=2G网络
*/

function checkNet(cb){
	uexDevice.cbGetInfo=function (opCode,dataType,data){
        var device = eval('('+data+')');
		var connectStatus=device.connectStatus;
		if(isDefine(connectStatus)){
			cb(connectStatus);
			//if(connectStatus==-1){
			//	console.log('网络状态：网络不可用');
			//}else if(connectStatus==0){
			//	console.log('网络状态：WIFI网络'); 
			//}else if(connectStatus==1){
			//	console.log('网络状态：3G网络'); 
			//}else if(connectStatus==2){
			//	console.log('网络状态：2G网络');
			//}
		}
	}
	uexDevice.getInfo('13');
}


/*
*判断IOS版本号是否大于7，大于7在顶部加20
*为了避免在每个页面都调用这个方法，可以在zy_control.js文件的zy_init()方法中调用。在使用这种方法时，在应用的首页就不要使用zy_init方法了。
*而是改为直接调用该方法，并且传入一个回调函数，在回调函数中在打开浮动窗口，否则就会出现打开的浮动窗口位置错误的问题
*/
/****************设置header字体颜色****************/
function zhycheck(){
    uexWindow.setStatusBarTitleColor(0);
}
function add20ToHeader(cb){
    if (isAndroid){
	   return;		
	}
	if($("#header").length==0||$("#header").length<0){
	   return;  
	}
    if (getLocVal('IOS7Plus')) {
        try {
            if (getLocVal('IOS7Plus') == 2) {
                if ($$('header').style.paddingTop) {
                    $$('header').style.paddingTop = (parseInt($$('header').style.paddingTop) + 18) + 'px';
                }else{
                    $$('header').style.paddingTop = '18px';
                }
            }
        } 
        catch (e) {
        }
		if(cb) cb();
    }else {
        var iPhoneNume=window.navigator.userAgent.indexOf('iPhone OS')+10;
        var iPhoneDate=window.navigator.userAgent.slice(iPhoneNume,iPhoneNume+1);
        if(iPhoneDate>7){
            if ($$('header').style.paddingTop) {
                $$('header').style.paddingTop = (parseInt($$('header').style.paddingTop) + 18) + 'px';
            }else{
                $$('header').style.paddingTop = '18px';
            }
        }else{
            setLocVal('IOS7Plus', 1);
        }
        
        /*uexDevice.cbGetInfo = function(opId, dataType, data){
            if (data) {
                var device = JSON.parse(data);
                var os = parseInt(device.os);
                if (os >= 7) {
                    setLocVal('IOS7Plus',2);
                    if ($$('header').style.paddingTop) {
                        $$('header').style.paddingTop = (parseInt($$('header').style.paddingTop) + 18) + 'px';
                    }
                    else {							
                        $$('header').style.paddingTop = '18px';
                    }
                }
            }
			if(cb) cb();
        };
        uexDevice.getInfo('1');*/
    }
    /*if(isIPhone){
        var headhtml='<div style="position: absolute;top: 0px;height: 20px;width:100%;background-color: #f2f2f2;"></div>';
        $("#header").css("padding-top","20px");
        $("#header").prepend(headhtml);
    }*/
}

function header_padding(){
    if (getLocVal('IOS7Plus') == 2) {
	    $$('header').style.paddingTop = '18px';
        // $$('header_center').style.paddingTop = '20px';
        // $$('header_info').style.paddingTop = '20px';
        // $$('header_active').style.paddingTop = '20px';
        // $$('header_help').style.paddingTop = '20px';
    }
}



/*****返回顶部*****/
function myScroll(){
    var x = document.body.scrollTop || document.documentElement.scrollTop;
    var timer = setInterval(function(){
        x = x - 100;
        if (x < 100) {
            x = 0;
            window.scrollTo(x, x);
            clearInterval(timer);
        }
        window.scrollTo(x, x);
    }, "10");
}

//封装openPop
function zy_con_p(id,url,x,y,fid){
	var s=window.getComputedStyle($$(id),null);
	var ft=window.getComputedStyle($$(fid),null);
	uexWindow.openPopover(id,"0",url,"",parseInt(x),parseInt(y),parseInt(s.width),'',parseInt(s.fontSize),"0");
}

/*
 *为元素添加属性
 *
 */
function AppendAttr(obj){
    var attrItem = "";
    for (key in obj) {
        var obj_key = obj[key]===""?"--":obj[key];
        attrItem += key+ '="' + obj_key +'" ';
    };
    return attrItem;
}
/*
 * 通过元素名获取元素集合
 */
function $$name(obj,NAME){
    return obj.getElementsByTagName(NAME);
}