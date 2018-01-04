var app = angular.module("lazy-load",[]);

app.factory('LazyLoad',function($http){
	//初始化participate对象
 	var participate = function(){
 		this.items = [];
 		this.busy = false;
         this.page = 1;
         this.finish = false;
         this.data = {};
         this.url = "";
         this.varables = null;
 	}
 	//重置participate对象
 	participate.prototype.reset = function(){
 		var old_items = this.items;
 		this.items = [];
 		if(old_items.length > 0){
 			this.callback(this.items);
 		}
 		this.busy = false;
 		this.finish = false;
 		this.page = 1;
 		this.nextPage();
 	}
 	
 	participate.prototype.nextPage = function(){
 		//finish（全部数据是否请求完成），busy（nextPage函数是否繁忙）
 		if(this.busy || this.finish || !this.url) return;
 		this.busy = true;
 		//page值，即翻页数
 		var data = {'page' : this.page};
 		if(this.data ){
 			var arr = this.data.split("&");
 			for(var i in arr){
 				var s = arr[i].split("=");
 				if(s.length ==2){
 					data[s[0]] = s[1];
 				}
 			}
 		}
 		//绑定用户id到data
 		if(this.need_token){
 			data.access_token = pinet.checkUserAccount();
 		}
 		//发送请求
 		pinet.post(this.url,data,function(response){
          		this.busy = false;
            	if(response.errcode == 0){
            		//发送成功且未返回数据的情况（翻页结束）
            		if(response.content.length == 0){
            			this.finish = true;
            			if(this.callback ){
                			this.callback(this.items);
                		}
            			return;
            		}
            		//将请求到的数据绑定到items数组
            		for(var i = 0; i< response.content.length; i++){
            			this.items.push(response.content[i]);
            		}
            		if(this.varables){
            			
            		}
            		if(this.callback){
            			this.callback(this.items);
            		}
                 this.page ++;
            	}
		//将this值绑定到participate对象
		}.bind(this));	
 	};
 	return participate;
 });
app.directive('infinite', ['$rootScope', '$window', '$timeout','LazyLoad', function($rootScope, $window, $timeout, LazyLoad) {
       return {
         link: function(scope, elem, attrs) {
	    	 var lazyload =  new LazyLoad();
	    	 lazyload.data = attrs.data;
	    	 lazyload.url = attrs.url;
	    	 //绑定items数组到页面标签中
	    	 if(attrs.bind){
	    		 lazyload.callback = function(){
	    			 scope[attrs.bind] = lazyload.items;
	    			 if ($rootScope.$$phase) {
	                     scope.$eval();
	                   } else {
	                     scope.$apply();
	                   }
	    		 }
	    	 }
	    	 
	    	 if(attrs.callback){
	    		 lazyload.callback = scope[attrs.callback]; 
	    	 }
	    	 //绑定lazyload对象到instance
	    	 if(attrs.instance){
	    		 scope[attrs.instance] = lazyload;
	    	 }
	    	 //确定是否需要用户凭证
	    	 if(attrs.needToken){
	    		 lazyload.need_token = true;
	    	 }
	    	 //执行nextPage
	    	 if(lazyload.page == 1){
	    		 lazyload.nextPage();
	    	 }
	    	 
	    	 
	    	 
           var checkWhenEnabled, handler, scrollDistance, scrollEnabled;
           $window = angular.element($window);
           scrollDistance = 0;
           //监听无限滚动距离设定的值
           if (attrs.infiniteScrollDistance != null) {
             scope.$watch(attrs.infiniteScrollDistance, function(value) {
               return scrollDistance = parseInt(value, 10);
             });
           }
           
           scrollEnabled = true;
           checkWhenEnabled = false;
           //监听busy值，并据此改变scrollEnabled和checkwhenenabled的值
           if (lazyload.busy != null) {
             scope.$watch(lazyload.busy, function(value) {
               scrollEnabled = !value;
               if (scrollEnabled && checkWhenEnabled) {
                 checkWhenEnabled = false;
                 return handler();
               }
             });
           }
           
           handler = function() {
             var elementBottom, remaining, shouldScroll, windowBottom;
             windowBottom = $window.height() + $window.scrollTop();
             elementBottom = elem.offset().top + elem.height();
             remaining = elementBottom - windowBottom;
             shouldScroll = remaining <= $window.height() * scrollDistance;
             //确定何时执行翻页函数（shouldScroll：根据距离判定是否滚动）（scrollEnabled:根据busy值判定是否滚动）
             if (shouldScroll && scrollEnabled) {
            	 if ($rootScope.$$phase) {
                 return scope.$eval(lazyload.nextPage());
               } else {
                 return scope.$apply(lazyload.nextPage());
               }
             } else if (shouldScroll) {
               return checkWhenEnabled = true;
             }
           };
           $window.on('scroll', handler);
           scope.$on('$destroy', function() {
             return $window.off('scroll', handler);
           });
           return $timeout((function() {
             if (attrs.infiniteScrollImmediateCheck) {
               if (scope.$eval(attrs.infiniteScrollImmediateCheck)) {
                 return handler();
               }
             } else {
               return handler();
             }
           }), 0);
         }
       };
     }
   ]);
