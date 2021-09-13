var example = {
    //意图执行前的处理
    start: {
        test: function (data, callback) {
            data.action = 'test';
            callback(data);
        }
    },
    //意图完成后的处理
    terminal: {
        test: function (item, response) {
            item.answer = response.data;
            window.viewScheduleData(item);
        }
    },
}