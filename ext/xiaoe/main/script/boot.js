var LocalMethod = {
    // weather: true,
    // scan: true
}
var BootTerminalCallBack = {
    //签到
    signIn: function (item, response) {
        if (response.data) {
            item.answer = response.data.answer;
            if (response.data.address) {
                item.__data = {
                    address: response.data.address
                }
            }
        }
        window.viewScheduleData(item);
    },
    //签到
    outSignIn: function (item, response) {
        if (response.data) {
            item.answer = response.data.answer;
            if (response.data.address) {
                item.__data = {
                    address: response.data.address
                }
            }
        }
        window.viewScheduleData(item);
    },
    //签退
    signOut: function (item, response) {
        if (response.data) {
            item.answer = response.data.answer;
            if (response.data.address) {
                item.__data = {
                    address: response.data.address
                }
            }
        }
        window.viewScheduleData(item);
    },
    //获取考勤记录
    getAttendanceRecord: function (item, response) {
        item.__data = response.data;
        window.ViewAttendancereport(item)
    },
    //排班
    scheduling: function (item, response) {
        item.answer = response.data.answer;
        item.__data = response.data.data;
        window.ViewBlog(item);
    },
    //假期余额
    getVacationDays: function (item, response) {
        item.__data = response.data;
        window.ViewAttendancereport(item)
    },
    //天气
    weather: function (item, response) {
        window.getWeather(item);
    },
    //查询人员
    person: function (item, response) {
        item.__data = response.data;
        window.ViewUserList(item);
    },
    //扫码,功能待实现
    scan: function (item, response) {
        location.href = 'eoffice:scanQRCode:scanQRCode';
    },
    //微博
    queryBlog: function (item, response) {
        item.__data = response.data;
        window.ViewBlog(item);
    },
};
var BootStartCallBack = {
    signIn: function (data, callback) {
        var attendType = 0;
        if (getSystemInfo('attend_mobile_type_location') == 1) {
            attendType = 1;
        } else if (getSystemInfo('attend_mobile_type_wifi') == 1) {
            attendType = 2;
        }
        data.platfrom = window.UserEnv;
        data.attendType = attendType;
        //定位考勤
        if (attendType == 1) {
            window.attendByLocation = function (response) {
                if (typeof response == 'string') {
                    response = JSON.parse(response);
                }
                data.address = response.address;
                data.lat = response.latitude;
                data.long = response.longitude;
                callback(data);
            }
            location.href = "eoffice:location:attendByLocation";
        }
        //wifi考勤
        if (attendType == 2) {
            window.attendByWifi = function (response) {
                if (typeof response == 'string') {
                    response = JSON.parse(response);
                }
                data.attend_wifi_name = response.wifi_name;
                data.attend_wifi_mac = response.wifi_mac
                callback(data);
            }
            location.href = 'eoffice:getWifiInfo:attendByWifi';
        }
    },
    outSignIn: function (data, callback) {
        data.platfrom = window.UserEnv;
        window.attendByLocation = function (response) {
            if (typeof response == 'string') {
                response = JSON.parse(response);
            }
            data.address = response.address;
            data.lat = response.latitude;
            data.long = response.longitude;
            callback(data);
        }
        location.href = "eoffice:location:attendByLocation";
    },
    signOut: function (data, callback) {
        var attendType = 0;
        if (getSystemInfo('attend_mobile_type_location') == 1) {
            attendType = 1;
        } else if (getSystemInfo('attend_mobile_type_wifi') == 1) {
            attendType = 2;
        }
        data.platfrom = window.UserEnv;
        data.attendType = attendType;
        //定位考勤
        if (attendType == 1) {
            window.attendByLocation = function (response) {
                if (typeof response == 'string') {
                    response = JSON.parse(response);
                }
                data.address = response.address;
                data.lat = response.latitude;
                data.long = response.longitude;
                callback(data);
            }
            location.href = "eoffice:location:attendByLocation";
        }
        //wifi考勤
        if (attendType == 2) {
            window.attendByWifi = function (response) {
                if (typeof response == 'string') {
                    response = JSON.parse(response);
                }
                data.attend_wifi_name = response.wifi_name;
                data.attend_wifi_mac = response.wifi_mac
                callback(data);
            }
            location.href = 'eoffice:getWifiInfo:attendByWifi';
        }
    }
};

function getSystemInfo(key) {
    var systemInfo = getUser()['system_info'];
    if (key) {
        return systemInfo[key] || '';
    }
    return systemInfo;
}

function getUser() {
    var userInfo = localStorage.getItem('eoffice_mobile_login_user');
    userInfo = JSON.parse(userInfo);
    return userInfo;
}

function start(data, moduleName, bootAction, callback) {
    var bootActionCamel = toCamel(bootAction);
    if (moduleName === 'eoffice' && BootStartCallBack[bootActionCamel]) {
        BootStartCallBack[bootActionCamel](data, callback);
    } else if (moduleName !== 'eoffice' && window[moduleName]['start'][bootActionCamel]) {
        window[moduleName]['start'][bootActionCamel](data, callback);
    } else {
        callback(data);
    }
}

function terminal(item, response, bootAction, moduleName) {
    var bootActionCamel = toCamel(bootAction);
    if (moduleName === 'eoffice' && BootTerminalCallBack[bootActionCamel]) {
        BootTerminalCallBack[bootActionCamel](item, response);
    } else if (moduleName !== 'eoffice' && window[moduleName]['terminal'][bootActionCamel]) {
        window[moduleName]['terminal'][bootActionCamel](item, response);
    } else {
        //无需单独定义方法怎么展示数据
        if (response.data && response.data.method) {
            var method = response.data.method;
            switch (method) {
                case 'windowViewApproval'://列表
                    windowViewApproval(item, response);
                    break;
                case 'windowOpen'://打开页面
                    openUrl(response.data.url);
                    break;
                case 'windowAnswer':
                    windowAnswer(item, response);
                    break;
                case 'windowViewTable':
                    windowViewTable(item, response);
                    break;
                case 'windowCall':
                    windowCall(item, response);
                    break;
                default:
                    return;
            }
        }
    }
}

function toCamel(str) {
    return str.replace(/([^-])(?:-+([^-]))/g, function ($0, $1, $2) {
        return $1 + $2.toUpperCase();
    });
}

function openUrl(url) {
    window.location.href = './main/php/iframe.php?url=' + encodeURI(url)
}


/**
 * 渲染列表
 * @param data
 */
function windowViewApproval(item, response) {
    var length = response.data.list.length;
    if (length > 0) {
        item.answer = response.data.answer || '已为您查找到' + length + '条相关记录';
        item.__data = response.data;
    } else {
        item.answer = response.data.answer || '没有找到相关记录';
    }
    window.ViewApproval(item);
}

/**
 * 回答对话
 * @param answer
 */
function windowAnswer(item, response) {
    item.answer = response.data.answer;
    window.ViewApproval(item)
}

/**
 * 报表
 * @param item
 * @param response
 */
function windowViewTable(item, response) {
    item.__data = response.data.data;
    window.ViewTable(item);
}

/**
 * 给某人打电话
 * @param item
 * @param response
 */
function windowCall(item, response) {
    window.location.href = 'tel:' + response.data.phone;
}