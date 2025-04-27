#!/usr/bin/env bash
cp -R -a app/b2c/neigou_statics/$1/* app/b2c/neigou_statics/normal 2> /tmp/autoci.ecstore.err
if [ $? -eq 0 ]; then
    echo "图片替换成功";
    else

    echo $(cat /tmp/autoci.ecstore.err) >&2;
    echo "图片替换失败" >&2;
    rm /tmp/autoci.ecstore.err;
    exit 1;
fi

cp -R -a app/jifen/$1/* app/jifen/normal 2> /tmp/autoci.ecstore.err
if [ $? -eq 0 ]; then
    echo "jifen图片替换成功";
    else

    echo $(cat /tmp/autoci.ecstore.err) >&2;
    echo "jifen图片替换失败" >&2;
    rm /tmp/autoci.ecstore.err;
    exit 1;
fi

cp -R -a themes/ecstore/block/$1/* themes/ecstore/block/normal 2> /tmp/autoci.ecstore.err
if [ $? -eq 0 ]; then
    echo "模版替换成功";
    else

    echo $(cat /tmp/autoci.ecstore.err) >&2;
    echo "模版替换失败" >&2;
    rm /tmp/autoci.ecstore.err;
    exit 1;
fi

pc_replace_env=("juyoufuli" "ruantong")
if echo "${pc_replace_env[@]}" | grep -w $1 &>/dev/null;then
    cp -R -a app/b2c/view/site/product/$1/* app/b2c/view/site/product/normal 2> /tmp/autoci.ecstore.err
    if [ $? -eq 0 ]; then
        echo "PC商品详情模版替换成功";
        else

        echo $(cat /tmp/autoci.ecstore.err) >&2;
        echo "PC商品详情模版替换失败" >&2;
        rm /tmp/autoci.ecstore.err;
        exit 1;
    fi

    cp -R -a app/b2c/view/wap/product/$1/* app/b2c/view/wap/product/normal 2> /tmp/autoci.ecstore.err
    if [ $? -eq 0 ]; then
        echo "wap商品详情模版替换成功";
        else

        echo $(cat /tmp/autoci.ecstore.err) >&2;
        echo "wap商品详情模版替换失败" >&2;
        rm /tmp/autoci.ecstore.err;
        exit 1;
    fi
fi




echo "执行Python替换脚本"
python ./autoci.py $1

cd  config
if [ -f config.php.online ];then
    ln -snf config.php.online config.php
    echo "配置文件config.php替换成功"
else
    echo "配置文件config.php.online 不存在"
fi

if [ -f neigou_config.php.online.$1 ];then
    ln -snf neigou_config.php.online.$1 neigou_config.php
    echo "$1 配置文件neigou_config.php替换成功"
else
    echo "配置文件neigou_config.php.online.$1 不存在"
fi
