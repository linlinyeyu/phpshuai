var app = angular.module("back-filter", []);
app.directive('onBackRenderFilters',function($timeout){
		return {
			restrict : 'A',
			link : function(scope, element ,attr){
				element.context.onclick =  function(){
					if (document.referrer){
			            history.go(-1);
			        }else{
			            location.replace("/wap/home/index");
			        }
				}
				
			}
		}
	 });
