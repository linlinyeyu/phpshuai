var root = "/public/";
		if(create_link){
			create_script(root + "/js/ajaxfileupload.js");
		}
var uploadimg = angular.module("uploadimg",[]);
uploadimg.directive('uploadimg',function(){
	return{
			restrict:'E',
			scope : {},
			template:'<div >'+
					'<img  ng-click="choose()" class="choose" style="width:{{imgWidth}}px;height:{{imgHeight}}px;"/>'+
					'<input type="hidden"  name="{{image_name}}" value="{{image_value}}">'+
					'<input  ng-model="file" id="{{fileid}}" type="file" name="file" style="display:none;" onchange="angular.element(this).scope().upload()"/>'+
					'<span style="display:block;margin-top:5px;">建议尺寸：{{width}}*{{height}}</span>'+
					'</div>',
			replace:true,
			link:function(scope,element,attrs){
				var is_qiniu = attrs.isQiniu;
				element = $(element);
				
				var is_multiple = attrs.isMultiple;
				var img = element.find("img");
				var default_img_url = attrs.defaultImgUrl ? attrs.defaultImgUrl : '/public/images/banner.png';
				var error_img_url = attrs.errorImgUrl ? attrs.errorImgUrl : default_img_url;
				var image_url = attrs.imageUrl ? attrs.imageUrl : '';
				if(image_url){
					scope.image_value = image_url;
					img.attr("src", image_url.indexOf("http") == 0 || image_url.indexOf("https") == 0 ? image_url : GV.IMG_URL +  image_url); 
					
				}else{
					img.attr("src",default_img_url);
				}
				
				img.error(function(){
					img.attr("src", error_img_url);
				});
				
				var resize = attrs.resize ? attrs.resize : 0;
				scope.image_name = attrs.imageName ? attrs.imageName : "image";
				scope.imgWidth = 150;
				scope.width = attrs.width ? attrs.width : scope.imgWidth;
				scope.height = attrs.height ? attrs.height : scope.imgWidth;
				scope.value =element.find("input[type=file]").value;
				scope.fileid = attrs.fileid ? attrs.fileid : "file";
				var rate = scope.height / scope.width;
				if(resize){
					scope.imgHeight = 1 * scope.imgWidth;
				}else{
					scope.imgHeight = rate * scope.imgWidth;
				}
				
				scope.choose = function(){
					element.find("input[type=file]").click();
				}
				
				var data = {};
				data.cate = attrs.cate ? attrs.cate : "image";
				
				var url = attrs.url ? attrs.url : '/admin/Uploadimage/upload_image.html';
				scope.upload = function(){
					jQuery.ajaxFileUpload({
					 	url : url,
					 	fileElementId : scope.fileid,
					 	dataType : 'json',
					 	data : data,
					 	success : function(data){
					 		if(data.errcode >= 0){
					 			var mUrl = data.content.name;
								element.find("img").attr("src", GV.IMG_URL + mUrl);
								$("input[name="+scope.image_name+"]").val(mUrl);
								if(attrs.callback && scope[attrs.callback]){
									scope[attrs.callback](GV.IMG_URL + mUrl);
								}
							}
					 	}
					 });
				}
			},
		};
});
