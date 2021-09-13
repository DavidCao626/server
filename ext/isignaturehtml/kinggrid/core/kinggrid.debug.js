(function(KG){
	KG.addRequestListener('response', function(data , xhd){
		var str  = JSON.stringify(data);
		if(str.length>1024){
			str=str.substring(0,1024);
		}
		$('body').prepend('<div>'+str +'</div>');
	});
	
	KG.addRequestListener('beforeAjax', function(){
		var str = '';
		for(var i =0 ; i<arguments.length;i++){
			str +=arguments[i];
		}
		$('body').prepend('<div>'+str +'</div>');
	});
	
})(kinggrid);