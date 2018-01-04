var app = angular.module("image-resize",[]);

app.directive("imageResize", function(){
	return {
		link : function(scope, element, attrs){
			
			var default_src = null;
			//若在标签中设置了isAvater属性且为true，则设定默认头像src。
			if(attrs.isAvater){
				default_src = '/public/images/new/Default-Avatar.png';
			}
			
			var img = $(element);
			//若获取到元素的src布尔值为false，则将其替换为默认头像。
			if(!img.attr("src")){
				img.attr("src", default_src);
			}
			//获取到的元素第一个加载出错时，将其src改为默认头像。
			img.get(0).onerror = function(){
				img.attr("src", default_src);
			}
			//默认或获取宽度、高度、比值
			var width = attrs.width ? attrs.width : "100%";
			var ratio = attrs.ratio ? attrs.ratio : 1;
			var height = attrs.height ? attrs.height : "0";
			var is_parent = !!attrs.isParent;
			var clientWidth = is_parent ? $(element).parent().width() :  document.documentElement.clientWidth;
			var clientHeight = is_parent ? $(element).parent().height() : document.documentElement.clientHeight;
			//设定图片宽度
			if(width.indexOf("%") > 0){
				var width = width.replace("%", "") / 100;
				width = clientWidth * width;
			}else if(width.indexOf("px")){
				width = width.replace("px", "") * 1;
			}
			//设定图片高度
			if(height == 0){
				height = width * ratio;
			}else if(height.indexOf("%") > 0){
				var height = height.replace("%", "") / 100;
				height = clientHeight * height;
			}else if(height.indexOf("px")){
				height = height.replace("px", "") * 1;
			}
			$(element).width(width).height(height);
		}
	}
});

