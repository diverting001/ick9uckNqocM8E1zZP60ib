(function($){
    var data = [];
    var subData = {};
    var match_phone = /^[0-9]{11}$/;
    var regx_null = /^\s*$/;
    var expressAry = JSON.parse($('input[name=reason_list]').val());
        
        $.each(expressAry, function (index, obj) {
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
                    if (obj.value == data[0]) {
                        $('input[name=reason_id]').val(obj.key);
                        if(obj.key == 13){
                            $('.rests_reason').removeClass('hide');
                        }else{
                            $('.rests_reason').addClass('hide');
                        }
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
        var _that = $(this);
        subData.name = $('input[name=refund_name]').val();
        subData.mobile = $('input[name=mobile]').val();
        subData.order_id = $('input[name=order_id]').val();
        var refund_reason_show = $('#refund_reason_show').val();
        if(refund_reason_show && refund_reason_show == 1){
            subData.reason_id = $('input[name=reason_id]').val();
            if(subData.reason_id == 13) subData.other_reason = $('input[name=rests_reason]').val();
            if (regx_null.test(subData.reason_id)) {
                alertTips('请选择退款原因');
                return;
            }
            if (subData.reason_id == 13 && regx_null.test(subData.other_reason)) {
                alertTips('其他原因不能为空');
                return;
            }
        }else{
            subData.reason_id =$('#reason_id').val();
            subData.other_reason = '其它原因';
        }
        if (regx_null.test(subData.name)) {
            alertTips('退款人不能为空');
            return;
        }

        if (!match_phone.test(subData.mobile)) {
            alertTips('手机号码格式不正确');
            return;
        }
        if(_that.hasClass('disabled')) return;
        _that.addClass('disabled');
        $.ajax({
            url: "/m/member-ajaxCancelOrder.html",
            data: subData,
            type: "post",
            dataType: "json",
            success: function (res) {
                if (res.result){
                    $('.order-box').hide();
                    $('.order-box3').removeClass('hide')
                } else {
                    alertTips('申请失败')
                    _that.removeClass('disabled');
                }

            },
            error: function (err) {
                alertTips(err);
            }
        });
    })
})(Zepto);