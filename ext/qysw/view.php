<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <style>
        html{
            height: 100%;
        }
        body{
            margin: 0;
            color: #333;
            height: 100%;
            font-size: 14px;
            overflow: hidden;
        }
        .container{
            padding-top: 100px;
            position: relative;
            height: 100%;
        }
        .navbar{
            height: 50px;
            border-bottom: 1px solid #efefef;
            background-color: #fafbfc;
            flex-wrap: nowrap;
            align-items: center;
            padding-left: 15px;
            padding-right: 15px;
            display: flex;
            position: absolute;
            width:100%;
            top:0px;
        }
        .navbar.sub{
            top:51px;
            background:#fff;
            overflow: auto;
        }
        .body{
            padding: 10px 15px;
/*            //height: 100%;*/
            height: calc(100% - 120px);
            overflow: auto;
        }
        .select{
            width: 80px;
            height: 30px;
            border-color: #ccc;
            border-radius: 4px;
            font-size: 14px;
            color: #666;
            padding: 0px 5px;
            margin-right: 10px;
            cursor: pointer;
        }
        .header-btn{
            height: 30px;
            border-color: #ccc;
            border-radius: 4px;
            font-size: 14px;
            color: #666;
            padding: 0px 15px;
            border: 1px solid #ccc;
            background: #fff;
            margin-right: 10px;
            cursor: pointer;
        }
        table {
            min-width: 100%;
            border-collapse: collapse;
            border-spacing: 1;
            border-spacing: 0;
            border: 1px solid #ccc;
            /*margin-bottom: 120px;*/
        }
        table thead tr{
            background: #F3F5F8;
            height: 30px;
        }
        table thead tr td{
            border-left: 1px solid #e9e9e9;
            border-right: 1px solid #e9e9e9;
            text-align: center;
            font-size: 12px;
            color: #777;
            padding: 3px;
            margin-left: -1px;
            box-sizing: border-box;
        }
        table thead tr td.danger{
            color:#f92323;
        }
        table tbody tr td {
            margin-top: -1px;
            color: #333;
            padding: 10px 5px;
            text-align: center;
            font-size: 14px;
            margin-left: -1px;
            border-left: 1px solid #e9e9e9;
            border-right: 1px solid #e9e9e9;
            border-top: 1px solid #ccc;
            cursor: pointer;
            font-size: 12px;
        }
        table tbody tr:hover{
            background:#F3F5F8;
        }
        table tbody tr td.danger{
            color:#f92323;
        }
        .page-group{
            display: flex;
            margin-left: 30px;
        }
    </style>
    <?php
        $year = intval(date('Y'));
        $years = [];
        for($y = $year -3; $y <= $year + 1; $y ++) {
            $years[] = $y;
        }
        $month = intval(date('m'));
    ?>
    <body>
        <div class="container">
            <div class="navbar">
                ??????????????????
            </div>
            <div class="navbar sub">
                <select class="select" id="year">
                    <?php foreach($years as $y) {?>
                    <option value="<?php echo $y?>" <?php if($year === $y){?>selected<?php }?>><?php echo $y?> ???</option>
                    <?php }?>
                </select>
                <select class="select" id="month">
                    <option value="1" <?php if($month === 1){?>selected<?php }?>>1 ???</option>
                    <option value="2" <?php if($month === 2){?>selected<?php }?>>2 ???</option>
                    <option value="3" <?php if($month === 3){?>selected<?php }?>>3 ???</option>
                    <option value="4" <?php if($month === 4){?>selected<?php }?>>4 ???</option>
                    <option value="5" <?php if($month === 5){?>selected<?php }?>>5 ???</option>
                    <option value="6" <?php if($month === 6){?>selected<?php }?>>6 ???</option>
                    <option value="7" <?php if($month === 7){?>selected<?php }?>>7 ???</option>
                    <option value="8" <?php if($month === 8){?>selected<?php }?>>8 ???</option>
                    <option value="9" <?php if($month === 9){?>selected<?php }?>>9 ???</option>
                    <option value="10" <?php if($month === 10){?>selected<?php }?>>10 ???</option>
                    <option value="11" <?php if($month === 11){?>selected<?php }?>>11 ???</option>
                    <option value="12" <?php if($month === 12){?>selected<?php }?>>12 ???</option>
                </select>
                <button class="header-btn">??????</button>
                <button class="header-btn">??????</button>
<!--                <div class="page-group">
                    <button class="header-btn">?????????</button>
                    <button class="header-btn">?????????</button>
                </div>-->
            </div>
            <div class="body">
                <table>
                    <thead>
                        <tr>
                            <td width="40px"><div style="width:40px">??????</div></td>
                            <td width="80px"><div style="width:80px">??????</div></td>
                            <td width="60px"><div style="width:60px">??????</div></td>
                            <td width="60px"><div style="width:60px">??????</div></td>
                            <td width="50px"><div style="width:50px">?????????<br>(???)</div></td>
                            <td width="50px"><div style="width:50px">?????????<br>(???)</div></td>
                            <td width="40px"><div style="width:40px">??????<br>(??????)</div></td>
                            <td width="40px"><div style="width:40px">??????<br>(??????)</div></td>
                            <td width="40px"><div style="width:40px">??????<br>(???)</div></td>
                            <td width="40px"><div style="width:40px">??????<br>(???)</div></td>
                            <td width="60px"><div style="width:60px">????????????<br>(??????)</div></td>
                            <td width="60px"><div style="width:60px">????????????<br>(??????)</div></td>
                            <td width="60px"><div style="width:60px">????????????<br>(??????)</div></td>
                            <td width="60px"><div style="width:60px">????????????<br>(??????)</div></td>
                            <td width="60px"><div style="width:60px">????????????<br>(??????)</div></td>
                            <td width="60px"><div style="width:60px">????????????<br>(??????)</div></td>
                            <?php foreach($vacation as $item){?>
                            <td width="60px"><div style="width:60px"><?php echo $item;?><br>(??????)</div></td>
                            <?php }?>
                            <td width="60px"><div style="width:60px">????????????<br>(???)</div></td>
                            <td width="60px"><div style="width:60px">????????????<br>(???)</div></td>
                            <td width="60px"><div style="width:60px">????????????<br>(???)</div></td>
                        </tr>
                    </thead>
                    <tbody id="stat">
                     
                    </tbody>
                </table>
            </div>
        </div>
    </body>
</html>
<script src="./jquery.js"></script>
<script>
    loadData('','');
    function loadData(year, month)
    {
        $.ajax({
            url: './index.php?action=stat',
            data: {
                year: year,
                month: month
            },
            type: 'GET',
            success: function (response) {
               var data = JSON.parse(response);
               var html = '';
               $.each(data, function(key, item){
                   html += '<tr>';
                   $.each(item, function(k, $_item){
                        html += '<td>' + $_item.value + '</td>';
                   });
                   html += '</tr>';
               });
               $('#stat').html(html);
            },
            error: function (response) {

            }
        });
    }
    function getVacation()
    {
        $.ajax({
            url: './index.php?action=vacation',
            type: 'GET',
            success: function (response) {
               var data = JSON.parse(response);
               var html = '';
               $.each(data, function(key, item){
                   html += '<tr>';
                   $.each(item, function(k, $_item){
                        html += '<td>' + $_item.value + '</td>';
                   });
                   html += '</tr>';
               });
               $('#stat').html(html);
            },
            error: function (response) {

            }
        });
    }
</script>