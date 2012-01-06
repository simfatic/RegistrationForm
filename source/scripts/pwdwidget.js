/* 
*
* Password Widget 1.0
*
* This script is distributed under the GNU Lesser General Public License.
* Read the entire license text here: http://www.gnu.org/licenses/lgpl.html
*
* Copyright (C) 2009 HTML Form Guide 
* http://www.html-form-guide.com/
*/

function PasswordWidget(divid,pwdname)
{
	this.maindivobj = document.getElementById(divid);
	this.pwdobjname = pwdname;

	this.MakePWDWidget=_MakePWDWidget;

	this.showing_pwd=1;
	this.txtShow = 'Show';
	this.txtMask = 'Mask';
	this.txtGenerate = 'Generate';
	this.txtWeak='weak';
	this.txtMedium='medium';
	this.txtGood='good';

	this.enableShowMask=true;
	this.enableGenerate=true;
	this.enableShowStrength=true;
	this.enableShowStrengthStr=true;

}

function _MakePWDWidget()
{
	var code="";
    var pwdname = this.pwdobjname;

	this.pwdfieldid = pwdname+"_id";

	code += "<input type='password' class='pwdfield' name='"+pwdname+"' id='"+this.pwdfieldid+"'>";

	this.pwdtxtfield=pwdname+"_text";

	this.pwdtxtfieldid = this.pwdtxtfield+"_id";

	code += "<input type='text' class='pwdfield' name='"+this.pwdtxtfield+"' id='"+this.pwdtxtfieldid+"' style='display: none;'>";

	this.pwdshowdiv = pwdname+"_showdiv";

	this.pwdshow_anch = pwdname + "_show_anch";

	code += "<div class='pwdopsdiv' id='"+this.pwdshowdiv+"'><a href='#' id='"+this.pwdshow_anch+"'>"+this.txtShow+"</a></div>";

	this.pwdgendiv = pwdname+"_gendiv";

	this.pwdgenerate_anch = pwdname + "_gen_anch";

	code += "<div class='pwdopsdiv'id='"+this.pwdgendiv+"'><a href='#' id='"+this.pwdgenerate_anch+"'>"+this.txtGenerate+"</a></div>";

	this.pwdstrengthdiv = pwdname + "_strength_div";

	code += "<div class='pwdstrength' id='"+this.pwdstrengthdiv+"'>";

	this.pwdstrengthbar = pwdname + "_strength_bar";

	code += "<div class='pwdstrengthbar' id='"+this.pwdstrengthbar+"'></div>";

	this.pwdstrengthstr = pwdname + "_strength_str";

	code += "<div class='pwdstrengthstr' id='"+this.pwdstrengthstr+"'></div>";

	code += "</div>";

	this.maindivobj.innerHTML = code;

	this.pwdfieldobj = document.getElementById(this.pwdfieldid);
	
	this.pwdfieldobj.pwdwidget=this;

	this.pwdstrengthbar_obj = document.getElementById(this.pwdstrengthbar);
	
	this.pwdstrengthstr_obj = document.getElementById(this.pwdstrengthstr);

	this._showPasswordStrength = passwordStrength;

	this.pwdfieldobj.onkeyup=function(){ this.pwdwidget._onKeyUpPwdFields(); }

	this._showGeneatedPwd = showGeneatedPwd;

	this.generate_anch_obj = document.getElementById(this.pwdgenerate_anch);
	
	this.generate_anch_obj.pwdwidget=this;

	this.generate_anch_obj.onclick = function(){ this.pwdwidget._showGeneatedPwd(); }

	this._showpwdchars = showpwdchars;

	this.show_anch_obj = document.getElementById(this.pwdshow_anch);

	this.show_anch_obj.pwdwidget = this;

	this.show_anch_obj.onclick = function(){ this.pwdwidget._showpwdchars();}

	this.pwdtxtfield_obj = document.getElementById(this.pwdtxtfieldid);

	this.pwdtxtfield_obj.pwdwidget=this;

	this.pwdtxtfield_obj.onkeyup=function(){ this.pwdwidget._onKeyUpPwdFields(); }
	

	this._updatePwdFieldValues = updatePwdFieldValues;

	this._onKeyUpPwdFields=onKeyUpPwdFields;

	if(!this.enableShowMask)
	{ document.getElementById(this.pwdshowdiv).style.display='none';}

	if(!this.enableGenerate)
	{ document.getElementById(this.pwdgendiv).style.display='none';}

	if(!this.enableShowStrength)
	{ document.getElementById(this.pwdstrengthdiv).style.display='none';}

	if(!this.enableShowStrengthStr)
	{ document.getElementById(this.pwdstrengthstr).style.display='none';}
}

function onKeyUpPwdFields()
{
	this._updatePwdFieldValues(); 
	this._showPasswordStrength();
}

function updatePwdFieldValues()
{
	if(1 == this.showing_pwd)
	{
		this.pwdtxtfield_obj.value = this.pwdfieldobj.value;	
	}
	else
	{
		this.pwdfieldobj.value = this.pwdtxtfield_obj.value;
	}
}

function showpwdchars()
{
	var innerText='';
	var pwdfield = this.pwdfieldobj;
	var pwdtxt = this.pwdtxtfield_obj;
	var field;
	if(1 == this.showing_pwd)
	{
		this.showing_pwd=0;
		innerText = this.txtMask;

		pwdtxt.value = pwdfield.value;
		pwdfield.style.display='none';
		pwdtxt.style.display='';
		pwdtxt.focus();
	}
	else
	{
		this.showing_pwd=1;
		innerText = this.txtShow;	
		pwdfield.value = pwdtxt.value;
		pwdtxt.style.display='none';
		pwdfield.style.display='';
		pwdfield.focus();
			
	}
	this.show_anch_obj.innerHTML = innerText;

}

function passwordStrength()
{
	var colors = new Array();
	colors[0] = "#cccccc";
	colors[1] = "#ff0000";
	colors[2] = "#ff5f5f";
	colors[3] = "#56e500";
	colors[4] = "#4dcd00";
	colors[5] = "#399800";

	var pwdfield = this.pwdfieldobj;
	var password = pwdfield.value

	var score   = 0;

	if (password.length > 6) {score++;}

	if ( ( password.match(/[a-z]/) ) && 
	     ( password.match(/[A-Z]/) ) ) {score++;}

	if (password.match(/\d+/)){ score++;}

	if ( password.match(/[^a-z\d]+/) )	{score++};

	if (password.length > 12){ score++;}
	
	var color=colors[score];
	var strengthdiv = this.pwdstrengthbar_obj;
	
	strengthdiv.style.background=colors[score];
	
	if (password.length <= 0)
	{ 
		strengthdiv.style.width=0; 
	}
	else
	{
		strengthdiv.style.width=(score+1)*10+'px';
	}

	var desc='';
	if(password.length < 1){desc='';}
	else if(score<3){ desc = this.txtWeak; }
	else if(score<4){ desc = this.txtMedium; }
	else if(score>=4){ desc= this.txtGood; }

	var strengthstrdiv = this.pwdstrengthstr_obj;
	strengthstrdiv.innerHTML = desc;
}

function getRand(max) 
{
	return (Math.floor(Math.random() * max));
}

function shuffleString(mystr)
{
	var arrPwd=mystr.split('');

	for(i=0;i< mystr.length;i++)
	{
		var r1= i;
		var r2=getRand(mystr.length);

		var tmp = arrPwd[r1];
		arrPwd[r1] = arrPwd[r2];
		arrPwd[r2] = tmp;
	}

	return arrPwd.join("");
}

function showGeneatedPwd()
{
	var pwd = generatePWD();
	this.pwdfieldobj.value= pwd;
	this.pwdtxtfield_obj.value =pwd;

	this._showPasswordStrength();
}

function generatePWD()
{
    var maxAlpha = 26;
	var strSymbols="~!@#$%^&*(){}?><`=-|][";
	var password='';
	for(i=0;i<3;i++)
	{
		password += String.fromCharCode("a".charCodeAt(0) + getRand(maxAlpha));
	}
	for(i=0;i<3;i++)
	{
		password += String.fromCharCode("A".charCodeAt(0) + getRand(maxAlpha));
	}
	for(i=0;i<3;i++)
	{
		password += String.fromCharCode("0".charCodeAt(0) + getRand(10));
	}
	for(i=0;i<4;i++)
	{
		password += strSymbols.charAt(getRand(strSymbols.length));
	}

	password = shuffleString(password);
	password = shuffleString(password);
	password = shuffleString(password);

	return password;
}