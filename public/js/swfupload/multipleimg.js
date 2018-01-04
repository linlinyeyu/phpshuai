var root = "/public/";
		if(create_link){
			create_script(root + "/js/swfupload/handlers");
			create_script(root + "/js/swfupload/swfupload.js");
		}
var multipleimg = angular.module("multipleimg",[]);
var swfu;
multipleimg.directive('multipleimg',function(){
	return{
			restrict:'E',
			template:'<div class="controls">'+
						'<div id="content">'+
					          '<span id="spanButtonPlaceholder"></span>'+
					         
							'<div id="divFileProgressContainer"></div>'+
      						'<div id="thumbnails"></div>'+
      					'</div>'+
					'</div>',
			replace:true,
			link:function(scope,element,attrs){
				window.onload = function () {
					swfu = new SWFUpload({
						// 后端设置
						upload_url: "{:U('banner/qiniu')}",
						post_params: {"PHPSESSENID": "f5lu3e8qlo0kbo32741dbfc4k4"},

						// 文件上传设置
						file_size_limit : "2 MB",	// 2MB
						file_types : "*.jpg;*.gif;*.png;*.jpeg",
						file_types_description : "JPG Images",
						file_upload_limit : "0",

						// 事件处理程序设置 - 这些功能在Handlers.js定义
						file_queue_error_handler : fileQueueError,
						file_dialog_complete_handler : fileDialogComplete,
						upload_progress_handler : uploadProgress,
						upload_error_handler : uploadError,
						upload_success_handler : uploadSuccess,
						upload_complete_handler : uploadComplete,

						// 按钮设置
						button_image_url: "__PUBLIC__/images/button.png",
						button_width: "133",
						button_height: "36",
						button_placeholder_id: "spanButtonPlaceholder",
						button_text: '<span class="theFont">上传图片</span>',
						button_text_style: ".theFont{font-size:18;font-weight:800;text-align:center}",
						button_text_left_padding: 5,
						button_text_top_padding: 6,
						button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
						button_cursor: SWFUpload.CURSOR.HAND,

						flash_url : "__PUBLIC__/js/swfupload/swfupload.swf",
						flash9_url : "http://www.swfupload.org/swfupload_fp9.swf",
						custom_settings : {
							upload_target : "divFileProgressContainer"
						},
						debug: false
					});
				}
			}
		}
	});
// window.onload = function () {
// 	swfu = new SWFUpload({
		
// 		// 后端设置
// 		upload_url: "{:U('banner/file')}",
// 		post_params: {"PHPSESSENID": "f5lu3e8qlo0kbo32741dbfc4k4"},

// 		// 文件上传设置
// 		file_size_limit : "2 MB",	// 2MB
// 		file_types : "*.jpg;*.gif;*.png;*.jpeg",
// 		file_types_description : "JPG Images",
// 		file_upload_limit : "0",

// 		// 事件处理程序设置 - 这些功能在Handlers.js定义
// 		file_queue_error_handler : fileQueueError,
// 		file_dialog_complete_handler : fileDialogComplete,
// 		upload_progress_handler : uploadProgress,
// 		upload_error_handler : uploadError,
// 		upload_success_handler : uploadSuccess,
// 		upload_complete_handler : uploadComplete,

// 		// 按钮设置
// 		button_image_url: "__PUBLIC__/images/button.png",
// 		button_width: "133",
// 		button_height: "36",
// 		button_placeholder_id: "spanButtonPlaceholder",
// 		button_text: '<span class="theFont">上传图片</span>',
// 		button_text_style: ".theFont{font-size:18;font-weight:800;text-align:center}",
// 		button_text_left_padding: 5,
// 		button_text_top_padding: 6,
// 		button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
// 		button_cursor: SWFUpload.CURSOR.HAND,
		
// 		flash_url : "__PUBLIC__/js/swfupload/swfupload.swf",
// 		flash9_url : "http://www.swfupload.org/swfupload_fp9.swf",
// 		custom_settings : {
// 			upload_target : "divFileProgressContainer"
// 		},
		
// 		debug: false
// 	});
// };