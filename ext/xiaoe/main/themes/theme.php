<?php
//动态引入样式
$style = <<<EOF
<style>
    /**
    输入框
     */
    .am-input-control {
        background-color: rgba(255, 255, 255, .2) !important;
    }

    /**
    列表
     */
    .List {
        background: rgb($color)!important;
    }

    /**
    回答
     */
    .FormHead .jiqianswer .answer {
        background-color: rgba(255, 255, 255, .08) !important;
    }

    /**
    checkone点击元素
     */
    .clickitem {
        background-color: rgba(255, 255, 255, .15) !important;
        border-bottom-color: rgba(255, 255, 255, .08) !important;
    }

    /**
    checkone底部按钮父元素背景
     */
    .CHECK_INTENTION .FormBody .bottom_btn {
        background-color: rgba(255, 255, 255, .09) !important;
    }

    /**
    checkone底部按钮元素背景
     */
    .CHECK_INTENTION .FormBody .bottom_btn .flex span {
        background-color:rgb($color)!important;
    }

    /**
    列表背景
     */
    .FINISH .FormBody .finish_box .telBox {
        background-color: rgba(255, 255, 255, .08) !important;
    }

    /**
    列表底部边框颜色
     */
    .FINISH .FormBody .finish_box .borderLine {
        border-top-color: rgba(255, 255, 255, 0.1) !important;
    }

    /**
    列表前图片的样式
     */
    .FINISH .FormBody .finish_box .telBox .one .head {
        background: rgba($color, 0.7) !important;
    }

    .Home #siric {
        background: rgb($color) !important;
    }

    /**
    表单背景
     */
    .CHECK_FORM .FormBody .FORM {
        background-color: rgba(255, 255, 255, 0.08) !important;
    }

    .CHECK_FORM .FormBody .bottom_btn {
        background-color: rgba(255, 255, 255, 0.08) !important;
    }

    .am-list-item .am-list-line .am-list-extra {
        background-color: rgba(255, 255, 255, 0.08) !important;
    }

    .am-list-item .am-textarea-control textarea {
        background-color: rgba(255, 255, 255, 0.08) !important;
    }

    .bottom_btn .flex span {
        background-color: rgba($color, 0.8) !important;
    }
    .CHECK_ONE .bottom_btn{
        background-color: rgba(255, 255, 255, .08) !important;
        border-color: rgba(255, 255, 255, .08) !important;;
    }
    .CHECK_ONE  .bottom_btn .clickitem{
        background-color: rgba($color, 0.8) !important;
    }
    .FINISH .finish_box .blog_query li{
        background: rgba(255,255,255,0.06) !important;
        border-bottom: .01333rem solid rgba(255,255,255,0.2) !important;;
    }
    .FINISH .holidayDiv .lineTips{
      background: rgba(255,255,255,0.2) !important;
    }
    .FINISH .holidayDiv .lineBottom{
       background: rgba(255,255,255,0.12) !important;
       border-bottom-color:rgba(255,255,255,0.2) !important;
    }
    /**
    天气背景
     */
    .weatherlist{
       background-color: rgba(255, 255, 255, .2) !important;
    }
    .CHECK_LEAVE .FormBody .bottom_btn {
        background: rgba(255,255,255,0.2) !important;
    }
    .FAQ2 .scroll_wrap .wrap .list{
         background: rgba(255,255,255,0) !important;
         border-bottom-color:rgba(255,255,255,0.2) !important;
    }
    /**
    帮助文字
     */
    .FAQ .faqListBgColor{
        color: #eeeeee!important;
    }
    /*.seeVedio a{*/
        /*text-decoration: none!important;*/
    /*}*/
    /**
    客户导航地图背景色
     */
    .FINISH .map_query .mapItem{
        background: rgba(255,255,255,0.07) !important;
    }
    /**
    报表表格头部
     */
    .FINISH .ShowStyle5 table tr th{
         background: rgba(255,255,255,0.07) !important;
    }
    .FINISH .ShowStyle5 table tr th,td{
        border-color:rgba(255,255,255,0.2)!important;
    }
    .FINISH .ShowStyle5 table{
         border-bottom-color:rgba(255,255,255,0.2) !important;
    }
</style>
EOF;
echo $style;
//判断是否是否有单独定制的样式
$extendStyleFile = './main/themes/css/' . str_replace(',', '', $color) . '.css';
if (file_exists($extendStyleFile)) {
    echo " <link rel='stylesheet' href='$extendStyleFile'>";
}
?>