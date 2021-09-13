(function (root , kinggrid ,kingPlus , Signature , $) {
	'use strict';
	function getLen(v) {
        return Math.sqrt(v.x * v.x + v.y * v.y);
    }

    function dot(v1, v2) {
        return v1.x * v2.x + v1.y * v2.y;
    }

    function getAngle(v1, v2) {
        var mr = getLen(v1) * getLen(v2);
        if (mr === 0) return 0;
        var r = dot(v1, v2) / mr;
        if (r > 1) r = 1;
        return Math.acos(r);
    }

    function cross(v1, v2) {
        return v1.x * v2.y - v2.x * v1.y;
    }

    function getRotateAngle(v1, v2) {
        var angle = getAngle(v1, v2);
        if (cross(v1, v2) > 0) {
            angle *= -1;
        }

        return angle * 180 / Math.PI;
    }
    var AlloyFinger = function (el, option) {
		this.preV = { x: null, y: null };
        this.pinchStartLen = null;
        this.scale = 1;
        this.isDoubleTap = false;
        this.rotate = option.rotate || function () { };
        this.pointStart = option.pointStart || function () { };
        this.multipointStart = option.multipointStart || function () { };
        this.multipointEnd=option.multipointEnd||function(){};
        this.pinch = option.pinch || function () { };
        this.swipe = option.swipe || function () { };
        this.tap = option.tap || function () { };
        this.doubleTap = option.doubleTap || function () { };
        this.longTap = option.longTap || function () { };
        this.singleTap = option.singleTap || function () { };
        this.pressMove = option.pressMove || function () { };

        this.delta = null;
        this.last = null;
        this.now = null;
        this.tapTimeout = null;
        this.touchTimeout = null;
        this.longTapTimeout = null;
        this.swipeTimeout=null;
        this.x1 = this.x2 = this.y1 = this.y2 = null;
        this.preTapPosition={x:null,y:null};
        
        el.addEventListener("touchstart", this.start.bind(this), false);
        el.addEventListener("touchmove", this.move.bind(this), false);
        el.addEventListener("touchend", this.end.bind(this), false);
        el.addEventListener("touchcancel",this.cancel.bind(this),false);

        
        
    };

    AlloyFinger.prototype = {
        start: function (evt) {
        	
            if(!evt.touches)return;
            this.now = Date.now();
            this.x1 = evt.touches[0].pageX;
            this.y1 = evt.touches[0].pageY;
            this.delta = this.now - (this.last || this.now);
            this.pointStart(evt);
            if(this.preTapPosition.x!==null){
                this.isDoubleTap = (this.delta > 0 && this.delta <= 250&&Math.abs(this.preTapPosition.x-this.x1)<30&&Math.abs(this.preTapPosition.y-this.y1)<30);
            }
            this.preTapPosition.x=this.x1;
            this.preTapPosition.y=this.y1;
            
            
            this.last = this.now;
            var preV = this.preV,
                len = evt.touches.length;
            if (len > 1) {
                var v = { x: evt.touches[1].pageX - this.x1, y: evt.touches[1].pageY - this.y1 };
                preV.x = v.x;
                preV.y = v.y;
                this.pinchStartLen = getLen(preV);
                this.multipointStart(evt);
            }
            this.longTapTimeout = setTimeout(function(){
                this.longTap(evt);
            }.bind(this), 750);
        },
        move: function (evt) {
            if(!evt.touches)return;
            var preV = this.preV,
                len = evt.touches.length,
                currentX = evt.touches[0].pageX,
                currentY = evt.touches[0].pageY;
            this.isDoubleTap=false;
            if (len > 1) {
                var v = { x: evt.touches[1].pageX - currentX, y: evt.touches[1].pageY - currentY };

                if (preV.x !== null) {
                    if (this.pinchStartLen > 0) {
                        evt.scale = getLen(v) / this.pinchStartLen;
                        this.pinch(evt);
                    }

                    evt.angle = getRotateAngle(v, preV);
                    this.rotate(evt);
                }
                preV.x = v.x;
                preV.y = v.y;
            } else if (this.x2 !== null) {
                evt.deltaX = currentX - this.x2;
                evt.deltaY = currentY - this.y2;
                this.pressMove(evt);
            }
            this._cancelLongTap();
            this.x2 = currentX;
            this.y2 = currentY;
            evt.preventDefault();
        },
        end: function (evt) {
        
        	
            if(!evt.changedTouches)return;
            this._cancelLongTap();
            var self = this;
            if( evt.touches.length<2){
                this.multipointEnd(evt);
            }
            //swipe
            if ((this.x2 && Math.abs(this.x1 - this.x2) > 30) ||
                (this.y2 && Math.abs(this.preV.y - this.y2) > 30)) {
                evt.direction = this._swipeDirection(this.x1, this.x2, this.y1, this.y2);
                this.swipeTimeout = setTimeout(function () {
                    self.swipe(evt);

                }, 0)
            } else {
            
            
                this.tapTimeout = setTimeout(function () {
                    self.tap(evt);
                    // trigger double tap immediately
                    if (self.isDoubleTap) {
                        self.doubleTap(evt);
                        clearTimeout(self.touchTimeout);
                        self.isDoubleTap = false;
                    }else{
                        self.touchTimeout=setTimeout(function(){
                            self.singleTap(evt);
                        },250);
                    }
                }, 0)
            }

            this.preV.x = 0;
            this.preV.y = 0;
            this.scale = 1;
            this.pinchStartLen = null;
            this.x1 = this.x2 = this.y1 = this.y2 = null;
        },
        cancel:function(){
            clearTimeout(this.touchTimeout);
            clearInterval(this.tapTimeout);
            clearInterval(this.longTapTimeout);
            clearInterval(this.swipeTimeout);
        },
        _cancelLongTap: function () {
            clearTimeout(this.longTapTimeout);
        },
        _swipeDirection: function (x1, x2, y1, y2) {
            return Math.abs(x1 - x2) >= Math.abs(y1 - y2) ? (x1 - x2 > 0 ? 'Left' : 'Right') : (y1 - y2 > 0 ? 'Up' : 'Down')
        }
    };
    
    var Utils = kinggrid.Utils;
    
    
    Utils.extend(Signature.options.template,{
		
    	infoBtl:'<div class="kg-dialog kg-dialog-info" id="kg-info">'+
		'<%var modified = this.modified;%>'+
		'<%var canSign = this.signatureData.signMeta|| this.canSign(); %>'+
		'<div class="kg-content">'+
			'<div class="kg-tab">'+
				'<ul class="kg-nav clearfix">'+
					'<li class=" <% modified?"":"active"%> "><a href="#" kg-target="signatureinfo">签章信息</a></li>'+
					'<%if(canSign){%>'+
					'<li class="" ><a href="#" kg-target="certinfo">证书信息</a></li>'+
					'<%}%>'+
				'</ul>'+
				'<div class="kg-tab-content">'+
					'<div class="kg-tab-pane <% modified?"":"active"%> signatureinfo">'+
						'<% var SDATA = this.signatureData; %>'+
						'<% var modifiedItems = this.modifiedItems; %>'+
						'<div class="kg-meta">'+
							'<div class="clearfix kg-item ">'+
								'<label class="kg-label">检测结果：</label>'+
								'<span class="kg-value"><%modified?"保护数据被篡改！":"保护数据正常！"%></span>'+
							'</div>'+
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
						'<div>'+
							'<button type="button" id="revoke_<%this.signatureid%>" class="kg-button ">撤销签章</button>'+
						'</div>'+
					'</div>'+
					'<%if(canSign){%>'+
					'<div class="kg-tab-pane certinfo">'+
						'<%var noCertInfo = !this.signatureData.signMeta; %>'+
						'<%if(!noCertInfo){%>'+
						'<%var certinfo = this.signatureData.signMeta.certinfo; %>'+
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
						'<%}else{%>'+
						
						'<div class="kg-meta">'+
							'<div class="clearfix kg-item kg-visble ">'+
								'<label class="kg-label"></label>'+
								'<span class="kg-value">----------------------------------------------------------------------------------------------------------------------</span>'+
							'</div>'+
							'<div class="clearfix kg-item kg-nosign">'+
								'<label class="kg-label">提示信息：</label>'+
								'<span class="kg-value">当前文档未做数字签名</span>'+
							'</div>'+
							'<div class="clearfix kg-item kg-visble">'+
								'<label class="kg-label"></label>'+
								'<span class="kg-value">--------------------------------------------------------------------</span>'+
							'</div>'+
						'</div>'+
						
						'<%}%>'+
						'<%if(noCertInfo){%>'+
						'<div>'+
							'<button type="button" id="sign_<%this.signatureid%>" class="kg-button">数字签名</button>'+
						'</div>'+
						'<%}else{%>'+
						'<div>'+
							'<button type="button" id="verifysign_<%this.signatureid%>" class="kg-button">签名验证</button>'+
						'</div>'+
						'<%}%>'+
					'</div>'+
					'<%}%>'+
				'</div>'+
			'</div>'+
		'</div>'+
	'</div>',
		
		handwritedlg: '<div class="kg-dialog kg-dialog-info" id="kg-handwrite">'+
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
	    '<canvas id="canvasId" width=700px height=300px>' +
	    '</canvas><br />'+
		 '<div id="div-judge" class="kg-tab-content" align="center">' +
			'<button type="button" id="clearid" class="kg-button">清除</button>' +
			'<button type="button" id="okid" class="kg-button">签名</button>' +
		'</div>' +
	    '</div>',
		
	    barcodedlg: '<div class="kg-dialog kg-dialog-info" id="kg-barcode">'+
        '<label class="kg-label">二维码内容：</label>'+
        '<textarea id="bcId" align="center"></textarea><br />' +
        '<div id="output"><br /></div>' +
        '<div id="div-judge" class="kg-tab-content">' +
		'<button type="button" id="bc_clearid" class="kg-button">清除</button>' +
		'<button type="button" id="bc_okid" class="kg-button">确定</button>' +
		'</div>' +
        '</div>',
        
        scanbarcodedlg : '<div class="kg-dialog kg-dialog-info" id="kg-scanBC">'
	        + '<div id="contentHolder" style="width:320px;height:320px; background:red;">'         
	        + '<video id="html5_qrcode_video" height="320px" width="320px" autoplay></video>'  
	        + '<canvas id="qr-canvas" width="320px" height="320px" style="display:none;">'         
	        + '</canvas> <br/> '   
	        + '</div>' +  
        '</div>'
	});
   
    Signature._create.prototype.onshowSealsDialog =  function(dialog){
    	var that = this;
    	dialog.find('.arrow').hide();
	    var switcher = dialog.find('#kg-switcher');
	    var event = {
	    	swipe: function(event){
	    		that.switcher[event.direction === 'Left'?'swipePrev':'swipeNext']();
	    	},
	    	tap: function(){
	    		that.switcher.swipeNext();
	    	}
	    }
	    that.switcher.animate = false;
		new AlloyFinger(switcher[0] , event);
    }
    
    Signature.prototype.onhandleImg = function(imgDiv){
    	var that = this;
    	var bM = true;
    	/**
    	 * 点击事件
    	 */
    	var tapFn =  function(){
    		that.verify();
    		that.signatureInfo();
    	}
    
    	if(that.canMove(imgDiv)){
    		var moveFn = function(event){
    			if(!bM) return;
    		   	event.originalEvent = event;
    		   	var api = that.runMove(event , imgDiv ,  function(move){
    		   		if(!move){
						tapFn();
					}
			   	});
			   	event.preventDefault();
			   	event.stopPropagation();
			}
			Utils.addEvent(imgDiv , 'mousedown touchstart' , moveFn);
    	}else{
    		Utils.addEvent(imgDiv , 'click' , tapFn);
    	}
    	
    	var overFn = function(event){
    		if(Signature.options.extra !== undefined){
				var id = imgDiv.attributes.signatureid.nodeValue;
				if(id){
					var o = Signature.options.extra[id];
					if(o != null){
						bM = o.icon_move();
					}
				}
			}
    	}
    	Utils.addEvent(imgDiv , 'mouseover touchstart' , overFn);
    }
    
    /**
     * 显示签章信息
     */
    Signature.prototype.signatureInfo = function(){
		var that = this;
		var config = {
				title:false,
				target:that,
				onShow: function(){
					var d = this;
					var kgTab = d.find('.kg-tab');
					d.tab = Utils.tab(kgTab);
					
					var verifySignDom = d.find('#verifysign_'+that.signatureid)[0];
					if(verifySignDom){
						verifySignDom.onclick = function(){
							that.verifySignData(function(response){
								if(!response.result){
									if(response.errcode){
										that.warning(response);
									}else{
										that.warning('签名数据被篡改');
									}
								}else{
									that.warning('签名数据验证正常');
								}
							});
						}
					}	
					var signDom = d.find('#sign_'+that.signatureid)[0];
					if(signDom){
						signDom.onclick =  function(){
							that.signSignature(function(response){
								if(response.result){
									that.warning('数字签名成功！');
									d.remove();
								}
							});
						};
					}
					d.find('#revoke_'+that.signatureid)[0].onclick = function(){
						that.revokeSignature(function(response){
							if(response.result){
								d.remove();
							}
						});
					};
				}
			}
		return that.showDialog('infoBtl' , config );
	}
    
    Signature.prototype.handWriteDlg = function(config, callback){
    	var that = this;
    	
    	/*if(config.width && config.height){
			  var html = Signature.options.template["handwritedlg"];
			  var tmp = html.substring(0, html.indexOf("width=")+6);
			  var tmp1 =  html.substring(html.indexOf("px "), html.length);
			  tmp += config.width;
			  tmp += tmp1;
			  html = tmp.substring(0, tmp.indexOf("height=")+7);
			  tmp1 =  tmp.substring(tmp.indexOf("px>"), tmp.length);
			  html += config.height;
			  html += tmp1;
			  Signature.options.template["handwritedlg"] = html;
		   }*/
    	
    	var hwdlg = that.showDialog('handwritedlg' , config );
/*    	var canvas = document.getElementById("canvasId");
    	var content = canvas.getContext("2d");
    	if (window.screen.orientation.angle == 90 || window.screen.orientation.angle == -90) {
			//ipad、iphone竖屏；Andriod横屏
    		 canvas.width = parseInt(window.screen.availWidth)*0.9;
		     canvas.height = parseInt(window.screen.availHeight)*0.5;
		     //content.fillRect(0, 0, canvas.width, canvas.height);
			
		}else if(window.screen.orientation.angle == 180 || window.screen.orientation.angle == 0){
			//ipad、iphone横屏；Andriod竖屏
		     canvas.width = parseInt(window.screen.availWidth)*0.9;
		     canvas.height = parseInt(window.screen.availHeight)*0.5;
		    // content.fillRect(0, 0, canvas.width, canvas.height);
		}
    	$(window).bind( 'orientationchange', function(e){
    		var data = content.getImageData(0, 0, canvas.width, canvas.height);
    		var img = new Image();
    		img.src = signaturePad.toDataURL();
    		if (window.screen.orientation.angle == 90 || window.screen.orientation.angle == -90) {
    			//ipad、iphone竖屏；Andriod横屏
        		 canvas.width = parseInt(window.screen.availWidth)*0.9;
    		     canvas.height = parseInt(window.screen.availHeight)*0.5;
    		}else if(window.screen.orientation.angle == 180 || window.screen.orientation.angle == 0){
    			//ipad、iphone横屏；Andriod竖屏
    		     canvas.width = parseInt(window.screen.availWidth)*0.9;
    		     canvas.height = parseInt(window.screen.availHeight)*0.5;
    		}
    		 //content.fillRect(0, 0, canvas.width, canvas.height);
    		// content.putImageData(data,0,0,0, 0, canvas.width, canvas.height);
    		content.drawImage(img, 0, 0, canvas.width, canvas.height);
    	});*/
    	
    	config.penColor = document.getElementById("hw_color").value;
    	config.minWidth = 0.5;
    	config.maxWidth = 4.5;
		var signaturePad = new SignaturePad(canvas, config);
		
		var hwColor = document.getElementById("hw_color");
		hwColor.onchange = function(){
			var sle = hwColor.value;
			signaturePad.penColor = sle;
		};
		var hwWidth = document.getElementById("hw_width");
		hwWidth.onchange = function(){
			var sle = hwWidth.value;
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
		};
		
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
							height: config.image_height || "3.00",
							width:  config.image_width || "7.00",
							imageData: base64Data,
							name: document.getElementById("nameid").value
					};
				    callback(param);
				}
			}
		}
    }
    
    Signature.prototype.barCodeDlg = function(options, callback){
		var that = this;
		var config = {
			title:'二维码',
			target:that,
			onCancel:false
		}
		
		var dlg = that.showDialog('barcodedlg' , config );
		var clearBtn = document.getElementById("bc_clearid");
		var okBtn = document.getElementById("bc_okid");
		var ta = document.getElementById("bcId");
		
		if(clearBtn){
			clearBtn.onclick = function(){
				ta.value = "";
			}
		}
		if(okBtn){
			okBtn.onclick = function(){
				dlg.close();
				var bc = $('#output').qrcode(ta.value);
				var param = {
						height: options.image_height || "5",
						width:  options.image_width || "5",
						imageData: bc,
						name: "二维码"//document.getElementById("nameid").value
				};
				callback(param);
			}
		}
	}
    
    Signature.prototype.scanBCDlg = function(options, callback){
		var that = this;
		var config = {
			title:'扫码签章',
			target:that,
			onCancel:false
		}
     
       var sbcdlg = that.showDialog('scanbarcodedlg' , config );
      // window.addEventListener("DOMContentLoaded", function (){  
		try{  
			var canvas = document.getElementById("qr-canvas");  
		    var context = canvas.getContext("2d");  
		    var video = document.getElementById("html5_qrcode_video");           
		    var videoObj = { "video":true, audio:false};
		    navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia;   
		    window.URL = window.URL || window.webkitURL || window.mozURL || window.msURL; 
		    if (navigator.getUserMedia) { 
		    	navigator.getUserMedia({video: true, audio: false}, function(stream){
		            video.src = window.URL.createObjectURL(stream);	
		   		    //firefox不支持src,由下面代替
		            //video.mozSrcObject= stream
		    	    video.play();
		    	}, function(error){
		    		alert("出错信息: " + err.name);
		    	}); 
		    }
			//这个是拍照按钮的事件，                   
			var sto = setInterval(function(){//防止调用过快  
				if(context){
					context.drawImage(video, 0, 0, 640, 480);
					var imageData = context.getImageData(0, 0, 640, 480);
					
					 var decoded = jsQR.decodeQRFromImage(imageData.data, imageData.width, imageData.height);
					 if(decoded) {
				          sbcdlg.close();
				          window.clearInterval(sto);
				          options.content = decoded;
				          var param = {
									height: options.image_height || "5",
									width:  options.image_width || "5",
									imageData: $('#output').qrcode(decoded),
									name: "二维码"
							     };
						  callback(param);
				       }
				}
			       /*if(context && qrcode)  {  
					context.drawImage(video, 0, 0, 320, 320);
					qrcode.callback = function(data) {
						// 得到扫码的结果
						alert(data);
						if (data.indexOf("error") < 0) {
							alert(data);
							sbcdlg.close();
							barCodeDlg(options, callback);
							window.clearInterval(sto);
						}
					};
					alert(canvas.toDataURL());
					qrcode.decode(canvas.toDataURL());
			    }  */
			},2000);
			
		}catch(e){        
				printHtml("浏览器不支持HTML5 CANVAS");         
			}   
		//}, false); 
	}

    
	function getsec(str) { 
		var str1=str.substring(1,str.length)*1; 
		var str2=str.substring(0,1); 
		if (str2=="s") { 
			return str1*1000; 
			} 
		else if (str2=="h") {
			return str1*60*60*1000; 
			} 
		else if (str2=="d") {
			return str1*24*60*60*1000; 
			} 
		} 
	
	function setCookie(name,value,time) { 
		var strsec = getsec(time); 
		var exp = new Date(); 
		exp.setTime(exp.getTime() + strsec*1); 
		document.cookie = name + "="+ escape (value) + ";expires=" + exp.toGMTString(); 
		} 

	
	function getCookie(name) { 
		var arr,reg=new RegExp("(^| )"+name+"=([^;]*)(;|$)"); 
		if(arr=document.cookie.match(reg)) 
			return unescape(arr[2]); 
		else
			return null;
		} 

	function delCookie(name) { 
		var exp = new Date(); 
		exp.setTime(exp.getTime() - 1); 
		var cval=getCookie(name); 
		if(cval!=null) 
			document.cookie= name + "="+cval+";expires="+exp.toGMTString(); 
		}
	
    Signature.prototype.onshowSealsDialog_PW = function(d){
    	var that = this;
    	var keysn = that.keyData.keysn;
 
    	var checkBox = document.getElementById("kg-remenberPwd");//d.find('.kg-remenberPwd');
    	if(getCookie("ck") == "true"){
    		checkBox.checked = true;
    	}
    	if(checkBox.checked){
    		var ksn = getCookie("ksn");
    		if(keysn == ksn){
    			var pw = document.getElementById("kg-password");//d.find('.kg-password');
    			var cpw = getCookie("pwd");
    			if(cpw != null){
    				pw.value = cpw;
    			}
    			else
    				pw.value = "123";
    		}
    	}
    }
    
    Signature.prototype.onexecSuccess = function(d, pw, isCheck){
    	var that = this;
    	var keysn = that.keyData.keysn;
    	if(isCheck){
    		var ksn = getCookie("ksn");
    		if(keysn != ksn){
    			var valTimeOut = Signature.options.pw_timeout;
    			if(typeof(valTimeOut)=="undefined" || 
    					(valTimeOut.indexOf("s") < 0 && 
    					valTimeOut.indexOf("h") < 0 && valTimeOut.indexOf("d") < 0)){
    				valTimeOut = 's1800';
    			}
    			
    			setCookie("ksn",keysn,valTimeOut); 
        		setCookie("pwd",pw,valTimeOut); 
        		setCookie("ck","true",valTimeOut); 
    		}
    	}
    	else{
    		delCookie("ksn"); 
    		delCookie("pwd"); 
    		delCookie("ck"); 
    	}
    }
    
	var plus = kingPlus();
	
	/**
	 * 更改alert的窗口属性
	 */
	Utils.extend(plus.alertConfig , {
		cancelDisplay:true,
		quickClose: false,
		backdropOpacity:0.1
		
	});
	
	/**
	 * 更改loading的窗口属性
	 */
	Utils.extend(plus.loadingConfig , {
		cancelDisplay:true,
		quickClose: false,
		backdropOpacity:0.1,
		
	});
	/**
	 * 更改默认窗口属性
	 */
	Utils.extend(plus.dialogConfig , {
		cancelDisplay:false,
		quickClose: true
	});
	
		
})(this , kinggrid , kingPlus,Signature, jQuery);
