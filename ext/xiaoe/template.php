<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>小e助手</title>
    <link rel="stylesheet" href="./main/css/eoffice.css">
    <?php
    list($color, $colorHex, $theme) = $data['style'];
    if ($theme == 3) {
        $colorHex = '#053e73';
    } else {
        require_once './main/themes/theme.php';
    }
    ?>
    <style>
        body {
            background-color: <?php  echo $colorHex?> !important;
        }
    </style>
    <?php
    if ($data['console']) {
        ?>
        <style>
            #__vconsole {
                display: block !important;
            }
        </style>
        <?php
    }
    ?>
</head>
<body>
<script src="./main/script/jquery.js"></script>
<script src="./main/script/boot.js"></script>
<?php
if ($data['scripts']) {
    foreach ($data['scripts'] as $script) {
        ?>
        <script src="<?php echo $script ?>"></script>
        <?php
    }
}
?>
<script>
    window.UserEnv = '<?=$data['platform']?>';
    window.isEoffice = true;
    // 定义ET_VOICE对象
    window.ET_VOICE = {
        qywechatConfig: function (callback) {
            var qywechatConfig = {
                appId: '',
                timeStamp: '',
                nonceStr: '',
                signature: '',
                cardSign: ''
            };
            callback(qywechatConfig);
        },
        getUserInfo: function (callback) {
            var userInfo = {
                appId: '<?=$data['app_id']?>',
                timestamp: '<?=$data['timestamp']?>',
                sign: '<?=$data['sign']?>',
                userId: '<?=$data['user_id']?>'
            };
            callback(userInfo);
        },
        getAccessToken: function (callback) {
            callback({accessToken: ''});
        },
        boot: function (data) {
            var action = data.invoker.action;
            var params = data.invoker.params;
            var bootActions = action.split(':');
            var bootAction = bootActions[2]
            var moduleName = bootActions[0]
            //记录下意图相关的信息
            var extra = {
                'intention_key': data.intentInfo.name,
                'intention_name': data.intentInfo.title,
                'appId': data.appId,
                'content': data.originText,
                'platform': window.UserEnv
            }
            start(params, moduleName, bootAction, function (handleData) {
                if (LocalMethod[bootAction]) {
                    terminal(data, {}, bootAction, 'eoffice');
                } else {
                    var prefix = '../../public/api/xiao-e';
                    var modulePrefix = moduleName === 'eoffice' ? '' : '/' + moduleName;
                    handleData.extra = extra;
                    $.ajax({
                        url: prefix + modulePrefix + '/boot/' + bootAction + '?api_token=<?=$data['api_token']?>',
                        data: handleData,
                        type: 'POST',
                        success: function (response) {
                            if (response.status == 0) {
                                data.answer = response.errors[0].message;
                                window.viewScheduleData(data);
                            } else {
                                terminal(data, response, bootAction, moduleName);
                            }
                        },
                        error: function (response) {
                            if (response.status == 401) {
                                window.eofficeBackHome();
                            }
                        }
                    });
                }

            });
        }
    };
    window.eofficeBack = function () {
        history.back();
    };
    window.eofficeBackHome = function () {
        <?php
        $url = $data['fromUrl'];
        if ($url) {
            echo "location.href='$url'";
        } else {
            echo "history.back()";
        }
        ?>
    };
    //定义app对象
    if (window.UserEnv === 'app') {
        if (true) {
            var scv = false;
            window.supportChangeVoice = function () {
                scv = true;
            };
            if (!window.getThemeColor) {
                window.getThemeColor = function () {
                    return '<?php echo $colorHex ?? '#ffffff' ?>'
                };
            }
            if (!window.showhtml) {
                window.showhtml = {};
            }
            if (!window.showhtml.supportChangeVoice) {
                window.showhtml.supportChangeVoice = function () {
                    return true;
                };
            }
            if (!window.showAlert) {
                window.showAlert = function () {

                };
            }
        }
        window.app = {
            startRecord: function (request) {
                if (true) {
                    request.success();
                } else {
                    request.fail();
                }
            },
            stopRecord: function () {
                location.href = "emobile:stopVoice";
            },
            continueSpeech: function (request) {
                window.backUnderstand = function (response) {
                    var responseData = JSON.parse(response);

                    request.continueSpeechend({result: responseData.text});
                };
                window.backErr = function (error) {
                    // console.log(error);
                };
                window.changeVoice = function (voice) {
                    request.changeVoice(voice);
                };
                location = "emobile:speechUnderstand:backUnderstand:backErr:1500";
            },
            getLocation: function (request) {
                window.app_location_success = function (response) {
                    try {
                        request.success(response);
                    } catch (e) {
                        request.fail(e.message);
                    }
                    delete window.app_location_success;
                };
                location.href = "eoffice:location:app_location_success";
            },
            playVoice: function (sayWhat) {
                location.href = "emobile:palyVoice:" + encodeURI(sayWhat);
            },
            //聊天
            openEnterpriseChat: function (data) {
                openUrl(data.userId);
            }
        };
    }
</script>
<?php
require_once $sdk;
?>
</body>
</html>