
$.fn.extend({
		//---元素拖动插件
    dragging:function(data){   
		var $this = $(this);
		var xPage;
		var yPage;
		var X;//
		var Y;//
		var father = $this.parent();
		var defaults = {
			move : 'both',
			hander:1
		}
		var opt = $.extend({},defaults,data);
		var movePosition = opt.move;
		
		var hander = opt.hander;
		
		if(hander == 1){
			hander = $this; 
		}else{
			hander = $this.find(opt.hander);
		}
		
			
		//---初始化
		father.css({"position":"relative","overflow":"hidden"});
		$this.css({"position":"absolute"});
		hander.css({"cursor":"move"});

		var faWidth = father.width();
		var faHeight = father.height();
		var thisWidth = $this.width()+parseInt($this.css('padding-left'))+parseInt($this.css('padding-right'));
		var thisHeight = $this.height()+parseInt($this.css('padding-top'))+parseInt($this.css('padding-bottom'));
		
		var mDown = false;//
		var positionX;
		var positionY;
		var moveX ;
		var moveY ;
		$this.each(function(){
			var $that = $(this);
			$("<span />").addClass("draggable").appendTo($(this)).mousedown(function(e){
				var offset = $(this).offset();
				$that.move = true;
				$that.posix = {'x' : e.pageX , 'y' : e.pageY, 'w' : $that.width(), 'h' : $that.height()};
				e.preventDefault();
				return false;
			});
			$(document).mousemove(function(e){
				if($that.move == true){
					var width = $that.posix.w + e.pageX - $that.posix.x;
					var height = $that.posix.h + e.pageY - $that.posix.y;
					
					var maxWidth = faWidth - $that.position().left;
					var maxHeight = faHeight - $that.position().top;
					
					var minWidth = 10;
					
					var minHeight = 10;
					
					width = width > maxWidth ? maxWidth : width;
					height = height > maxHeight ? maxHeight : height;
					
					width = width < minWidth ? minWidth: width;
					height = height < minHeight ? minHeight : height;
					$that.css({'width': width,'height' : height});
					e.preventDefault();
					return false;
				}
				
			}).mouseup(function(e){
				if($that.move == true){
					$that.move = false;
					thisWidth = $this.width()+parseInt($this.css('padding-left'))+parseInt($this.css('padding-right'));
					thisHeight = $this.height()+parseInt($this.css('padding-top'))+parseInt($this.css('padding-bottom'));
				}
			});
			
			var left = $(this).attr("data-left");
			var top = $(this).attr("data-top");
			if(movePosition.toLowerCase() == 'x'){
				$(this).css({
					left:left 
				});
			}else if(movePosition.toLowerCase() == 'y'){
				$(this).css({
					top:top
				});
			}else if(movePosition.toLowerCase() == 'both'){
				console.log(top);
				$(this).css({
					top:top ,
					left:left 
				});
			}
		});
		
		
		
		hander.mousedown(function(e){
			mDown = true;
			X = e.pageX;
			Y = e.pageY;
			positionX = $this.position().left;
			positionY = $this.position().top;
			return false;
		});
			
		$(document).mouseup(function(e){
			mDown = false;
			
		});
			
		$(document).mousemove(function(e){
			xPage = e.pageX;//--
			moveX = positionX+xPage-X;
			
			yPage = e.pageY;//--
			moveY = positionY+yPage-Y;
			
			function thisXMove(){ //x轴移动
				if(mDown == true){
					$this.css({"left":moveX});
				}else{
					return;
				}
				if(moveX < 0){
					$this.css({"left":"0"});
				}
				if(moveX > (faWidth-thisWidth)){
					$this.css({"left":faWidth-thisWidth});
				}
				return moveX;
			}
			
			function thisYMove(){ //y轴移动
				if(mDown == true){
					$this.css({"top":moveY});
				}else{
					return;
				}
				if(moveY < 0){
					$this.css({"top":"0"});
				}
				if(moveY > (faHeight-thisHeight)){
					$this.css({"top":faHeight-thisHeight});
				}
				return moveY;
			}

			function thisAllMove(){ //全部移动
				if(mDown == true){
					$this.css({"left":moveX,"top":moveY});
				}else{
					return;
				}
				if(moveX < 0){
					$this.css({"left":"0"});
				}
				if(moveX > (faWidth-thisWidth)){
					$this.css({"left":faWidth-thisWidth});
				}

				if(moveY < 0){
					$this.css({"top":"0"});
				}
				if(moveY > (faHeight-thisHeight)){
					$this.css({"top":faHeight-thisHeight});
				}
			}
			if(movePosition.toLowerCase() == "x"){
				thisXMove();
			}else if(movePosition.toLowerCase() == "y"){
				thisYMove();
			}else if(movePosition.toLowerCase() == 'both'){
				thisAllMove();
			}
		});
    }
}); 