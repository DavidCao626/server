var ipt;
var ser;
var bot;
var oul;
var callback = null;

var sealsData;
var seals = {};

/* 取li */
var lis = document.getElementsByTagName("li");

function setSeals(sealsData) {
	this.sealsData = sealsData;
	this.seals = sealsData.seals;
}

function deepCopy(source) {
	var result = {};
	for ( var key in source) {
		result[key] = typeof source[key] === 'object' ? deepCopy(source[key])
				: source[key];
	}
	return result;
}

function getSeal() {
	var keyData = deepCopy(sealsData);
	var seal = [];
	if (ipt !== null && ipt.value != null) {
		var j = 0;
		for ( var i in seals) {
			if (ipt.value == seals[i].signname) {
				seal[j] = seals[i];
				j++;
			}
		}
	}

	keyData.seals = seal;
	return keyData;
}

function bindDOM(iptid, serid, botid, oulid, cb) {
	ipt = document.getElementById(iptid);
	ser = document.getElementById(serid);
	bot = document.getElementById(botid);
	oul = document.getElementById(oulid);
	callback = cb;
	callback(sealsData);

	ipt.oninput = function() {
		var ss = ipt.value;
		addScript();
		var data = [];
		if (callback !== undefined && callback !== null  && sealsData !== undefined
				&& sealsData !== null && sealsData.seals !== undefined && sealsData.seals !== null) {
			var signseals = sealsData.seals;
			for (var i = 0, j = 0; i < signseals.length; ++i) {
				if (ss == "") {
					data[j] = signseals[i].signname;
					seals[signseals[i].signname] = signseals[i];
					j++;
				} else if (signseals[i].signname.match(ss)) {
					data[j] = signseals[i].signname;
					seals[signseals[i].signname] = signseals[i];
					j++;
				}
			}
		}
		queryList(data);
	}

	ipt.onfocus = function() {
		var ss = ipt.value;
		addScript();
		var data = [];
		if (callback !== undefined && callback !== null && sealsData !== undefined
				&& sealsData !== null && sealsData.seals !== undefined && sealsData.seals !== null) {
			var signseals = sealsData.seals;
			for (var i = 0, j = 0; i < signseals.length; ++i) {
				if (ss == "") {
					data[j] = signseals[i].signname;
					j++;
				} else if (signseals[i].signname.match(ss)) {
					data[j] = signseals[i].signname;
					j++;
				}
			}
		}
		queryList(data);

	}
}

function queryList(data) {
	ss = document.getElementsByTagName("script_search")[0];
	document.body.removeChild(ss)

	var arr = data;
	oul.innerHTML = "";
	if (arr.length == 0) {
		bot.style.display = "none";
	} else {
		bot.style.display = "block";
	}

	for (var i = 0; i < arr.length; i++) {
		var li = document.createElement("li");
		li.innerHTML = arr[i];
		li.onclick = function() {
			ipt.value = this.innerHTML;
			oul.innerHTML = "";
			bot.style.display = "none";
		}
		oul.appendChild(li);
	}
}

function addScript() {
	var s = document.createElement("script_search");
	document.body.appendChild(s);
}

/* 按键 */
var i = 0;

document.onkeydown = function(ev) {

	if (bot.style.display == "block") {
        var kcode = window.event.keyCode || ev.keyCode;
		if (kcode == 40) {
			for (var j = 0; j < lis.length; j++) {
				if (lis[j].className == "sel") {
					lis[j].className = "";
				}
			}

			if (i < lis.length) {
				lis[i].className = "sel";
				i++;
				if (i == lis.length) {
					i = 0;
				}
			}
		}

		if (kcode == 38) {
			m = 0
			for (; m < lis.length; m++) {
				if (lis[m].className == "sel") {
					lis[m].className = "";
					break;
				}
			}
			i = m;
			if (m > 0) {
				lis[m - 1].className = "sel";
			} else {
				lis[lis.length - 1].className = "sel";
			}
		}

		if (kcode == 13) {
			for (var n = 0; n < lis.length; n++) {
				if (lis[n].className == "sel") {
					ipt.value = lis[n].innerHTML;
				}
			}
			bot.style.display = "none";
		}
	} else {
		i = 0;
		m = 0;
	}
}