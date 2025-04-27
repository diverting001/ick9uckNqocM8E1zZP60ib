#!/usr/bin/python
# coding=UTF-8
import os
import os.path
import re
import sys

def file_extension(path):
        return os.path.splitext(path)[1]

g = os.walk(r".")

if sys.argv[1] == 'zhongliang':
        for path,dir_list,file_list in g:
                for file_name in file_list:
                        # print(os.path.join(path, file_name) )
                        real_name = os.path.join(path, file_name)
                        # print(real_name)
                        extension = file_extension(real_name)
                        if extension in ['.html','.js','.tmpl','.php']:
                                if real_name not in [
                                    './app/b2c/lib/thirdsupplier/catadapter.php',
                                    './app/b2c/lib/thirdsupplier/typeadapter.php'
                                ]:
#                                     print(real_name)
                                    f1 = open(real_name,'r+')
                                    finfo = f1.read()
                                    # 关键词修改
                                    line_new = re.sub(r'点滴关怀','中粮我买',finfo)
                                    line_new = re.sub(r'内购积分','企业购积分',line_new)
                                    line_new = re.sub(r'内购网','中粮企业购',line_new)
                                    line_new = re.sub(r'内购','中粮',line_new)
                                    line_new = re.sub(r'400-666-6365','0562-7111906',line_new)
                                    line_new = re.sub(r'周一至周日','工作日',line_new)
                                    line_new = re.sub(r'9:00-21:00','9:00-18:00',line_new)
                                    line_new = re.sub(r'关注公众号实时了解订单状态','扫码收藏手机版，实时了解订单情况',line_new)
                                    line_new = re.sub(r'VARIABLES_PLATFORM_NAME','zhongliang',line_new)


                                    f1.seek(0)
                                    f1.truncate()
                                    f1.write(line_new)
                                    f1.close()
if sys.argv[1] == 'juyoufuli':
        dirlist = os.walk(r"./app/b2c")
        for path,dir_list,file_list in dirlist:
                for file_name in file_list:
                        # print(os.path.join(path, file_name) )
                        real_name = os.path.join(path, file_name)
                        # print(real_name)
                        extension = file_extension(real_name)
                        if extension in ['.html','.js','.tmpl','.css','.php']:
                                if real_name not in [
                                    './app/b2c/lib/thirdsupplier/catadapter.php',
                                    './app/b2c/lib/thirdsupplier/typeadapter.php'
                                ]:
#                                     print(real_name)
                                    f1 = open(real_name,'r+')
                                    finfo = f1.read()
                                    # 关键词修改
                                    line_new = re.sub(r'#004076','#CD2426',finfo)
                                    line_new = re.sub(r'#004076','#CD2426',line_new)
                                    line_new = re.sub(r'#1560A0','#A91315',line_new)
                                    line_new = re.sub(r'#CDE2F3','#FDF6F7',line_new)
                                    line_new = re.sub(r'#CDE2F3','#FFBFBF',line_new)
                                    line_new = re.sub(r'#cff3ff','#FFFFFF',line_new)
                                    line_new = re.sub(r'require_hide_block_env','hide_block_env',line_new)
                                    line_new = re.sub(r'require_block_float','block_float',line_new)
                                    line_new = re.sub(r'礼品卡','聚优卡',line_new)
                                    line_new = re.sub(r'点滴关怀','聚优福利',line_new)
                                    line_new = re.sub(r'内购积分','优积分',line_new)
                                    line_new = re.sub(r'内购网','聚优福利',line_new)
                                    line_new = re.sub(r'内购','聚优',line_new)
                                    line_new = re.sub(r'#CDE2F3','#FFBFBF',line_new)
                                    line_new = re.sub(r'周一至周日','工作日',line_new)
                                    line_new = re.sub(r'9:00-21:00','9:00-18:00',line_new)
                                    line_new = re.sub(r'VARIABLES_PLATFORM_NAME','juyoufuli',line_new)

                                    # 统计js替换
                                    line_new = re.sub(r'<!--STATISTICS-SPACE-->','<div style="display:none"><script type="text/javascript" src="https://s9.cnzz.com/z_stat.php?id=1280158633&web_id=1280158633"></script></div>',line_new)

                                    f1.seek(0)
                                    f1.truncate()
                                    f1.write(line_new)
                                    f1.close()

        dirlist = os.walk(r"./themes/ecstore/block")
        for path,dir_list,file_list in dirlist:
                for file_name in file_list:
                        # print(os.path.join(path, file_name) )
                        real_name = os.path.join(path, file_name)
                        # print(real_name)
                        extension = file_extension(real_name)
                        if extension in ['.html']:
                                # print(real_name)

                                f1 = open(real_name,'r+')
                                finfo = f1.read()
                                # 关键词修改
                                line_new = re.sub(r'#004076','#CD2426',finfo)
                                line_new = re.sub(r'#004076','#CD2426',line_new)
                                line_new = re.sub(r'#1560A0','#A91315',line_new)
                                line_new = re.sub(r'#1560A0','#A91315',line_new)
                                line_new = re.sub(r'#CDE2F3','#FDF6F7',line_new)
                                line_new = re.sub(r'#CDE2F3','#FFBFBF',line_new)
                                line_new = re.sub(r'#cff3ff','#FFFFFF',line_new)
                                line_new = re.sub(r'周一至周日','工作日',line_new)
                                line_new = re.sub(r'9:00-21:00','9:00-18:00',line_new)
                                line_new = re.sub(r'require_hide_block_env','hide_block_env',line_new)
                                line_new = re.sub(r'require_block_float','block_float',line_new)
                                line_new = re.sub(r'VARIABLES_PLATFORM_NAME','juyoufuli',line_new)
                                f1.seek(0)
                                f1.truncate()
                                f1.write(line_new)
                                f1.close()
        dirlist = os.walk(r"./wap_themes")
        for path,dir_list,file_list in dirlist:
                for file_name in file_list:
                        # print(os.path.join(path, file_name) )
                        real_name = os.path.join(path, file_name)
                        # print(real_name)
                        extension = file_extension(real_name)
                        if extension in ['.html']:
                                # print(real_name)

                                f1 = open(real_name,'r+')
                                finfo = f1.read()
                                # 关键词修改
                                line_new = re.sub(r'VARIABLES_PLATFORM_NAME','juyoufuli',finfo)
                                f1.seek(0)
                                f1.truncate()
                                f1.write(line_new)
                                f1.close()
if sys.argv[1] == 'ruantong':
        dirlist = os.walk(r"./app/b2c")
        for path,dir_list,file_list in dirlist:
                for file_name in file_list:
                        # print(os.path.join(path, file_name) )
                        real_name = os.path.join(path, file_name)
                        # print(real_name)
                        extension = file_extension(real_name)
                        if extension in ['.html','.js','.tmpl','.css','.php']:
                                if real_name not in [
                                    './app/b2c/lib/thirdsupplier/catadapter.php',
                                    './app/b2c/lib/thirdsupplier/typeadapter.php'
                                ]:
#                                     print(real_name)
                                    f1 = open(real_name,'r+')
                                    finfo = f1.read()
                                    # 关键词修改
                                    line_new = re.sub(r'require_hide_block_env','hide_block_env',line_new)
                                    line_new = re.sub(r'require_block_float','block_float',line_new)
                                    line_new = re.sub(r'礼品卡','通行卡',line_new)
                                    line_new = re.sub(r'点滴关怀','软通动力',line_new)
#                                     line_new = re.sub(r'内购积分','优积分',line_new)
                                    line_new = re.sub(r'内购网','软通动力',line_new)
                                    line_new = re.sub(r'内购','软通',line_new)
#                                     line_new = re.sub(r'周一至周日','工作日',line_new)
                                    line_new = re.sub(r'9:00-21:00','9:00-18:00',line_new)
                                    line_new = re.sub(r'VARIABLES_PLATFORM_NAME','ruantong',line_new)

                                    f1.seek(0)
                                    f1.truncate()
                                    f1.write(line_new)
                                    f1.close()

        dirlist = os.walk(r"./themes/ecstore/block")
        for path,dir_list,file_list in dirlist:
                for file_name in file_list:
                        # print(os.path.join(path, file_name) )
                        real_name = os.path.join(path, file_name)
                        # print(real_name)
                        extension = file_extension(real_name)
                        if extension in ['.html']:
                                # print(real_name)

                                f1 = open(real_name,'r+')
                                finfo = f1.read()
                                # 关键词修改
#                                 line_new = re.sub(r'周一至周日','工作日',line_new)
#                                 line_new = re.sub(r'9:00-21:00','9:00-18:00',line_new)
                                line_new = re.sub(r'require_hide_block_env','hide_block_env',line_new)
                                line_new = re.sub(r'require_block_float','block_float',line_new)
                                line_new = re.sub(r'VARIABLES_PLATFORM_NAME','ruantong',line_new)
                                f1.seek(0)
                                f1.truncate()
                                f1.write(line_new)
                                f1.close()
        dirlist = os.walk(r"./wap_themes")
        for path,dir_list,file_list in dirlist:
                for file_name in file_list:
                        # print(os.path.join(path, file_name) )
                        real_name = os.path.join(path, file_name)
                        # print(real_name)
                        extension = file_extension(real_name)
                        if extension in ['.html']:
                                # print(real_name)

                                f1 = open(real_name,'r+')
                                finfo = f1.read()
                                # 关键词修改
                                line_new = re.sub(r'VARIABLES_PLATFORM_NAME','ruantong',finfo)
                                f1.seek(0)
                                f1.truncate()
                                f1.write(line_new)
                                f1.close()

if sys.argv[1] == 'fuxi':
        dirlist = os.walk(r"./app/b2c")
        for path,dir_list,file_list in dirlist:
                for file_name in file_list:
                        # print(os.path.join(path, file_name) )
                        real_name = os.path.join(path, file_name)
                        # print(real_name)
                        extension = file_extension(real_name)
                        if extension in ['.html','.js','.tmpl','.css','.php']:
                                if real_name not in [
                                    './app/b2c/lib/thirdsupplier/catadapter.php',
                                    './app/b2c/lib/thirdsupplier/typeadapter.php'
                                ]:
#                                     print(real_name)
                                    f1 = open(real_name,'r+')
                                    finfo = f1.read()
                                    # 关键词修改
                                    line_new = re.sub(r'#004076','#004076',finfo)
                                    line_new = re.sub(r'#004076','#004076',line_new)
                                    #按钮hover色
                                    line_new = re.sub(r'#1560A0','#1560A0',line_new)
                                    line_new = re.sub(r'#1560A0','#1560A0',line_new)
                                    # 企业标识背景色
                                    line_new = re.sub(r'#CDE2F3','#CDE2F3',line_new)
                                    #侧导渐变色
                                    line_new = re.sub(r'#00559C','#00559C',line_new)
                                    line_new = re.sub(r'require_hide_block_env','hide_block_env',line_new)
                                    line_new = re.sub(r'require_block_float','block_float',line_new)
                                    line_new = re.sub(r'礼品卡','福喜卡',line_new)
                                    line_new = re.sub(r'点滴关怀','福喜',line_new)
                                    line_new = re.sub(r'内购积分','福喜积分',line_new)
                                    line_new = re.sub(r'内购网','福喜',line_new)
                                    line_new = re.sub(r'内购','福喜',line_new)
                                    line_new = re.sub(r'周一至周日','工作日',line_new)
                                    line_new = re.sub(r'9:00-21:00','9:00-18:00',line_new)

                                    line_new = re.sub(r'VARIABLES_PLATFORM_NAME','fuxi',line_new)

                                    # 统计js替换
                                    #line_new = re.sub(r'<!--STATISTICS-SPACE-->','<div style="display:none"><script type="text/javascript" src="https://s9.cnzz.com/z_stat.php?id=1280158633&web_id=1280158633"></script></div>',line_new)

                                    f1.seek(0)
                                    f1.truncate()
                                    f1.write(line_new)
                                    f1.close()

        dirlist = os.walk(r"./themes/ecstore/block")
        for path,dir_list,file_list in dirlist:
                for file_name in file_list:
                        # print(os.path.join(path, file_name) )
                        real_name = os.path.join(path, file_name)
                        # print(real_name)
                        extension = file_extension(real_name)
                        if extension in ['.html']:
                                # print(real_name)

                                f1 = open(real_name,'r+')
                                finfo = f1.read()
                                # 关键词修改
                                line_new = re.sub(r'#004076','#004076',finfo)
                                line_new = re.sub(r'#004076','#004076',line_new)
                                line_new = re.sub(r'#1560A0','#1560A0',line_new)
                                line_new = re.sub(r'#CDE2F3','#CDE2F3',line_new)
                                line_new = re.sub(r'周一至周日','工作日',line_new)
                                line_new = re.sub(r'9:00-21:00','9:00-18:00',line_new)
                                line_new = re.sub(r'require_hide_block_env','hide_block_env',line_new)
                                line_new = re.sub(r'require_block_float','block_float',line_new)
                                line_new = re.sub(r'VARIABLES_PLATFORM_NAME','fuxi',line_new)
                                f1.seek(0)
                                f1.truncate()
                                f1.write(line_new)
                                f1.close()
        dirlist = os.walk(r"./wap_themes")
        for path,dir_list,file_list in dirlist:
                for file_name in file_list:
                        # print(os.path.join(path, file_name) )
                        real_name = os.path.join(path, file_name)
                        # print(real_name)
                        extension = file_extension(real_name)
                        if extension in ['.html']:
                                # print(real_name)

                                f1 = open(real_name,'r+')
                                finfo = f1.read()
                                # 关键词修改
                                line_new = re.sub(r'VARIABLES_PLATFORM_NAME','fuxi',finfo)
                                f1.seek(0)
                                f1.truncate()
                                f1.write(line_new)
                                f1.close()