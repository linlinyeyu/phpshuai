
var mod = angular.module("datepicker",[]);

mod.directive("datepicker",['$filter', function($filter){
	var dateFilter = $filter('date');
	var linkFun = { 
			require : 'ngModel',
			link:function($scope, element, attrs, ctrl) {
				
				ctrl.$formatters.push(function(value){
					if(!value || value == 0){
						return ;
					}
					return dateFilter(value * 1000, 'yyyy-MM-dd');
				});  
	            ctrl.$parsers.unshift(function(value){
	            	if(!value || value == 0){
	            		return 0;
	            	}
	            	var arr = value.split("-");
	            	if(arr.length == 3){
	            		var date = new Date(arr[0], arr[1] - 1, arr[2]);
	            		return date.getTime() / 1000;
	            	}
	            	return 0;
	            }); 
				element = jQuery(element);
		        element.datepicker();
		        element.attr("readonly","readonly");
		        element.change(function() {
		        	element.datepicker( "option", "showAnim","slideDown" );
		        });
		    	jQuery.datepicker.regional['zh-CN'] = datepickeroptions;  
			    jQuery.datepicker.setDefaults($.datepicker.regional['zh-CN']);
			    
			    
			}};
	return linkFun;
}]);

mod.directive("days", function(){
	var linkFun = { 
			require : 'ngModel',
			link:function($scope, element, attrs, ctrl) {
				
				ctrl.$formatters.push(function(value){
					if(!value || value == 0){
						return ;
					}
					return value / 60 / 60 / 24;
				});  
	            ctrl.$parsers.unshift(function(value){
	            	if(!value || value == 0){
	            		return 0;
	            	}
	            	return value * 60 * 60 * 24;
	            }); 
			    
			}};
	return linkFun;
});

	