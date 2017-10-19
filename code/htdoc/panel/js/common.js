// message_cn.js ?
jQuery.extend(jQuery.validator.messages, {
    required: function(a, input){ 
        var label = $(input).closest('.form-group').find('label.control-label').text();
        label = label.replace("：", "");
        label = label.replace(":", "");
        return '请填入'+label;
    },
    email: "电子邮件格式错误",
    url: "请输入合法的网址",
    date: "请输入合法的日期",
    number: "请输入合法的数字",
    digits: "只能输入整数",
    remote:"请修正该字段",
    dateISO: "请输入合法的日期(ISO).",
    creditcard: "请输入合法的信用卡号",
    equalTo: "请再次输入相同的值",
    accept: "请输入拥有合法后缀名的字符串",
    maxlength:jQuery.validator.format("少于{0}个字符串"),
    minlength:jQuery.validator.format("最少{0}个字符串"),
    rangelength:jQuery.validator.format("请输入一个长度介于{0}和{1}之间的字符串"),
    range:jQuery.validator.format("请输入一个介于{0}和{1}之间的值"),
    max: jQuery.validator.format("请输入一个最大为{0}的值"),
    min: jQuery.validator.format("请输入一个最小为{0}的值")
});

/**
 * 只能输入价格格式的数字
 * -.
 * @param  {[type]} obj [description]
 * @param  {[type]} num   支持最大数
 */
function input_price(obj, num) {
    //先把非数字的都替换掉，除了数字和.
    obj.value = obj.value.replace(/[^\d.-]/g, "");
    //必须保证第一个为数字而不是.
    obj.value = obj.value.replace(/^\./g, "");
    //保证只有出现一个.而没有多个.
    obj.value = obj.value.replace(/\.{2,}/g, ".");
    //保证.只出现一次，而不能出现两次以上
    obj.value = obj.value.replace(".", "$#$").replace(/\./g, "").replace("$#$", ".");
    //保证商品单价只能输入两位小数
    obj.value = obj.value.replace(".", "$#$").replace(/\./g, "").replace("$#$", ".").replace(/^(\-)*(\d+)\.(\d\d).*$/, '$1$2.$3');
    if(typeof(num) == 'undefined'){
        num = 999999; // 默认最大数为999999
    }
    if (Number(obj.value) > num) {
        obj.value = num;
    }
}

/**
 * 使文本框 只能输入 +.数
 * @param  {[type]} obj [description]
 * @return {[type]}     [description]
 */
function input_number(obj) {
    var value = $(obj).val();
    // value = value.replace(/[^\d\.]/g, '');
    value = value.replace(/[^\d.]/g, "").replace(/^\./g, "").replace(/\.{2,}/g, ".").replace(".", "$#$").replace(/\./g, "").replace("$#$", ".").replace(/^(\-)*(\d+)\.(\d\d).*$/, '$1$2.$3');
    $(obj).val(value);
}

/**
 * 只能输入正整数
 * @param  {[type]} obj [description]
 */
function input_integer(obj) {
    if(obj.value.length==1){obj.value=obj.value.replace(/[^1-9]/g,'')}else{obj.value=obj.value.replace(/\D/g,'')}
}



/**
 * 对日期进行格式化 'yyyy-MM-dd hh:mm:ss'
 * @param timestamp 要格式化的时间戳
 * @param format 进行格式化的模式字符串
 *     支持的模式字母有：
 *     y:年,
 *     M:年中的月份(1-12),
 *     d:月份中的天(1-31),
 *     h:小时(0-23),
 *     m:分(0-59),
 *     s:秒(0-59),
 *     S:毫秒(0-999),
 *     q:季度(1-4)
 * @return String
 * @author yanis.wang
 * @see http://yaniswang.com/frontend/2013/02/16/dateformat-performance/
 */
template.helper('date_format', function (timestamp, format) {
    var date = new Date(parseInt(timestamp)*1000).toString();


    date = new Date(date);

    var map = {
        "M": date.getMonth() + 1, //月份
        "d": date.getDate(), //日
        "h": date.getHours(), //小时
        "m": date.getMinutes(), //分
        "s": date.getSeconds(), //秒
        "q": Math.floor((date.getMonth() + 3) / 3), //季度
        "S": date.getMilliseconds() //毫秒
    };
    format = format.replace(/([yMdhmsqS])+/g, function(all, t){
        var v = map[t];
        if(v !== undefined){
            if(all.length > 1){
                v = '0' + v;
                v = v.substr(v.length-2);
            }
            return v;
        }
        else if(t === 'y'){
            return (date.getFullYear() + '').substr(4 - all.length);
        }
        return all;
    });
    return format;
});
