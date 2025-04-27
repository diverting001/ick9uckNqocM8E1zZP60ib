let fs = require('fs');
let path = require('path');
let asset_filters = ['jquery','zepto','swiper','flexible','iphoneX_bar','clipboard','bootstrap','map.qq.com','res.wx.qq.com','renren.com'];
let store_cdn = 'cdn_base_url';
let club_cdn = 'CLUB_CDN_DOMAIN';
let life_cdn = 'LIFE_CDN_DOMAIN_DYNPTL';
let template_dir = [
    './app/b2c/view/',
    './app/jifen/view/',
    './app/lvyou/view/',
    './themes/ecstore/',
    './wap_themes/'
    
];
const timeline = (() => {
    const time = new Date();
    const Y = time.getFullYear()
    const M = (time.getMonth() + 1).toString()
    const D = time.getDate().toString()
    const h = time.getHours().toString()
    const m = time.getMinutes().toString()
    const s = time.getSeconds().toString()
    // return `${Y}/${M}/${D}-${h}:${m}:${s}`
    return ''+Y+M+D+h+m+s
})()
readdirFile(template_dir)
function readdirFile(files){
    files.forEach((item)=>{
        let status = fs.statSync(path.resolve(item)).isDirectory();
        if(status){
            var files = fs.readdirSync(item)
            var dirname = path.resolve(item)
            var newF = files.map(file=>{
                return dirname +'/'+ file
            })
            readdirFile(newF)
        }else{
            if(/\.html/g.test(item)){
                fileWrite(item)
            }
        }
    })
}
function fileWrite(path){
    let file = fs.readFileSync(path,'utf-8');
    let lines = file.split('\n')
    let content = [];
    lines.forEach(item=>{
        var is_css = item.indexOf('<link');
        var has_css = item.indexOf('.css');
        var is_js = item.indexOf('<script');
        var has_src = item.indexOf('src=');
        let cur_url = '';
        let cur_url_split = '';
        var ary =  item.split(/\s+/)
        let is_filter = false;
        asset_filters.forEach(filte=>{
            if(item.indexOf(filte) != -1) is_filter = true;
        })
        if(item.indexOf(store_cdn)!= -1 ||item.indexOf(club_cdn) != -1 || item.indexOf(life_cdn) != -1){
            if(is_css != -1 && has_css != -1 && !is_filter){
                for (var i = 0;i<ary.length;i++){
                    var css_href = ary[i].match(/href="(.+)"/);  
                    if(css_href){
                        cur_url = css_href[1]
                        cur_url_split = cur_url.split('.css')[0];
                    }
                }
                item = item.replace(cur_url,cur_url_split+'.css?v='+timeline)
            }else if(is_js != -1 && has_src != -1 && !is_filter){
                for (var i = 0;i<ary.length;i++){
                    var js_src = ary[i].match(/src="(.+)"/);  
                    if(js_src){
                        cur_url = js_src[1]
                        cur_url_split = cur_url.split('.js')[0];
                    }
                }
                item = item.replace(cur_url,cur_url_split+'.js?v='+timeline)
            }
        }
        content.push(item)

    })
    //文件重写
    fs.writeFileSync(path, content.join('\n'), 'utf8', (err) => {
        if (err) throw err;
    });
}   

