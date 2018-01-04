var root = "/public/autocomplete/";
		if(create_link){
			create_link(root + "/css/jquery.ui.core.css");
			create_link(root + "/css/jquery.ui.menu.css");
			create_link(root + "/css/jquery.ui.autocomplete.css");

			create_script(root + "/js/jquery.ui.core.js");
			create_script(root + "/js/jquery.ui.widget.js");
			create_script(root + "/js/jquery.ui.position.js");
			create_script(root + "/js/jquery.ui.menu.js");
			create_script(root + "/js/jquery.ui.autocomplete.js");
		}
var mod = angular.module("autocomplete",[]);

mod.directive("yzlAutocomplete", function(){
	var linkFun = function($scope, element, attrs) {
		
        var $input = jQuery(element);
        var option = {};
        var url = attrs.url ;
        var keyword = attrs.keyword ? attrs.keyword : "term";
        
        
        option.source = function(request, response){
        	var data = {};
	        data[keyword] = request.term;
        	jQuery.ajax({
        		url : url ,
        		data : data,
        		dataType: 'json',
        		success : function(data){
        			response(jQuery.map(data,function(item){
        				return {
        					label : item[attrs.labelKey],
        					value : item[attrs.labelKey],
        					id    : item[attrs.valueKey]
        				};
        			}));
        		}
        	});
        }
        option.minLength = attrs.minLength > 0 ? attrs.minLength : 1;
        option.select = function(event , ui){
        	if($scope[attrs.callback]){
        		$scope[attrs.callback](ui.item);
        	}
        }
        $input.autocomplete(option);
    };
    return linkFun;
});

	