// Ajax communications
	function getWithAjax(target, callback) {
		var xmlhttp;
		if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp=new XMLHttpRequest();
		}

		else {// code for IE6, IE5
			xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
		}

		xmlhttp.onreadystatechange=function() {
			if (xmlhttp.readyState==4) {
				if (xmlhttp.status == 200) {
					callback(xmlhttp.responseText, xmlhttp.status);
				} else if (xmlhttp.status == 401 || xmlhttp.status == 302) {
					window.location.replace("https://www.youtold.me/login.php");
				} else {
					callback(xmlhttp.responseText, xmlhttp.status);
				}
			}
		}
		xmlhttp.open("GET",target,true);
		xmlhttp.overrideMimeType('text/plain; charset=x-user-defined');
		xmlhttp.send();
	}

	function postWithAjax(target, message,  callback) {
		var xmlhttp;
		
		if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp=new XMLHttpRequest();
		}
		else {// code for IE6, IE5
			xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
		}
		
		xmlhttp.open("POST", target, true);
		
		xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		
		xmlhttp.onreadystatechange=function() {
			if (xmlhttp.readyState==4) {
                                if (xmlhttp.status == 200) {
                                        callback(xmlhttp.responseText, xmlhttp.status);
                                } else if (xmlhttp.status == 401 || xmlhttp.status == 302) {
                                        window.location.replace("https://www.youtold.me/login.php");
                                } else {
                                        callback(xmlhttp.responseText, xmlhttp.status);
                                }
                        }
		}
		xmlhttp.send(message);
	}
