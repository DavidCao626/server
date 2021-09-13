(function (root , kinggrid , Signature , $) {
	'use strict';
	var isIE8 = false;
	if(root['JSON']){
		isIE8 = '{"x":"中"}' !== root['JSON'].stringify({x:'中'});
	}
	var Utils = kinggrid.Utils;
	var options = Signature.options;
	Utils.extend(Signature.options.template,{
		signInfoBtl:'<div class="kg-dialog kg-dialog-info kg-dialog-signedinfo" id="kg-signedinfo">'+
		'<%var modified = this.signModified;%>'+
		'<div class="kg-title success <%modified?"kg-hide":""%>">'+
			'<i class="kg-icon"></i><span>检测结果：签名数据验证正常！</span>'+
		'</div>'+
		'<div class="kg-title danger  <%modified?"":"kg-hide"%> ">'+
			'<i class="kg-icon"></i><span>检测结果：签名数据被篡改！</span>'+
		'</div>'+
		'<div class="kg-content">'+
			'<div class="kg-tab">'+
				'<ul class="kg-nav clearfix">'+
					'<li class="active"><a href="#" kg-target="certinfo">证书信息</a></li>'+
				'</ul>'+
				'<%var certinfo = this.signatureData.signMeta.certinfo; %>'+
				'<div class="kg-tab-content">'+
					'<div class="kg-tab-pane active certinfo">'+
						'<div class="kg-meta">'+
							'<div class="clearfix kg-item ">'+
								'<label class="kg-label">版本：</label>'+
								'<span class="kg-value">V<%certinfo.version%></span>'+
							'</div>'+
							'<div class="clearfix kg-item ">'+
								'<label class="kg-label">序号：</label>'+
								'<span class="kg-value"><%certinfo.serialNumber%></span>'+
							'</div>'+
							'<div class="clearfix kg-item ">'+
								'<label class="kg-label">签名算法：</label>'+
								'<span class="kg-value"><%certinfo.algName%></span>'+
							'</div>'+
							'<div class="clearfix kg-item ">'+
								'<label class="kg-label">颁发者：</label>'+
								'<span class="kg-value"><%certinfo.issuerDN%></span>'+
							'</div>'+
							'<div class="clearfix kg-item ">'+
								'<label class="kg-label">使用者：</label>'+
								'<span class="kg-value"><%certinfo.subjectDN%></span>'+
							'</div>'+
							'<div class="clearfix kg-item ">'+
								'<label class="kg-label">有效期从：</label>'+
								'<span class="kg-value"><%kinggrid.Utils.formatDate(new Date(certinfo.notBefore) , "yyyy-MM-dd hh:mm:ss")%></span>'+
							'</div>'+
							'<div class="clearfix kg-item ">'+
								'<label class="kg-label">至：</label>'+
								'<span class="kg-value"><%kinggrid.Utils.formatDate(new Date(certinfo.notAfter) , "yyyy-MM-dd hh:mm:ss")%></span>'+
							'</div>'+
						'</div>'+
					'</div>'+
				'</div>'+
			'</div>'+
		'</div>'+
	'</div>',
	
	signatureInfoBtl:'<div class="kg-dialog kg-dialog-info" id="kg-sigantureinfo">'+
		'<%var modified = this.modified;%>'+
		'<div class="kg-title success <%modified?"kg-hide":""%>">'+
			'<i class="kg-icon"></i><span>检测结果：保护数据正常！</span>'+
		'</div>'+
		'<div class="kg-title danger  <%modified?"":"kg-hide"%> ">'+
			'<i class="kg-icon"></i><span>检测结果：保护数据被篡改！</span>'+
		'</div>'+
		'<div class="kg-content">'+
			'<div class="kg-tab">'+
				'<ul class="kg-nav clearfix">'+
					'<li class=" <% modified?"":"active"%> "><a href="#" kg-target="signatureinfo">签章信息</a></li>'+
					'<%if(modified){%>'+
					'<li class=" <% modified?"active":""%> " ><a href="#" kg-target="modifieditems">篡改信息</a></li>'+
					'<%}%>'+
				'</ul>'+
				'<div class="kg-tab-content">'+
					'<div class="kg-tab-pane <% modified?"":"active"%> signatureinfo">'+
						'<% var SDATA = this.signatureData; %>'+
						'<% var modifiedItems = this.modifiedItems; %>'+
						'<div class="kg-meta">'+
							'<div class="clearfix kg-item ">'+
								'<label class="kg-label">应用程序：</label>'+
								'<span class="kg-value"><%SDATA.appname%></span>'+
							'</div>'+
							'<div class="clearfix kg-item ">'+
								'<label class="kg-label">授权单位：</label>'+
								'<span class="kg-value"><%SDATA.orgname%></span>'+
							'</div>'+
							'<div class="clearfix kg-item ">'+
								'<label class="kg-label">用户名称：</label>'+
								'<span class="kg-value"><%SDATA.username%></span>'+
							'</div>'+
							'<div class="clearfix kg-item ">'+
								'<label class="kg-label">密钥序列：</label>'+
								'<span class="kg-value"><%SDATA.keysn%></span>'+
							'</div>'+
							
							'<div class="clearfix kg-item ">'+
								'<label class="kg-label">签章名称：</label>'+
								'<span class="kg-value"><%SDATA.seal.signname%></span>'+
							'</div>'+
							'<div class="clearfix kg-item ">'+
								'<label class="kg-label">签章序列：</label>'+
								'<span class="kg-value"><%SDATA.seal.signsn%></span>'+
							'</div>'+
							'<div class="clearfix kg-item ">'+
								'<label class="kg-label">签章日期：</label>'+
								'<span class="kg-value"><%SDATA.timestamp.signtime%></span>'+
							'</div>'+
						'</div>'+
					'</div>'+
					
					'<%if(modified){%>'+
					'<%var modifiedItems = this.modifiedItems;%>'+
					'<% for(var i=0;i<modifiedItems.length;i++) {%>' + 
					'<% var item = modifiedItems[i]; %>' + 
					'<div class="kg-tab-pane modifieditems <% modified?"active":""%> ">'+
						'<div class="kg-meta">'+
							'<div class="clearfix kg-item ">'+
								'<label class="kg-label" kg-field="<%item.field%>"><%item.desc%>由：</label>'+
								'<span class="kg-value"><%this["_renderValue"](item.orivalue) %></span>'+
							'</div>'+
							'<div class="clearfix kg-item ">'+
								'<label class="kg-label">更改为：</label>'+
								'<span class="kg-value"><%this["_renderValue"](item.newvalue)%></span>'+
							'</div>'+
						'</div>'+
					'</div>'+
					'<%}%>'+
					'<%}%>'+
				'</div>'+
			'</div>'+
		'</div>'+
	'</div>',
		
		handwritedlg: '<div class="kg-dialog kg-dialog-info" id="kg-handwrite" unselectable="on" onselectstart="return false;" style="-moz-user-select:none;">'+
		        '名称:<input type="text" value="手写签名" id="nameid" />'+
		        '&nbsp;&nbsp;&nbsp;' +
		        '颜色:<select id="hw_color">'+
		        '<option value="black">黑</option>'+
		        '<option value="red">红</option>'+
		        '<option value="blue">蓝</option>'+
		        '<option value="green">绿</option>'+
		        '</select>'+
		        '&nbsp;&nbsp;&nbsp;' +
		        '笔宽:<select id="hw_width">'+
		        '<option value="1">1</option>'+
		        '<option value="2" selected>2</option>'+
		        '<option value="3">3</option>'+
		        '<option value="4">4</option>'+
		        '<option value="5">5</option>'+
		        '<option value="6">6</option>'+
		        '</select>'+
		        '<canvas id="canvasId" width=670px height=500px>' +
				'</canvas><br />'+
				'<div id="div-judge" style="display: block" align="center">' +
				'<button type="button" id="clearid" class="kg-button">清除</button>' +
				'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' +
				'<button type="button" id="okid" class="kg-button">签名</button>' +
				'</div>' +
		'</div>',
		
		barcodedlg: '<div class="kg-dialog kg-dialog-info" id="kg-barcode">'+
		        '<label class="kg-label">二维码内容：</label>'+
		        '<textarea id="bcId"></textarea><br />' +
		        '<div id="output"><br /></div>' +
		        '<div id="div-judge" style="display: block">' +
				'<button type="button" id="bc_clearid" class="kg-button">清除</button>' +
				'<button type="button" id="bc_okid" class="kg-button">确定</button>' +
 				'</div>' + 
 		'</div>',

		scanBCdlg : '<div class="kg-dialog kg-dialog-info" id="kg-barcode">'
			        + '<div id="support"></div> ' 
			        + '<div id="contentHolder">'         
			        + '<video id="video" width="320" height="320" autoplay></video>'  
			        + '<canvas style="display:none; background-color:#F00;" id="canvas_scanBC" width="320" height="320">'         
			        + '</canvas> <br/> '   
			        + '</div>' +  
		'</div>'
	});
	
	
	var plus = kingPlus();
	var template = template;
	var aisleKing;
	
	var _borken;
	var _borkenReq;
	
	Signature.addLiseter('init' , function(){
		aisleKing = Signature.aisleKing;
		var icons = Signature.options.icons;
		for(var i = 0; i < icons.length; ++i){
			var iconsMeta = icons[i];
			switch(iconsMeta.iconClass)
			{
			case "kg-icon-move":
				if(Signature.options.icon_move != null 
						&& Signature.options.icon_move == false)
					{
					    //Signature.options.icons.splice(i, 1);
					    iconsMeta.enable = false;
					}
				break;
			case "kg-icon-remove":
				if(Signature.options.icon_remove != null 
						&& Signature.options.icon_remove == false)
					{
					   // Signature.options.icons.splice(i, 1);
					   iconsMeta.enable = false;
					}
				break;
			case "kg-icon-sign":
				if(Signature.options.icon_sign != null 
						&& Signature.options.icon_sign == false)
				   {
				        //Signature.options.icons.splice(i, 1);
					    iconsMeta.enable = false;
				   }
				break;
			case "kg-icon-signverify":
				if(Signature.options.icon_signverify != null 
						&& Signature.options.icon_signverify == false)
				   {
				        //Signature.options.icons.splice(i, 1);
					    iconsMeta.enable = false;
				   }
				break;
			case "kg-icon-sealinfo":
				if(Signature.options.icon_sealinfo != null 
						&& Signature.options.icon_sealinfo == false)
				  {
				       //Signature.options.icons.splice(i, 1);
					   iconsMeta.enable = false;
				  }
				break;
			}
		}
		
	});
	
	Signature.addLiseter('beforeVerify' , function(){
		_borken = [];
		_borkenReq = {signsn:[] , sealImg:[]};
	});
	
	Signature.addLiseter('eachVerify' , function(signature){
		if(signature.broken){
			_borken.push(signature);
			_borkenReq.signsn.push(signature.signatureData.seal.signsn);
			_borkenReq.sealImg.push(signature.signatureData.seal.imgdata);
		}
	});
	
	Signature.addLiseter('verify' , function(){
		if(_borken.length>0){
			 aisleKing.request(options.imageUrl, {signsn:_borkenReq.signsn,sealImg:_borkenReq.sealImg}).ret(function(data){
				if(data.result){
					var imageFUrl = data.imageUrl || options.imageUrl+'_';
					for (var i = 0; i < _borken.length; i++) {
						var s = _borken[i];
						var imgEles = s.imgEles;
						for ( var key in imgEles) {
							var eles = imgEles[key];
							eles.find('.kg-img').attr({
								src:Signature.aisleKing.serverUrl+imageFUrl+'?a0=0&signsn='+(s.signatureData.seal.signsn)
							});
						}
						delete s['broken'];
					}
				}else{
					Signature.alert(kinggrid.msg(data.errcode , 'then'));
				}
			}).fail(function(cont , err){
				Signature.alert(kinggrid.msg(err.errcode , 'then'));
				cont(null);
			});
		}
	});

	var resizeSeal = {};
	var resizeTime;
	$(window).resize(function(){
		resizeTime&&clearTimeout(resizeTime);
		resizeTime = setTimeout(function(){
			for(var key in  resizeSeal){
				var $sealDiv = $(resizeSeal[key]);
				var $elem = $(Utils.$($sealDiv.attr('elemid')));
				Signature.prototype.calSealPos.call(null , $elem, $sealDiv);
			}
		}, 200);
	});
	
	
	Utils.extend(Signature.prototype ,  {
		_init: function(){
			var that = this;
			that.on('showAt', function (imgDiv) {
				var elem = Utils.$(imgDiv.getAttribute('elemid'));
				if(!elem.getAttribute('display')){
					 resizeSeal[imgDiv.id] = imgDiv;
				}
		    });
			
			that.on('removeAt', function (imgDiv) {
				delete resizeSeal[imgDiv.id];
		    });
			
			
			that.on('beforeShowAt', function(imgDiv){
				if(this.signatureData.seal.imgdata.length>3960&& isIE8){
					this.brokenImgDiv = imgDiv ;
				}
				
			});
			that.on('showAt', function(){
				if(that.brokenImgDiv){
					var seal = that.signatureData.seal;
					aisleKing && aisleKing.request(that.imageUrl, {signsn:seal.signsn,sealImg:seal.imgdata}).ret(function(data){
						if(data.result){
							var imageUrl = data.imageUrl || that.imageUrl+'_';
							$(that.brokenImgDiv).find('.kg-img').attr({
								src:Signature.aisleKing.serverUrl+imageUrl+'?a0=0&signsn='+seal.signsn
							});
							delete that['brokenImgDiv'];
						}
					});
				}
			});
			
			//添加点击显示印章功能
			that.on('showAt', function(imgDiv){
				var $imgDiv = $(imgDiv);
				$imgDiv.on('click' , function(){
					that._verify();
				    if(Signature.options.verifySignatureInfo !== undefined && Signature.options.verifySignatureInfo){
						if((Signature.options.verifySignatureInfo(that.modified,that.modifiedItems,that.signatureData)))
							return;
					}
					that.signatureInfo();
				});
				$imgDiv.append('<div class="kgImgIcons kg-img-icons" ></div>');
			});
			
			//添加显示按钮组			
			that.on('handleImg', function(imgDiv){
				var $imgDiv = $(imgDiv);
				$imgDiv.find('.kg-img-icon').remove();
				if(options.icons){
					var iconsDiv = $imgDiv.children('.kg-img-icons');
					var u = options.icons.length-1;
					for (var i = u; i >=0; i--) {
						var icon = options.icons[i];
						var r = Utils.val(icon.enable , that ,imgDiv );
						if(r){
							var iconDom = $('<a href="#" title="'+icon.title+'" class="kg-img-icon" id="kg-img-icon-'+icon.id+'-'+this.signatureid+'"><i class="'+icon.iconClass+'"></i></a>');
							iconsDiv.append(iconDom);
							
							if(icon.exec){
								icon.exec.call(that , imgDiv);
							}
							
							var click = icon.click || function(event){
								event.preventDefault();
								event.stopPropagation();
							};
							
							var fn = function(imgDiv , dom , c){
								iconDom.click(function(e){
									e.imgDiv =imgDiv;
									c.call(that , e , imgDiv);
								});
							}
							fn(imgDiv , iconDom , click);
						}
					}
					
				}
			});
		},
		
		
		signatureInfo: function(){
			var that = this;
			var config = {
					target:that,
					onCancel:false,
					onShow: function(){
						var kgTab = this.find('.kg-tab');
						this.tab = Utils.tab(kgTab);
					}
				}
			
			return that.showDialog('signatureInfoBtl' , config );
		},
		
		signInfo: function(){
			var that = this;
			var config = {
				title:'签名验证',
				target:that,
				onCancel:false
			}
			return that.showDialog('signInfoBtl' , config );
		},
		
		handWriteDlg: function(options, callback){
			var that = this;
			var config = {
				title:'手写签名',
				target:that,
				onCancel:false
			}
		   if(options.width && options.height){
			  var html = Signature.options.template["handwritedlg"];
			  var tmp = html.substring(0, html.indexOf("width=")+6);
			  var tmp1 =  html.substring(html.indexOf("px "), html.length);
			  tmp += options.width;
			  tmp += tmp1;
			  html = tmp.substring(0, tmp.indexOf("height=")+7);
			  tmp1 =  tmp.substring(tmp.indexOf("px>"), tmp.length);
			  html += options.height;
			  html += tmp1;
			  Signature.options.template["handwritedlg"] = html;
		   }
	       
	       var hwdlg = that.showDialog('handwritedlg' , config );
			/*var canvas = document.getElementById("canvasId");
			var content = canvas.getContext("2d");
			if (window.screen.orientation.angle == 90 || window.screen.orientation.angle == -90) {
				//ipad、iphone竖屏；Andriod横屏
				 var imgData = content.getImageData(0, 0, canvas.width, canvas.height);
			     canvas.width = parseInt(window.screen.availWidth)*0.5;
			     canvas.height = parseInt(window.screen.availHeight)*0.5;
			     content.putImageData(imgData,0,0);
				
			}else if(window.screen.orientation.angle == 180 || window.screen.orientation.angle == 0){
				//ipad、iphone横屏；Andriod竖屏
			     var imgData = content.getImageData(0, 0, canvas.width, canvas.height);
			     canvas.width = parseInt(window.screen.availWidth)*0.5;
			     canvas.height = parseInt(window.screen.availHeight)*0.5;
			     content.putImageData(imgData,0,0);
			}*/
			options.penColor = document.getElementById("hw_color").value;
			options.minWidth = 0.5;
			options.maxWidth = 4.5;
			var signaturePad = new SignaturePad(canvas, options);
			canvas.onmouseleave = function(event){
				signaturePad.simulate_mouseevent(canvas, "mouseup");
				signaturePad.simulate_mouseevent(document.getElementById("kg-handwrite"), "mouseup");
			};
			$("#hw_color").click(function(){
				var sle = document.getElementById("hw_color").value;
				signaturePad.penColor = sle;
			});
			
			$("#hw_width").click(function(){
				var sle = document.getElementById("hw_width").value;
				switch(sle)
				{
				case "1":
					{
					   signaturePad.minWidth = 0.5;
					   signaturePad.maxWidth = 2.5;
					}
					break;
				case "2":
				    {
					   signaturePad.minWidth = 0.5;
					   signaturePad.maxWidth = 4.5;
					}
					break;
				case "3":
				    {
					   signaturePad.minWidth = 1;
					   signaturePad.maxWidth = 6;
					}
					break;
				case "4":
				    {
					   signaturePad.minWidth = 1;
					   signaturePad.maxWidth = 8;
					}
					break;
				case "5":
				    {
					   signaturePad.minWidth = 2.5;
					   signaturePad.maxWidth = 10;
					}
					break;
				case "6":
				    {
					   signaturePad.minWidth = 3;
					   signaturePad.maxWidth = 12;
					}
					break;
				default:
				    {
					   signaturePad.minWidth = 0.5;
					   signaturePad.maxWidth = 2.5;
					}
					break;
				}
			});
			
			
			var clearBtn = document.getElementById("clearid");
			var okBtn = document.getElementById("okid");
			if(clearBtn){
				clearBtn.onclick = function(){
					signaturePad.clear();
				}
			}
			if(okBtn){
				okBtn.onclick = function(){
					hwdlg.close();
					var data_uri = signaturePad.toDataURL();
					var base64Data = data_uri.replace(/^data:image\/\w+;base64,/, "");
					if(base64Data){
						var param = {
								height: options.image_height || "5.00",
								width:  options.image_width || "6.70",
								imageData: base64Data,
								name: document.getElementById("nameid").value
						};
						callback(param);
					}
					   
				}
			}
		},
		fingerPrintsDevice:function(options,callback){
			var that = this;
			var successCall = options.successCall , errorCall = options.errorCall;
			var severKing = kinggrid.surry(Signature.options.clientUrl , 'IWEBASSIST.iWebAssistCtrl.1' , '4240FB41-A213-42B6-8CB5E6705C99B319');
			severKing.invoke('KGRunFingerPrints', options.device_type || '0', options.image_type || 'gif','JSComServer','','').ret(function(data){
				if(data.result){
					var imagedata = data.FingerPrintsMap;
					var imagewidth = parseFloat(data.ImageWidth* 254.0/ 9600.0).toFixed(2);
					var imageheight = parseFloat(data.ImageHeight* 254.0/ 9600.0).toFixed(2);
					var param = {
						width: options.image_height || imagewidth,
						height: options.image_width || imageheight,
						imageData: imagedata,
						name: "指纹签名"
					};
					callback(param);
				}else{
					that.error(data.errcode,'KGRunFingerPrints');
				}
			}).fail(function(cont , err){
				errorCall.call(that ,  err);
			});
		},
		handWriteDevice:function(options, callback){
			var that = this;
			if(options.copy_right != undefined  && options.copy_right != null){
				var severKing = kinggrid.surry(Signature.options.clientUrl , 'IWEBASSIST.iWebAssistCtrl.1' , '4240FB41-A213-42B6-8CB5E6705C99B319');
				var successCall = options.successCall , errorCall = options.errorCall;
				severKing.invoke('KGRunHandWritten', options.device_type || '0', options.copy_right,'JSComServer','','').ret(function(data){
					if(data.result){
						var imagedata = data.HandWrittenValue;
						var imagewidth = parseFloat(data.ImageWidth* 254.0/ 9600.0).toFixed(2);
						var imageheight = parseFloat(data.ImageHeight* 254.0/ 9600.0).toFixed(2);
						var param = {
							width: options.image_height || imagewidth,
							height: options.image_width || imageheight,
							imageData: imagedata,
							name: "手写签名"
						};
						callback(param);
					}else{
						that.error(data.errcode,'KGRunHandWritten');
					}
				}).fail(function(cont, err){
					errorCall.call(that, err);
				});
			}
		},
		
		barCodeDlg: function(options, callback){
			var that = this;
			if(options.content != undefined && options.content != null){
				var bc = $('#output').qrcode(options.content);
				var param = {
						height: options.image_height || "5",
						width:  options.image_width || "5",
						imageData: bc,
						name: "二维码"//document.getElementById("nameid").value
				};
				callback(param);
			}
		},
		
		scanBCDlg: function(options, callback){
			var that = this;
			var config = {
				title:'扫码签章',
				target:that,
				onCancel:false
			}
	     
	       var sbcdlg = that.showDialog('scanBCdlg' , config );
	     //  options.win.addEventListener("DOMContentLoaded", function (){  
			try{  
				var canvas = document.getElementById("canvas_scanBC");  
			    var context = canvas.getContext("2d");  
			    var video = document.getElementById("video");           
			    var videoObj = { "video": true,audio:false},  
				flag=true,  
			    MediaErr = function (error){             
				   flag=false;    
				   if (error.PERMISSION_DENIED){  
					   alert('用户拒绝了浏览器请求媒体的权限', '提示');  
				   } else if (error.NOT_SUPPORTED_ERROR) {  
					   alert('对不起，您的浏览器不支持拍照功能，请使用其他浏览器', '提示');  
				   } else if (error.MANDATORY_UNSATISFIED_ERROR) {  
					   alert('指定的媒体类型未接收到媒体流', '提示');  
				   } else {  
					   alert('系统未能获取到摄像头，请确保摄像头已正确安装。或尝试刷新页面，重试', '提示');  
				   }  
				};  
				//获取媒体的兼容代码，目前只支持（Firefox,Chrome,Opera）  
				if (navigator.getUserMedia) {  
					//qq浏览器不支持  
					if (navigator.userAgent.indexOf('MQQBrowser') > -1) {  
					   alert('对不起，您的浏览器不支持拍照功能，请使用其他浏览器', '提示');  
					   return false;  
					}  
				   navigator.getUserMedia(videoObj, function (stream) {  
					   video.src = stream;                  
					   video.play();        
					}, MediaErr);             
				} else if(navigator.webkitGetUserMedia){  
				   navigator.webkitGetUserMedia(videoObj, function (stream){            
					   video.src = window.webkitURL.createObjectURL(stream);             
					   video.play();             
				   }, MediaErr);             
				}else if (navigator.mozGetUserMedia){  
					   navigator.mozGetUserMedia(videoObj, function (stream) {  
					     video.src = window.URL.createObjectURL(stream);  
					     video.play();  
					   }, MediaErr);  
				}else if (navigator.msGetUserMedia){   
					   navigator.msGetUserMedia(videoObj, function (stream) {  
					      $(document).scrollTop($(window).height());  
					      video.src = window.URL.createObjectURL(stream);  
					      video.play();  
					   }, MediaErr);  
	            }else{  
					   alert('对不起，您的浏览器不支持拍照功能，请使用其他浏览器');  
					   return false;  
				}  
				if(flag){  
					   alert('为了获得更准确的测试结果，请尽量将二维码置于框中，然后进行拍摄、扫描。 请确保浏览器有权限使用摄像功能');  
				}  
				//这个是拍照按钮的事件，            
				var sto = setTimeout(function(){//防止调用过快  
				       if(context)  {  
				            context.drawImage(video, 0, 0, 320, 320); 
				            $('#contentHolder').html5_qrcode(function(data){
				                $('#read').html(data);
				                   sbcdlg.close();
				                   alert(data);
				                   options.content = data;
				                   barCodeDlg(options, callback);
				                   window.clearInterval(sto);
				              },
				              function(error){
				                $('#read_error').html(error);
				                sbcdlg.close();
				                window.clearInterval(sto);
				              }, function(videoError){
				                $('#vid_error').html(videoError);
				                sbcdlg.close();
				                window.clearInterval(sto);
				              }
				            );
				        }  
				},200);
				
			}catch(e){        
					printHtml("浏览器不支持HTML5 CANVAS");         
				}   
			//}, false); 
		}
	});
	
	Signature.addIcon({
		iconClass:'kg-icon-move',
		title:'移动签章',
		id:"moveSignature",
		enable: function(imgDiv){
			return this.canMove(imgDiv);
		},
		exec: function(imgDiv){
			var that = this;
	        var bM = true;
			$(imgDiv).on('touchstart mousedown', '.kg-icon-move', function (event) {
				if(bM) that.runMove(event , imgDiv);
		    });
			$(imgDiv).on('touchstart mouseover', '.kg-icon-move', function (event) {
				if(Signature.options.extra !== undefined){
					var id = imgDiv.attributes.signatureid.nodeValue;
					if(id && Signature.options.extra[id] !== undefined) bM = Signature.options.extra[id].icon_move();
				}
		    });
			
			/*Utils.addEvent(imgDiv , 'mouseover touchstart' , function(event){
				if(Signature.options.extra !== undefined){
					var id = imgDiv.attributes.signatureid.nodeValue;
					if(Signature.options.extra[id].icon_move()){
						Utils.$("kg-icon-move").style.visibility="hidden";
					}else{
						Utils.$("kg-icon-move").style.visibility="visible";
					}
				}
			});*/
		}
	});
	
	
	Signature.addIcon({
		iconClass:'kg-icon-remove',
		title:'撤销签章',
		id:"revokeSignature",
		enable: function(){
			return this.canDelete();
		},
		click: function(event){
			event.preventDefault();
			event.stopPropagation();
			this.revokeSignature();
		}
	});
	Signature.addIcon({
		iconClass:'kg-icon-sign',
		title:'数字签名',
		id:"sign",
		enable: function(){
			return this.canSign();
		},
		click: function(event){
			event.preventDefault();
			event.stopPropagation();
			if(this._verify()){
				this.signSignature();
			}else{
				Signature.alert('签章无效，不能执行数字签名！');
			}
		}
	});
	
	
	Signature.addIcon({
		iconClass:'kg-icon-signverify',
		title:'签名验证',
		id:"verifysign",
		enable: function(){
			return this.signatureData.signMeta;
		},
		click: function(event){
			event.preventDefault();
			event.stopPropagation();
			var that = this;
			that.verifySignData(function(response){
				if(response.errcode){
					that.warning(response);
					return ;
				}
				that.signInfo();
			});
		}
	});
	
	
	Signature.addIcon({
		iconClass:'kg-icon-sealinfo',
		title:'签章验证',
		id:"signatureInfo",
		enable: true,
		click: function(event){
			event.preventDefault();
			event.stopPropagation();
			this._verify();
			if(Signature.options.verifySignatureInfo !== undefined && Signature.options.verifySignatureInfo){
				if((Signature.options.verifySignatureInfo(this.modified,this.modifiedItems,this.signatureData)))
					return;
			}
			this.signatureInfo();
		}
	});
		
})(this , kinggrid ,Signature, jQuery);