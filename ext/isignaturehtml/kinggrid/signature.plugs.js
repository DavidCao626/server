(function (root , kinggrid , Signature , $) {
	'use strict';
	var isIE8 = false;
	if(root['JSON']){
		isIE8 = '{"x":"中"}' !== root['JSON'].stringify({x:'中'});
	}
	var Utils = kinggrid.Utils;
	var options = Signature.options;
	Utils.extend(Signature.options.template,{
		modifyPwdBtl:'<div id="kg-dialog-modify" class="kg-dialog kg-dialog-password ">'+
		'<div class="kg-form">'+
		 '<div class="form-item clearfix">'+
		    '<label class="kg-sm kg-sm-3 kg-label"><%this.keysn?"密钥序列号":"用户编号"%>：</label>'+
		    '<div class="kg-sm kg-sm-7">'+
		     '<p class="kg-form-static"> <%this.keysn||this.usercode%></p>'+
		    '</div>'+
		    '</div>'+
		    '<div class="form-item clearfix" style="padding-bottom:7px" >'+
		    '<label class="kg-sm kg-sm-3 kg-label">密码：</label>'+
		    '<div class="kg-sm kg-sm-7">'+
		     ' <input type="password" name="oldPwd" id="oldPwd" class="form-control">'+
		    '</div>'+
		    '</div>'+
		    '<div class="form-item clearfix" style="padding-bottom:7px" >'+
		    '<label class="kg-sm kg-sm-3 kg-label">新密码：</label>'+
		    '<div class="kg-sm kg-sm-7">'+
		     ' <input type="password" name="newPwd" id="newPwd" class="form-control">'+
		    '</div>'+
		    '</div>'+
		    '<div class="form-item clearfix" style="padding-bottom:7px" >'+
		    '<label class="kg-sm kg-sm-3 kg-label">重新输入：</label>'+
		    '<div class="kg-sm kg-sm-7">'+
		     ' <input type="password" name="rNewPwd" id="rNewPwd" class="form-control">'+
		    '</div>'+
		' </div>'+
		'</div>'+
		'</div>'
		
	});
	
	
	
	var plus = kingPlus();
	var template = template;
	
	
	Utils.extend(Signature.prototype ,  {
		modifyPwdUrl:'/key/modifyPwd',
		modifyPwd:function(params){
			var that = this;
			var config = {
					titile:'修改密码',
					target:{keysn:that.keysn,usercode:that.usercode},
					onOk:function(){
						var d = this;
						var oldPwd = d.find('#oldPwd').val();
						var newPwd = d.find('#newPwd').val()||'';
						var rNewPwd = d.find('#rNewPwd').val()||'';
						if(newPwd.length<6){
							plus.alert("新密码不能少于6位!");
							return false ;
						}
						if(newPwd!=rNewPwd){
							plus.alert("两次输入的新密码不一致!");
							return false;
						}
						
						var psparam = {
								keysn:that.keysn,
								oldPwd:oldPwd,
								newPwd:newPwd,
								usercode:that.usercode
						}
						
						var successCall = params.successCall , errorCall = params.errorCall;
						var assist = kinggrid.surry(that.serverUrl);
						assist.request(that.modifyPwdUrl ,psparam)
						.ret(function(data){
							if(data.result==true){
								d.close();
								plus.alert("密码修改成功!");
							}else{
								plus.alert(data.errmsg);
							}
							
							successCall.call(that ,  data);
						}).fail(function(cont , err){
							plus.alert(err);
							errorCall.call(that ,  err);
						});
						
						
						return false ;
					},
					onCancel:function(){return ;}
			}
			return that.showDialog('modifyPwdBtl',config);
		}
	});
	
	
		
})(this , kinggrid ,Signature, jQuery);