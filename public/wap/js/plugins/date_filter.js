var app = angular.module("date-filter", []);
app.filter('date_count_down',function(){
	return function(input, fmt){
		input = input || "0";
		fmt = fmt || "dd天hh时mm分ss秒";
		if(input < 0){
			input = 0;
		}
		var date = new Date(input * 1000);
		var day = parseInt(input / (24 * 60 * 60 ),0);
		var hours = date.getHours() - 8;
		if(hours < 0){
			hours += 24;
		}
		var o = {
		        "d+": day, //日 
		        "h+": hours, //小时 
		        "m+": date.getMinutes(), //分 
		        "s+": date.getSeconds(), //秒 
		        "S": date.getMilliseconds() //毫秒 
		    };
		for (var k in o){
			if (new RegExp("(" + k + ")").test(fmt)) {
				if( k =="d+" && o[k] == 0){
					fmt = fmt.replace(RegExp.$1, "");
					fmt = fmt.substr(1);
				}else{
					fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
				}
			}
		}
		   
		return fmt;
	}
});
