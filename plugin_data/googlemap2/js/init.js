// add vml namespace for MSIE
var agent = navigator.userAgent.toLowerCase();
if (agent.indexOf("msie") != -1 && agent.indexOf("opera") == -1) {
	try {
	document.namespaces.add('v', 'schemas-microsoft-com:vml');
	document.createStyleSheet().addRule('v\:*', 'behavior: url(#default#VML);');
	} catch(e) {}
}

var googlemaps_maps = new Array();
var googlemaps_markers = new Array();
var googlemaps_icons = new Array();
var onloadfunc = new Array();

function PGMarker (point, icon, map, hidden, visible) {
	var marker = null;
	if (hidden == false) {
		if (icon == '') {
			marker = new GMarker(point);
		} else {
			marker = new GMarker(point, googlemaps_icons[icon]);
		}
		GEvent.addListener(marker, "click", function() { this.pukiwikigooglemaps.onclick(); });
		marker.pukiwikigooglemaps = this;
	}
	this.marker = marker;
	this.icon = icon;
	this.map = map;
	this.point = point;

	var _visible = false;
	var _html = null;
	var _zoom = null;
	var _type = null;

	this.setHtml = function(h) {_html = h;}
	this.setZoom = function(z) {_zoom = z;}
	this.setMapType = function(t) {_type = t;}
	this.getHtml = function() {return _html;}
	this.getZoom = function() {return _zoom;}
	
	this.onclick = function () {
		var map = googlemaps_maps[this.map];
		//現在のズームレベル
		//var oz = map.getZoom();
		//移動先と現在地が収まるズームレベル
		//var tz = map.getBoundsZoomLevel(new GLatLngBounds(map.getCenter(), this.point));
		//map.setZoom(tz);
		
		map.panTo(this.point);
		
		if (_type != null) {
			map.setMapType(_type);
		}
		if (_zoom != null) {
			map.setZoom(_zoom);
		}
		
		if (_type != null || _zoom != null){map.panTo(this.point);}
		
		if (_html != null) {
			if (this.marker != null)
				this.marker.openInfoWindowHtml(_html);
		}
	}

	this.show = function () {
		if (_visible) return;
		if (this.marker != null)
			googlemaps_maps[this.map].addOverlay(this.marker);
		_visible = true;
	}

	this.hide = function () {
		if (!_visible) return;
		if (this.marker != null)
			googlemaps_maps[this.map].removeOverlay(this.marker);
		_visible = false;
	}

	if (visible) { this.show(); }
	return this;
}


var PGTool = new function () {
	this.fmtNum = function (x) {
		var n = x.toString().split(".");
		n[1] = (n[1] + "000000").substr(0, 6);
		return n.join(".");
	}
	this.getLatLng = function (x, y, api) {
		switch (api) {
			case 0:
				x = x - y * 0.000046038 - x * 0.000083043 + 0.010040;
				y = y - y * 0.00010695  + x * 0.000017464 + 0.00460170;
			case 1:
				t = x;
				x = y;
				y = t;
				break;
		}
		return new GLatLng(x, y);
	}
	this.getXYPoint = function (x, y, api) {
		if (api < 2) {
			t = x;
			x = y;
			y = t;
		}
		if (api == 0) {
			nx = 1.000083049 * x + 0.00004604674815 * y - 0.01004104571;
			ny = 1.000106961 * y - 0.00001746586797 * x - 0.004602192204;
			x = nx;
			y = ny;
		}
		return {x:x, y:y};
	}
}

var PGDraw = new function () {
	var self = this;
	this.weight = 10;
	this.opacity = 0.5;
	this.color = "#00FF00";

	this.line = function (plist) {
		return new GPolyline(plist, this.color, this.weight, this.opacity);
	}
	
	this.rectangle = function (p1, p2) {
		var points = new Array (
			p1,
			new GLatLng(p1.lat(), p2.lng()),
			p2,
			new GLatLng(p2.lat(), p1.lng()),
			p1
		);
		return new GPolyline(points, this.color, this.weight, this.opacity);
	}
	
	this.circle  = function (point, radius) {
		return draw_ngon(point, radius, 36, 0, 360);
	}
	
	this.arc = function (point, radius, st, ed) {
		while (st > ed) { ed += 360; }
		return draw_ngon(point, radius, 36, st, ed);
	}

	this.ngon = function (point, radius, n, rotate) {
		if (n < 3) return null;
		return draw_ngon(point, radius, n, rotate, rotate+360);
	}

	function draw_ngon (point, radius, div, st, ed) {
		var incr = (ed - st) / div;
		var lat = point.lat();
		var lng = point.lng();
		var plist = new Array();
		var rad = 0.017453292519943295; /* Math.PI/180.0 */
		var en = 0.00903576399827824;   /* 1/(6341km * rad) */
		var clat = radius * en; 
		var clng = clat/Math.cos(lat * rad);
		for (var i = st ; i <= ed; i+=incr) {
			if (i+incr > ed) {i=ed;}
			var x = lat + clat * Math.sin(i * rad);
			var y = lng + clng * Math.cos(i * rad);
			plist.push(new GLatLng(x, y));
		}
		return new GPolyline(plist, self.color, self.weight, self.opacity);
	}
}

function p_googlemaps_marker_toggle (mapname, check, name) {
	for (key in googlemaps_markers) {
		var m = googlemaps_markers[key];
		if (m.map != mapname) continue;
		if (m.icon == name) {
			if (check.checked) {
				m.show();
			} else {
				m.hide();
			}
		}
	}
}


function p_googlemaps_togglemarker_checkbox (mapname, undefname) {
	var icons = {};
	for (key in googlemaps_markers) {
		var map = googlemaps_markers[key].map;
		var icon = googlemaps_markers[key].icon;
		if (map != mapname) {continue;}
		icons[icon] = 1;
	}
	var iconlist = new Array();
	for (n in icons) {
		iconlist.push(n);
	}
	iconlist.sort();

	var r = document.createElement("div");
	var map = document.getElementById(mapname);
	map.parentNode.insertBefore(r, map.nextSibling);

	for (i in iconlist) {
		var name = iconlist[i];
		if (typeof(name) != "string" && !(name instanceof String)) {continue;}
		var id = "ti_" + mapname + "_" + name;
		var input = document.createElement("input");
		var label = document.createElement("label");
		input.setAttribute("type", "checkbox");
		input.id = id;
		label.htmlFor = id;
		if (name == "") {
		label.appendChild(document.createTextNode(undefname));
		} else {
		label.appendChild(document.createTextNode(name));
		}
		eval("input.onclick = function(){p_googlemaps_marker_toggle('"+mapname+"', this, '"+name+"');}");

		r.appendChild(input);
		r.appendChild(label);
		input.setAttribute("checked", "checked");
	}
}
// Add function in 'window.onload' event.
void function()
{
	var func = window.onload;
	window.onload = function () {
		if (GBrowserIsCompatible()) {
			while (onloadfunc.length > 0) {
				onloadfunc.shift()();
			}
		}
		if (func) func();
	}
}();
void function()
{
	var func = window.onunload;
	window.onunload = function () {
		if (func) func();
		GUnload();
	}
}();
