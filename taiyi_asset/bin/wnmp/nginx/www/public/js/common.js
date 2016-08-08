function js_alert(msg,url){
	/*var box1 = new Boxy("<div><p class='Font14 popup'>"+msg+"</p><p><a href='javascript:close_boxy();'>确定</a></p></div>", { 
										title: "消息", //对话框标题 
										modal: true, //是否为模式窗口 
										draggable: true, //是否可以拖动 
										closeable:false
				}); 
	box1.resize(300, 100); //设置对话框的大小 
	*/
	 Boxy.ask(msg, ["确定"], function(val) { 
		if(url != null){
			location.href=url;	
		} 
	}, {title: "消息"});
}

function js_confirm(msg){
	Boxy.ask(msg, ["确定","取消"], function(val) { 
		if(val == '确定'){
			return true;	
		}else{
			return false;			
		}
	}, {title: "消息"});
	
}
