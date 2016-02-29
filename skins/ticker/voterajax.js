/* Adapted from VoteItUp */
/* A general script for updating the contents of the vote widget on the fly */
/*
USAGE:
vote (object to update with vote count, object to update with after vote text, post id, user id, base url)
*/

var xmlHttp;
var xmlHttpAgenda;
var xmlHttpSort;
var xmlHttpLinks;
var currentobj;
var votelinks;
var responseobj;
var sorttype;
var lastreftime;

//Useful for compatibility
function function_exists( function_name ) { 
    if (typeof function_name == 'string'){
        return (typeof window[function_name] == 'function');
    } else{
        return (function_name instanceof Function);
    }
}

//Javascript Function for JavaScript to communicate with Server-side scripts
function lg_AJAXrequest(scriptURL,type) {
	xmlHttp=zGetXmlHttpObject();
	if (xmlHttp==null)
	{
		alert ("Your browser does not support AJAX!");
		return;
	} 
    if (type == 1) {
		xmlHttp.open("POST",scriptURL,false);
		xmlHttp.onreadystatechange = zvoteChanged;
		xmlHttp.send(null);
	} else if (type == 2) {
		xmlHttp.open("POST",scriptURL,false);
		xmlHttp.send(null);
	} else if (type == 3) {
		xmlHttp.open("POST",scriptURL,false);
		xmlHttp.send(null);
		if (xmlHttp.status == 200)
		{
			suggested();
		}
	}
	delete xmlHttp;
}

function lg_AJAXagenda(scriptURL,time,ishome) {
	xmlHttpAgenda=zGetXmlHttpObject();
	if (xmlHttpAgenda==null)
	{
		alert ("Your browser does not support AJAX!");
		return;
	} 
	newURL = scriptURL+"?time="+time+"&ishome="+ishome;
	xmlHttpAgenda.open("GET",newURL,true);
	
	//Send the proper header information along with the request
	xmlHttpAgenda.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	
	document.getElementById('showotherinsts').style.display = 'none';
	document.getElementById('agendaloading').style.display = 'block';
	xmlHttpAgenda.onreadystatechange=zmostVoted;
	xmlHttpAgenda.send(null);
	delete xmlHttpAgenda;
}

function lg_AJAXvotelinks(baseURL,userID,postID,today,ishome) {
	var scripturl = baseURL+"/voteinfo.php?pid="+postID+"&uid="+userID+"&time="+today+"&ishome="+ishome;
	xmlHttpLinks=zGetXmlHttpObject();
	xmlHttpLinks.open("GET",scripturl,true);
	xmlHttpLinks.onreadystatechange=updateVoteLinks;
	xmlHttpLinks.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xmlHttpLinks.send(null);
	delete xmlHttpLinks;
}

function updateVoteLinks()
{
	if (xmlHttpLinks.readyState == 4)
	{ 
		votelinks_obj = document.getElementById(votelinks);
		votelinks_obj.innerHTML = xmlHttpLinks.responseText;
	}
}

function lg_AJAXsort(baseURL,query,ishome,type,page_uri) {
	if (type === undefined) sorttype = 0;
	if (sorttype == 0) {
		document.getElementById('sortarea1').style.display = 'none';
		document.getElementById('resorting').style.display = 'inline-block';
	}
	var scriptURL = baseURL + "/postloop.php?query="+query+"&ishome="+ishome+"&page_uri="+page_uri;
	xmlHttpSort=zGetXmlHttpObject();
	if (xmlHttpSort==null)
	{
		alert ("Your browser does not support AJAX!");
		return;
	} 
	xmlHttpSort.open("GET",scriptURL,true);
	
	//Send the proper header information along with the request
	xmlHttpSort.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	
	xmlHttpSort.onreadystatechange=sortMethodChanged;
	xmlHttpSort.send(null);
	delete xmlHttpSort;
}

function zGetXmlHttpObject()
{
var lxmlHttp=null;
try
  {
  // Firefox, Opera 8.0+, Safari
  lxmlHttp=new XMLHttpRequest();
  }
catch (e)
  {
  // Internet Explorer
  try
    {
    lxmlHttp=new ActiveXObject("Msxml2.XMLHTTP");
    }
  catch (e)
    {
    lxmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
    }
  }
return lxmlHttp;
}

function zvoteChanged() 
{ 
	//var votelinks_obj = document.getElementById(votelinks);
	var currentobj_obj = document.getElementById(currentobj);
	var votenumber = xmlHttp.responseText;
	currentobj_obj.innerHTML = votenumber;
}

function sortMethodChanged()
{
	if (xmlHttpSort.readyState == 3)
	{ 
		var d = new Date();
		var curtime = d.getTime();
		if (lastreftime === null) lastreftime = curtime;
		if (curtime < lastreftime + 500) return;
		lastreftime = curtime;
		var postlooptxt = xmlHttpSort.responseText;
		//if (postlooptxt.indexOf('New papers') == -1) return;
		var postloopobj = document.getElementById('postloop');

		postloopobj.innerHTML = postlooptxt;
		document.getElementById('sortarea1').style.display = 'none';
		document.getElementById('resorting').style.display = 'none';
		document.getElementById('loading').style.display = 'inline-block';
		toggleCat(false);
		disableElements(true);
		disableElements(false);
		return;
	}
	if (xmlHttpSort.readyState == 4)
	{ 
		var postlooptxt = xmlHttpSort.responseText;
		var postloopobj = document.getElementById('postloop');

		postloopobj.innerHTML = postlooptxt;
		toggleCat(false);
		if (sorttype == 0) {
			document.getElementById('resorting').style.display = 'none';
			document.getElementById('loading').style.display = 'none';
			document.getElementById('sortarea1').style.display = 'block';
		}

		MathJax.Hub.Configured();
	}
}

function suggested()
{
	var suggest_elem = document.getElementById(responseobj);
	var suggest_text = xmlHttp.responseText;
	
	suggest_elem.innerHTML = suggest_text;
	suggest_elem.style.display = 'inline-block';
}

function zmostVoted() 
{ 
	if (xmlHttpAgenda.readyState==4)
	{ 
		var mostvotedtxt = xmlHttpAgenda.responseText;
		
		var mostvotedobj = document.getElementById('mostvoted');
		document.getElementById('agendaloading').style.display = 'none';
		document.getElementById('showotherinsts').style.display = 'block';
		mostvotedobj.innerHTML = mostvotedtxt;
	}
}

function vote(obj, aftervote, postID ,userID, baseURL, today, ishome) {
	currentobj = obj;
	votelinks = 'votelinks'+postID;
	document.getElementById(votelinks).innerHTML = "<img src=\""+baseURL+"/../../themes/arclite/small_loading.gif\">&nbsp;Casting vote...";
	setTimeout(function(){
		var scripturl = baseURL+"/voteinterface.php?type=vote&tid=total&uid="+userID+"&pid="+postID+"&auth="+Math.random();
		lg_AJAXrequest(scripturl, 1);
		if (ishome == 1 && document.getElementById('mostvoted')) {
			var scripturl2 = baseURL+"/skins/ticker/mostvoted.php";
			lg_AJAXagenda(scripturl2, today, ishome);
		}
		lg_AJAXvotelinks(baseURL,userID,postID,today,ishome);
	}, 0);
}

function sink(obj, aftervote, postID ,userID, baseURL, today, ishome) {
	currentobj = obj;
	votelinks = 'votelinks'+postID;
	document.getElementById(votelinks).innerHTML = "<img src=\""+baseURL+"/../../themes/arclite/small_loading.gif\">&nbsp;Casting vote...";
	setTimeout(function(){
		var scripturl = baseURL+"/voteinterface.php?type=sink&tid=total&uid="+userID+"&pid="+postID+"&auth="+Math.random();
		lg_AJAXrequest(scripturl, 1);
		if (ishome == 1 && document.getElementById('mostvoted')) {
			var scripturl2 = baseURL+"/skins/ticker/mostvoted.php";
			lg_AJAXagenda(scripturl2, today, ishome);
		}
		lg_AJAXvotelinks(baseURL,userID,postID,today,ishome);
	}, 0);
}

function changedate(obj, aftervote, postID ,userID, baseURL, today, ishome) {
	currentobj = obj;
	votelinks = 'votelinks'+postID;
	dateselect = 'datesel'+postID;
	dateselobj = document.getElementById(dateselect);
	newdate = dateselobj.options[dateselobj.selectedIndex].value;
	document.getElementById(votelinks).innerHTML = "<img src=\""+baseURL+
		"/../../themes/arclite/small_loading.gif\">&nbsp;Changing discussion date...";
	setTimeout(function(){
		var scripturl = baseURL+"/voteinterface.php?type=date&tid=total&uid="+userID+"&pid="+postID+"&date="+newdate+"&auth="+Math.random();
		lg_AJAXrequest(scripturl, 1);
		if (ishome == 1 && document.getElementById('mostvoted')) {
			var scripturl2 = baseURL+"/skins/ticker/mostvoted.php";
			lg_AJAXagenda(scripturl2, today, ishome);
		}
		lg_AJAXvotelinks(baseURL,userID,postID,today,ishome);
	}, 0);
}

function discuss(postID, baseURL, today, ishome, refresh) {
	var scripturl = baseURL+"/voteinterface.php?type=discuss&pid="+postID+"&today="+today+"&auth="+Math.random();
	lg_AJAXrequest(scripturl, 2);
	if (refresh == 1) {
		document.location.reload(true);
	} else {
		var scripturl2 = baseURL+"/skins/ticker/mostvoted.php";
		lg_AJAXagenda(scripturl2, today, ishome);
	}
}

function suggest(obj, respobj, postID ,userID, baseURL) {
	currentobj = obj;
	responseobj = respobj;
	var sugg_elem = document.getElementById(currentobj);
	var sugg = sugg_elem.value;
	var scripturl = baseURL+"/voteinterface.php?type=suggest&uid="+userID+"&pid="+postID+"&sname="+sugg+"&auth="+Math.random();
	lg_AJAXrequest(scripturl, 3);
}

function bump(obj, aftervote, postID ,userID, baseURL) {
	currentobj = obj;
	votelinks = 'votelinks'+postID;
	document.getElementById(votelinks).innerHTML = "<img src=\""+baseURL+"/../../themes/arclite/small_loading.gif\">&nbsp;Bumping vote...";
	setTimeout(function(){
		var scripturl = baseURL+"/voteinterface.php?type=bump&tid=total&uid="+userID+"&pid="+postID+"&auth="+Math.random();
		lg_AJAXrequest(scripturl, 1);
	}, 0);
}

function present(obj, aftervote, postID, userID, baseURL, today, ishome) {
	currentobj = obj;
	votelinks = 'votelinks'+postID;
	document.getElementById(votelinks).innerHTML = "<img src=\""+baseURL+"/../../themes/arclite/small_loading.gif\">&nbsp;Committing...";
	setTimeout(function(){
		var scripturl = baseURL+"/voteinterface.php?type=present&tid=total&uid="+userID+"&pid="+postID+"&auth="+Math.random();
		lg_AJAXrequest(scripturl, 1);
		if (ishome == 1 && document.getElementById('mostvoted')) {
			var scripturl2 = baseURL+"/skins/ticker/mostvoted.php";
			lg_AJAXagenda(scripturl2, today, ishome);
		}
		lg_AJAXvotelinks(baseURL,userID,postID,today,ishome);
	}, 0);
}

function unvote(obj, aftervote, postID, userID, baseURL, today, ishome) {
	currentobj = obj;
	votelinks = 'votelinks'+postID;
	document.getElementById(votelinks).innerHTML = "<img src=\""+baseURL+"/../../themes/arclite/small_loading.gif\">&nbsp;Removing vote...";
	setTimeout(function(){
		var scripturl = baseURL+"/voteinterface.php?type=unvote&tid=total&uid="+userID+"&pid="+postID+"&auth="+Math.random();
		lg_AJAXrequest(scripturl, 1);
		if (ishome == 1 && document.getElementById('mostvoted')) {
			var scripturl2 = baseURL+"/skins/ticker/mostvoted.php";
			lg_AJAXagenda(scripturl2, today, ishome);
		}
		lg_AJAXvotelinks(baseURL,userID,postID,today,ishome);
	}, 0);
}
