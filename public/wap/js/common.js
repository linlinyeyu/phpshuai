var pinet= {
     openUrl : function(url, title){
         if(window.openUrl){
            window.openUrl(url ,title);
        }else if(window.webview && window.webview.openUrl){
            webview.openUrl(url, title);
        }else{
            location.href= webviewurl + "?url=" + url + "&title=" + title ;
        }
     },
	 clientClick : function(activity, controller, url, params){
		params = params ? params : [];
        if(window.webview && window.webview.clientClick){
            param = JSON.stringify(params);
            webview.clientClick(activity, controller, param);
        }
        else if(window.clientClick){
            param = JSON.stringify(params);
            window.clientClick(activity, controller,param);
        }else{
            var url_params = "";
            for(var i in params){
                url_params += params[i].key + "=" + params[i].value + "&";
            }
            if(window.parent){
            	window.parent.location.href = url + "?" +url_params;
            }else{
            	location.href = url + "?" + url_params;
            }
            
        }
	 },
     setCookie:function(name, value, path, expire) {
        var Days = 365;
        if(!path){
            path = "/";
        }
        var exp = new Date();
        if(!expire){
            expire = Days * 24 * 60 * 60 ;
        }
        exp.setTime(exp.getTime() + expire * 1000);
        document.cookie = name + "=" + escape(value) + ";expires=" + exp.toGMTString() + ";path=" + path;
    },

    getCookie:function (name) {
        var arr, reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)");
    
        if (arr = document.cookie.match(reg))
            return unescape(arr[2]);
        else
            return "";
    },
    
     removeCookie: function (name, path) {
        var exp = new Date();
        exp.setTime(exp.getTime() - 1);
        if(!path){
            path = "/";
        }
        var cval = this.getCookie(name);
        if (cval != null)
            document.cookie = name + "=" + cval + ";expires=" + exp.toGMTString() + ";path=" + path;
    },
    checkUserAccount: function(is_jump){
        var access_token = this.getCookie("access_token");
        
        return access_token;
    },
    addCls : function (el, cls) {
        el.className = el.className + " " + cls;
    },
    
    removeCls : function(el, cls){
        if(el.className){
            var clses = el.className.split(' ');
            var final = [];
            for(var i in clses){
                if(clses[i].trim() != cls){
                    final.push(clses[i].trim());
                }
            }
            el.className = final.join(" ");
        }
    },

    hasCls : function (el, cls) {
        if (el.className) {
            var clses = el.className.split(' ');
            for (var i in clses) {
                if (clses[i].trim() == cls) {
                    return true;
                }
            }
        }
        return false;
    },
    toggleCls : function (el, cls) {
        if (s.hasCls(el, cls)) {
            s.removeCls(el, cls);
        } else {
            s.addCls(el,cls);
        }
    },
    alert : function(msg,fn){
        if (window.layer) {
            layer.open({
                content : '<div style="text-align:center">' + msg + '</div>',
                btn : ['确定'],
                time : 3,
                callback : fn,
                yes : fn
            });
        } else {
            alert(msg);
            if(fn){
            	fn();
            }
        }
    },
    confirm : function(msg,callback){
        if(window.layer){
            layer.open({
                content : '<div style="text-align:center">' + msg + '</div>',
                btn : ['确定','取消'],
                yes : callback
            });
        }else{
            if(confirm(msg)&&callback){
                callback();
            }
        }
    },
    toast : function(msg,duration){
        if(!duration){
            duration = 1500;
        }
        var div = document.createElement("div");
        this.addCls(div, "toast");
        div.innerHTML = msg;
        var body = document.getElementsByTagName("body")[0];
        body.appendChild(div);
        div.style.opacity = "1";
        setTimeout(function(){
            div.style.opacity = "0";
            setTimeout(function(){
                body.removeChild(div);
            },500)
        },duration);
    },
    extend :function(o,n,override){
    	   for(var p in n)if(n.hasOwnProperty(p) && (!o.hasOwnProperty(p) || override))o[p]=n[p];
    },
    createAjax : function () {
        //1.创建XMLHttpRequest对象     
        //这是XMLHttpReuquest对象无部使用中最复杂的一步     
        //需要针对IE和其他类型的浏览器建立这个对象的不同方式写不同的代码     
        var xmlHttpRequest;
        if (window.XMLHttpRequest) {
            //针对FireFox，Mozillar，Opera，Safari，IE7，IE8     
            xmlHttpRequest = new XMLHttpRequest();
            //针对某些特定版本的mozillar浏览器的BUG进行修正     

        } else if (window.ActiveXObject) {
            //针对IE6，IE5.5，IE5     
            //两个可以用于创建XMLHTTPRequest对象的控件名称，保存在一个js的数组中     
            //排在前面的版本较新     
            var activexName = ["MSXML2.XMLHTTP", "Microsoft.XMLHTTP"];
            for (var i = 0; i < activexName.length; i++) {
                try {
                    //取出一个控件名进行创建，如果创建成功就终止循环     
                    //如果创建失败，回抛出异常，然后可以继续循环，继续尝试创建     
                    xmlHttpRequest = new ActiveXObject(activexName[i]);
                    if (xmlHttpRequest) {
                        break;
                    }
                } catch (e) {
                }
            }
        }
        return xmlHttpRequest;
    },
    ajax : function (param) {
    	var that = this;
    	var defaultParam = {
    			url :'',
    			method: 'post',
    			contentType: 'application/x-www-form-urlencoded',
    			accept: 'application/json',
    			error : function(res){
    				if(layer){
    					layer.closeAll();
    				}
    				if(res){
    					that.alert(res);
    				}
    			},
    			process : false
    	};

    	this.extend(param, defaultParam);
    	
    	if(param.process){
    		var wrap = document.querySelector(".wrap");
    		if(wrap){
    			wrap.style.display = "none";
    		
	    		var body = document.querySelector("body");
	    		if(body){
	    			var div = document.createElement("div");
	    			div.className = "process";
	    			body.appendChild(div);
	    		}
    		}
    		
    	}
    	
        var httpRequest = this.createAjax();
        var data = [];
        if (typeof param.data == "object") {
            for (var i in param.data) {
                data.push(i + "=" + param.data[i]);
            }
            data = data.join("&");
        } else {
            data = param.data;
        }
        if(param.method.toLowerCase() == "get"){
            param.url = this.appendQueryString(param.url, data);
        }

        httpRequest.open(param.method, param.url, true);
        httpRequest.setRequestHeader("Content-Type", param.contentType);
        httpRequest.setRequestHeader("Accept", param.accept);
        
        httpRequest.onreadystatechange = function () {
            if (httpRequest.readyState == 4 && httpRequest.status == 200) {
            	if(param.process){
            		var wrap = document.querySelector(".wrap");
            		if(wrap){
            			var body = document.getElementsByTagName("body");
            			var div = document.querySelector(".process");
			    		if(body && div){
			    			body = body[0];
			    			body.removeChild(div);
			    		}
            			
            			wrap.style.display = "block";
            			
            		}
            	}
            	
                var res = httpRequest.responseText;
                res = eval("(" + res + ")");
                if (param.success) {
                	if(res.errcode >= 0){
                        if(res.errcode == 99){
                            that.setCookie("access_token", "", "/", -1);
                            that.toast("请重新登陆");
                        }else if(res.errcode == 10){
                        	return;
                        }else{
                            param.success(res);
                        }
                	}else{
                		if(param.error){
                			param.error(res.message);
                		}
                		
                	}
                }
            } else {
            	if(param.error){
            		param.error();
            	}
            }
        }

        
        httpRequest.send(data);
        return httpRequest;
    },
    
    post : function (url,data,success,error, process) {
    	var param = {
    		url : window.HOST + url,
    		method: 'post',
    		data : data,
    		success : success
    	};
    	if(error){
    		param.error = error;
    	}
    	if(process){
    		param.process = process;
    	}
        return this.ajax(param);
    },
    get : function(url,data,success,error, process){
    	var param = {
        		url : window.HOST + url,
        		method: 'get',
        		data : data,
        		success : success
        	};
	    	if(error){
	    		param.error = error;
	    	}
	    	if(process){
	    		param.process = process;
	    	}
            return this.ajax(param);
    },
    appendQuery:function(url, name,value){
        var index = url.indexOf("?");
        if(index <0){
            return url + "?"+name + "=" + value;
        }else{
            var pre = url.substring(0,index);
            var res =  url.substring(index + 1);
            var param = res.split("&");
            var s = true;
            for(var i in param){
                var myName = param[i].split("=")[0];
                if(myName == name){
                    param[i] = name + "=" + value;
                    s= false;
                    break;
                }
            }
            if(s){
                param.push(name + "=" + value);
            }
            return pre + "?" + param.join("&");
        }
    },
    appendQueryString : function(url, params){
        if(!params){
            return url;
        }
        var index = url.indexOf("?");
        if(index <0){
            return url + "?" + params;
        }else{
            var pre = url.substring(0,index);
            var res =  url.substring(index + 1);
            var param = res.split("&");
            var params = params.split("&");
            this.extend(params, param);
            return pre + "?" + params.join("&");
        }
    },
    md5 : function(string){
        function md5_RotateLeft(lValue, iShiftBits) {
                return (lValue<<iShiftBits) | (lValue>>>(32-iShiftBits));
        }
        function md5_AddUnsigned(lX,lY){
                var lX4,lY4,lX8,lY8,lResult;
                lX8 = (lX & 0x80000000);
                lY8 = (lY & 0x80000000);
                lX4 = (lX & 0x40000000);
                lY4 = (lY & 0x40000000);
                lResult = (lX & 0x3FFFFFFF)+(lY & 0x3FFFFFFF);
                if (lX4 & lY4) {
                        return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
                }
                if (lX4 | lY4) {
                        if (lResult & 0x40000000) {
                                return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
                        } else {
                                return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
                        }
                } else {
                        return (lResult ^ lX8 ^ lY8);
                }
        }         
        function md5_F(x,y,z){
                return (x & y) | ((~x) & z);
        }
        function md5_G(x,y,z){
                return (x & z) | (y & (~z));
        }
        function md5_H(x,y,z){
                return (x ^ y ^ z);
        }
        function md5_I(x,y,z){
                return (y ^ (x | (~z)));
        }
        function md5_FF(a,b,c,d,x,s,ac){
                a = md5_AddUnsigned(a, md5_AddUnsigned(md5_AddUnsigned(md5_F(b, c, d), x), ac));
                return md5_AddUnsigned(md5_RotateLeft(a, s), b);
        }; 
        function md5_GG(a,b,c,d,x,s,ac){
                a = md5_AddUnsigned(a, md5_AddUnsigned(md5_AddUnsigned(md5_G(b, c, d), x), ac));
                return md5_AddUnsigned(md5_RotateLeft(a, s), b);
        };
        function md5_HH(a,b,c,d,x,s,ac){
                a = md5_AddUnsigned(a, md5_AddUnsigned(md5_AddUnsigned(md5_H(b, c, d), x), ac));
                return md5_AddUnsigned(md5_RotateLeft(a, s), b);
        }; 
        function md5_II(a,b,c,d,x,s,ac){
                a = md5_AddUnsigned(a, md5_AddUnsigned(md5_AddUnsigned(md5_I(b, c, d), x), ac));
                return md5_AddUnsigned(md5_RotateLeft(a, s), b);
        };
        function md5_ConvertToWordArray(string) {
                var lWordCount;
                var lMessageLength = string.length;
                var lNumberOfWords_temp1=lMessageLength + 8;
                var lNumberOfWords_temp2=(lNumberOfWords_temp1-(lNumberOfWords_temp1 % 64))/64;
                var lNumberOfWords = (lNumberOfWords_temp2+1)*16;
                var lWordArray=Array(lNumberOfWords-1);
                var lBytePosition = 0;
                var lByteCount = 0;
                while ( lByteCount < lMessageLength ) {
                        lWordCount = (lByteCount-(lByteCount % 4))/4;
                        lBytePosition = (lByteCount % 4)*8;
                        lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount)<<lBytePosition));
                        lByteCount++;
                }
                lWordCount = (lByteCount-(lByteCount % 4))/4;
                lBytePosition = (lByteCount % 4)*8;
                lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80<<lBytePosition);
                lWordArray[lNumberOfWords-2] = lMessageLength<<3;
                lWordArray[lNumberOfWords-1] = lMessageLength>>>29;
                return lWordArray;
        }; 
        function md5_WordToHex(lValue){
                var WordToHexValue="",WordToHexValue_temp="",lByte,lCount;
                for(lCount = 0;lCount<=3;lCount++){
                        lByte = (lValue>>>(lCount*8)) & 255;
                        WordToHexValue_temp = "0" + lByte.toString(16);
                        WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length-2,2);
                }
                return WordToHexValue;
        };
        function md5_Utf8Encode(string){
                string = string.replace(/\r\n/g,"\n");
                var utftext = ""; 
                for (var n = 0; n < string.length; n++) {
                        var c = string.charCodeAt(n); 
                        if (c < 128) {
                                utftext += String.fromCharCode(c);
                        }else if((c > 127) && (c < 2048)) {
                                utftext += String.fromCharCode((c >> 6) | 192);
                                utftext += String.fromCharCode((c & 63) | 128);
                        } else {
                                utftext += String.fromCharCode((c >> 12) | 224);
                                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                                utftext += String.fromCharCode((c & 63) | 128);
                        } 
                } 
                return utftext;
        }; 
        var x=Array();
        var k,AA,BB,CC,DD,a,b,c,d;
        var S11=7, S12=12, S13=17, S14=22;
        var S21=5, S22=9 , S23=14, S24=20;
        var S31=4, S32=11, S33=16, S34=23;
        var S41=6, S42=10, S43=15, S44=21;
        string = md5_Utf8Encode(string);
        x = md5_ConvertToWordArray(string); 
        a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476; 
        for (k=0;k<x.length;k+=16) {
                AA=a; BB=b; CC=c; DD=d;
                a=md5_FF(a,b,c,d,x[k+0], S11,0xD76AA478);
                d=md5_FF(d,a,b,c,x[k+1], S12,0xE8C7B756);
                c=md5_FF(c,d,a,b,x[k+2], S13,0x242070DB);
                b=md5_FF(b,c,d,a,x[k+3], S14,0xC1BDCEEE);
                a=md5_FF(a,b,c,d,x[k+4], S11,0xF57C0FAF);
                d=md5_FF(d,a,b,c,x[k+5], S12,0x4787C62A);
                c=md5_FF(c,d,a,b,x[k+6], S13,0xA8304613);
                b=md5_FF(b,c,d,a,x[k+7], S14,0xFD469501);
                a=md5_FF(a,b,c,d,x[k+8], S11,0x698098D8);
                d=md5_FF(d,a,b,c,x[k+9], S12,0x8B44F7AF);
                c=md5_FF(c,d,a,b,x[k+10],S13,0xFFFF5BB1);
                b=md5_FF(b,c,d,a,x[k+11],S14,0x895CD7BE);
                a=md5_FF(a,b,c,d,x[k+12],S11,0x6B901122);
                d=md5_FF(d,a,b,c,x[k+13],S12,0xFD987193);
                c=md5_FF(c,d,a,b,x[k+14],S13,0xA679438E);
                b=md5_FF(b,c,d,a,x[k+15],S14,0x49B40821);
                a=md5_GG(a,b,c,d,x[k+1], S21,0xF61E2562);
                d=md5_GG(d,a,b,c,x[k+6], S22,0xC040B340);
                c=md5_GG(c,d,a,b,x[k+11],S23,0x265E5A51);
                b=md5_GG(b,c,d,a,x[k+0], S24,0xE9B6C7AA);
                a=md5_GG(a,b,c,d,x[k+5], S21,0xD62F105D);
                d=md5_GG(d,a,b,c,x[k+10],S22,0x2441453);
                c=md5_GG(c,d,a,b,x[k+15],S23,0xD8A1E681);
                b=md5_GG(b,c,d,a,x[k+4], S24,0xE7D3FBC8);
                a=md5_GG(a,b,c,d,x[k+9], S21,0x21E1CDE6);
                d=md5_GG(d,a,b,c,x[k+14],S22,0xC33707D6);
                c=md5_GG(c,d,a,b,x[k+3], S23,0xF4D50D87);
                b=md5_GG(b,c,d,a,x[k+8], S24,0x455A14ED);
                a=md5_GG(a,b,c,d,x[k+13],S21,0xA9E3E905);
                d=md5_GG(d,a,b,c,x[k+2], S22,0xFCEFA3F8);
                c=md5_GG(c,d,a,b,x[k+7], S23,0x676F02D9);
                b=md5_GG(b,c,d,a,x[k+12],S24,0x8D2A4C8A);
                a=md5_HH(a,b,c,d,x[k+5], S31,0xFFFA3942);
                d=md5_HH(d,a,b,c,x[k+8], S32,0x8771F681);
                c=md5_HH(c,d,a,b,x[k+11],S33,0x6D9D6122);
                b=md5_HH(b,c,d,a,x[k+14],S34,0xFDE5380C);
                a=md5_HH(a,b,c,d,x[k+1], S31,0xA4BEEA44);
                d=md5_HH(d,a,b,c,x[k+4], S32,0x4BDECFA9);
                c=md5_HH(c,d,a,b,x[k+7], S33,0xF6BB4B60);
                b=md5_HH(b,c,d,a,x[k+10],S34,0xBEBFBC70);
                a=md5_HH(a,b,c,d,x[k+13],S31,0x289B7EC6);
                d=md5_HH(d,a,b,c,x[k+0], S32,0xEAA127FA);
                c=md5_HH(c,d,a,b,x[k+3], S33,0xD4EF3085);
                b=md5_HH(b,c,d,a,x[k+6], S34,0x4881D05);
                a=md5_HH(a,b,c,d,x[k+9], S31,0xD9D4D039);
                d=md5_HH(d,a,b,c,x[k+12],S32,0xE6DB99E5);
                c=md5_HH(c,d,a,b,x[k+15],S33,0x1FA27CF8);
                b=md5_HH(b,c,d,a,x[k+2], S34,0xC4AC5665);
                a=md5_II(a,b,c,d,x[k+0], S41,0xF4292244);
                d=md5_II(d,a,b,c,x[k+7], S42,0x432AFF97);
                c=md5_II(c,d,a,b,x[k+14],S43,0xAB9423A7);
                b=md5_II(b,c,d,a,x[k+5], S44,0xFC93A039);
                a=md5_II(a,b,c,d,x[k+12],S41,0x655B59C3);
                d=md5_II(d,a,b,c,x[k+3], S42,0x8F0CCC92);
                c=md5_II(c,d,a,b,x[k+10],S43,0xFFEFF47D);
                b=md5_II(b,c,d,a,x[k+1], S44,0x85845DD1);
                a=md5_II(a,b,c,d,x[k+8], S41,0x6FA87E4F);
                d=md5_II(d,a,b,c,x[k+15],S42,0xFE2CE6E0);
                c=md5_II(c,d,a,b,x[k+6], S43,0xA3014314);
                b=md5_II(b,c,d,a,x[k+13],S44,0x4E0811A1);
                a=md5_II(a,b,c,d,x[k+4], S41,0xF7537E82);
                d=md5_II(d,a,b,c,x[k+11],S42,0xBD3AF235);
                c=md5_II(c,d,a,b,x[k+2], S43,0x2AD7D2BB);
                b=md5_II(b,c,d,a,x[k+9], S44,0xEB86D391);
                a=md5_AddUnsigned(a,AA);
                b=md5_AddUnsigned(b,BB);
                c=md5_AddUnsigned(c,CC);
                d=md5_AddUnsigned(d,DD);
        }
		return (md5_WordToHex(a)+md5_WordToHex(b)+md5_WordToHex(c)+md5_WordToHex(d)).toLowerCase();
		}
}

  