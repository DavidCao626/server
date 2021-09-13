
//     //小e初始化需要的信息
function Einit(callbackWxConfig, callbackUserInfo) {
    if (window.UserEnv == 'wechat') {
        window.ET_VOICE.qywechatConfig(data => {
            callbackWxConfig(data)
        })
    } else {
        callbackWxConfig({})
    }

    window.ET_VOICE.getUserInfo(data => {
        callbackUserInfo(data)
    })
}


// eteam播放语音
function EteamsPlayVoice(saywhat) {
    app.playVoice(saywhat)
}

//微信录音开始
function StartRecord() {
    if (window.UserEnv == 'app') {
        console.log('eteamsapp方法', app)
        console.log('eteamsapp方法.startRecord', app.startRecord)
        // app.startRecord('VoiceSuccessData','VoiceFailData')
        app.startRecord({
            success: function () {
                console.log('开始录音')
                window.globalcontinueSpeech()
            },
            fail: function () {
                alert('用户拒绝授权录音');
            }
        })
    } else {
        console.log('j如wx')

        wx.startRecord({
            success: function () {
                console.log('开始录音')
            },
            cancel: function () {
                alert('用户拒绝授权录音');
            }
        });
    }

}

// function VoiceSuccessData(text){
//     console.log('通知录音返回的参数',text)
//     window.VoiceBackUnderstandEt(text)
// }
// function VoiceFailData(text){
//     console.log('失败的参数',text)
//     // VoiceBackUnderstand(text)
// }
// function VoiceRange(argument){
//     if(argument){
//         window.siriWave.setAmplitude(argument * 0.3);
//     }else{
//         window.siriWave.setAmplitude(0.05);
//     }
// }


//微信录音停止
function StopRecord(headBackUnderstand, option) {
    console.log('停止录音')
    if (window.UserEnv == 'wechat') {
        wx.stopRecord({
            success: function (res) {
                var localId = res.localId;
                if (navigator.userAgent.indexOf('wxwork') > 0 && !window.isAndroid) {
                    wx.playVoice({ localId });
                    wx.stopVoice({ localId });
                }
                var param = { 'module': module };
                //第三方企业微信应用需要getAccessToken
                window.ET_VOICE.getAccessToken(data => {
                    wx.uploadVoice({
                        localId, // 需要上传的音频的本地ID，由stopRecord接口获得
                        isShowProgressTips: 0,
                        success: function (re) {
                            _axios.get(api.wxSpeech + `?mediaId=${re.serverId}&appId=${option.appId}&wxAccessToken=${data.accessToken}`)
                                .then(res => res.data)
                                .then(data => {
                                    if (data.isSuccess) {
                                        console.log('语音解析返回：', data)
                                        headBackUnderstand(data.data.result)
                                    } else {
                                        // Toast.info(data.errorMsg, 2)
                                    }

                                })
                        }
                    });
                });
            },
        });
    }
    else {
        app.stopRecord();
    }
}
// //监听录音自动停止
function onVoiceRecordEnd(headBackUnderstand, option) {
    if (window.UserEnv == 'wechat') {
        wx.onVoiceRecordEnd({
            complete: function (res) {
                window.siriWave.stop();
                window.ET_VOICE.getAccessToken(data => {
                    // return data || {};
                    wx.uploadVoice({
                        localId: res.localId, // 需要上传的音频的本地ID，由stopRecord接口获得
                        isShowProgressTips: 0, // 默认为1，显示进度提示
                        success: function (re) {
                            // 
                            _axios.get(api.wxSpeech + `?mediaId=${re.serverId}&appId=${option.appId}&wxAccessToken=${data.accessToken}`)
                                .then(res => res.data)
                                .then(data => {
                                    if (data.isSuccess) {
                                        console.log('语音接口调用成功：', data)
                                        headBackUnderstand(data.data.result)
                                    } else {
                                        Toast.info(data.errorMsg)
                                    }

                                })
                        }
                    });

                });

            }
        });
    }

}
//获取地理位置
function getLocation(callback) {
    if (window.UserEnv == 'wechat') {
        wx.getLocation({
            type: 'gcj02', // 默认为wgs84的gps坐标，如果要返回直接给openLocation用的火星坐标，可传入'gcj02'
            success: function (res) {
                console.log('res', res)
                callback(res)
            }

        });
    } else {
        app.getLocation({
            success: function (argument) {
                console.log('eteams地理位置', argument)
                let data = typeof argument == 'string' ? JSON.parse(argument) : argument
                callback(data)
            },
            fail: function (res) {

            },
        });
    }

}


// 开放型企业聊天
function openEnterpriseChat(data) {
    //  if(window.UserEnv=='wechat'){
    wx.openEnterpriseChat({
        userIds: data.loginid,    //参与会话的企业成员列表，格式为userid1;userid2;...，用分号隔开。
        groupName: '',  // 必填，会话名称。单聊时该参数传入空字符串""即可。
        success: function (res) {
            data.success(res)
            // 回调
        },
        fail: function (res) {
            if (res.errMsg.indexOf('function not exist') > -1) {
                alert('版本过低请升级')
            }
        }
    });
    // }
}

let storageData = "";
let str = ''
function FinishData(item) {
    //防止数据为改变重复调用的问题
    // if (storageData == JSON.stringify(item)) {
    //     return
    // } else {
    //     storageData = JSON.stringify(item)
    // }
    if (!item.invoker) return;
    const { action } = item.invoker
    if (String(action).indexOf('redirect:') == 0) {
        global.openInNewWindow(action.split('redirect:')[1])
    }
    let {
        sendTo, content, callTo, callToNumber, sendToNumber,
        city, type, searchType, workflowId
    } = item.invoker.params
    if (item.invoker) {
        // if (item.invoker.action=='getHelpList') {
        //     window.EtgetHelpList(item)
        // } else {
            //用户自定义的回调方法 执行相应的操作
            let callbackFun = item.invoker.action.split(':')[1]
            console.log('callbackFun', callbackFun)
            window.ET_VOICE[callbackFun](item)       
        //  }


    }

}

