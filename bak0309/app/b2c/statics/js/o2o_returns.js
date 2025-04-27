(function($){
    var data = [];
    var subData = {};
    var match_phone = /^[0-9]{11}$/;
    var regx_null = /^\s*$/;
    var expressAry = JSON.parse($('input[name=reason_list]').val());
        
        $.each(expressAry, function (index, obj) {
            obj = {'key':obj.id,value: obj.name};
            data.push(obj.value)
        })
        var mobileSelect1 = new MobileSelect({
            trigger: '#trigger',
            title: '退款原因',
            wheels: [
                { data: data }
            ],
            position: [0], //初始化定位 打开时默认选中的哪个 如果不填默认为0
            transitionEnd: function (indexArr, data) {
            },
            callback: function (indexArr, data) {
                $('#express-name').text(data[0])
                $.each(expressAry, function (index, obj) {
                    if (obj.name == data[0]) {
                        $('input[name=reason_id]').val(obj.id);
                    }
                })
            }
        });
    
    var alertTips = function(msg){
        var body = $('body'),width,height,win_wd = $(window).width();
        if(body.find('.alert-tips').length) return;
        body.append('<div class="alert-tips"><p>'+ msg +'</p></div>');
        width = body.find('.alert-tips').width();
        height = body.find('.alert-tips').height();
        body.find('.alert-tips').css({
            'left':'50%',
            'margin-top':-height/2,
            'margin-left':-width/2
        });
        setTimeout(function(){
            $('.alert-tips').remove();
        },2000)
    }
    $('#tipsBtn').on('click', function(){
        $('#test-popwindow').show().find('.layer-shade').show()
    })
    $('.btn-center').on('click', function(){
        $('#test-popwindow').hide().find('.layer-shade').hide()
    })
    $('#submit_btn').on('click', function(){
        // order_id，name，mobile，statistics_status，return_reason
        subData.statistics_status = $('input[name=reason_id]').val();
        subData.return_reason = $('#trigger').text();
        subData.name = $('input[name=refund_name]').val();
        subData.mobile = $('input[name=mobile]').val();
        subData.order_id = $('input[name=order_id]').val();
        if (regx_null.test(subData.statistics_status)) {
            alertTips('请选择退款原因');
            return;
        } 
        if (regx_null.test(subData.name)) {
            alertTips('退款人不能为空');
            return;
        }

        if (!match_phone.test(subData.mobile)) {
            alert(subData.mobile)
            alertTips('手机号码格式不正确');
            return;
        }
        $.ajax({
            url: "/m/member-o2o_returns_apply.html",
            data: subData,
            type: "post",
            dataType: "json",
            success: function (res) {
                if (res.result){
                    $('.order-box').hide();
                    $('.order-box3').removeClass('hide')
                } else {
                    alertTips('申请失败')
                }

            },
            error: function (err) {
                alertTips(err);
            }
        });
    })
})(Zepto);