/*
 * 	Identity switch RoundCube Bundle
 *
 *	@copyright	(c) 2024 Forian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/identity_switch/blob/master/LICENSE
 */

$(function() {
	$sw = $('#identity_switch_menu');
	isOk = false;

	switch (rcmail.env['skin']) {
	case 'larry':
		isOk = identity_switch_addCbLarry($sw);
		break;
			
	case 'classic':
		isOk = identity_switch_addCbClassic($sw);
		break;

    case 'elastic':
	case 'hivemail':
        isOk = identity_switch_addCbElastic($sw);
    
    default:
		break;
	}

	if (isOk)
        $sw.show();
});

// Catch all mouse clicks
$(document).click(function(event) { 
    
    // Check for left button
    if (event.button == 0) {
	    var id = event.target.id;
		var d = $('#identity_switch_dropdown'); 
	    if (id != 'identity_switch_menu' && !d.is(':hidden'))
			d.hide();
    }
});

// Plugin initialization
function identity_switch_init() {
    rcmail.addEventListener('plugin.identity_switch_notify', identity_switch_notify)
    	  .addEventListener('init', function() {
            // Bind to messages list select event, so favicon will be reverted on message preview too
            if (rcmail.message_list)
                rcmail.message_list.addEventListener('select', identity_switch_stop_notify);
    });
}

// Set menu position
function identity_switch_addCbLarry($sw) {
	var $truName = $('.topright .username');
	
	if ($truName.length > 0) {
		if ($sw.length > 0) {
			$sw.prependTo('#taskbar');
			$truName.hide();
			// Move our selection menu a bit to the right
			$('#identity_switch_menu').css('padding-top', '4px').css('padding-bottom', '4px');
			$('#identity_switch_dropdown').css('margin-left', '-92px');

			return true;
		}
	}

	return false;
}

// Set menu position
function identity_switch_addCbClassic($sw) {
	var $taskBar = $('#taskbar');
	
	if ($taskBar.length > 0) {
		$taskBar.prepend($sw);
		// Move our selection menu a bit to the right
		$('#identity_switch_menu').css('left', '-10px')
			.css('top', '-5px');
		$('#identity_switch_dropdown')
			.css('left', '190px')
			.css('top', '-40px');
			
		return true;
	}

	return false;
}

// Set menu position
function identity_switch_addCbElastic($sw) {
    var $taskBar = $('.header-title.username');
    
    $sw.css('background-color', 'transparent').css('padding','4px 0 0 2rem');
    if ($taskBar.length > 0) {
        $taskBar.prepend($sw);
        $taskBar.css('margin-left', '20px');

		// Remove text from <span>
	    var $node = $('.header-title.username');
	 
		var newNode = $('<' + $node[0].nodeName + '/>');
		$.each( $node[0].attributes, function ( i, attribute ) {
	        newNode.attr(attribute.name, attribute.value);
		});
	  	$node.children().each(function(){
	    	newNode.append(this);
	  	});
	  	$node.replaceWith(newNode);

		// Move our selection menu a bit to the bottom
		$('#identity_switch_menu')
			.css('height', '30px')
			.css('width', '180px');
		$('#identity_switch_dropdown')
			.css('left', '9px')
			.css('margin-top', '0');
		
        return true;
    }
 
    return false;
}

// Change userid in composer window to select proper identity
function identity_switch_fixIdent(iid) {
	if (parseInt(iid) > 0)
		$("#_from").val(iid);
}

// Open/close menu
function identity_switch_toggle_menu() {
	var d = $('#identity_switch_dropdown'); 

	if (d.is(':hidden')) {
		// reload window to show new mail counter in menu
		d.load(location.href + ' #identity_switch_dropdown > *', '');
		d.show();
		$('#messagelist-fixedcopy').css('z-index', 'auto');
	} else
		d.hide();
}

// Switch identity
function identity_switch_run(iid) {
	
    rcmail.env.unread_counts = {};
	rcmail.http_post('plugin.identity_switch_do', { 'identity_switch_iid': iid });
}

// Perform notification
function identity_switch_notify(ctl) {

	var autoplay = decodeURI(ctl[0].autoplay);
	var notification = decodeURI(ctl[0].notification);
	var title = decodeURI(ctl[0].title);
	
 	for (var i = 1; i < ctl.length; i++) {
		var e = $('#identity_switch_opt_' + ctl[i].iid);
		if (ctl[i].unseen == '0')
			e.text('');
		else
			e.text(ctl[i].unseen);

		if (ctl[i].basic !== undefined)
			identity_switch_basic();
		if (ctl[i].desktop !== undefined) 
			identity_switch_desktop(title, ctl[i].desktop.text, ctl[i].desktop.timeout, notification);
		if (ctl[i].sound !== undefined)
			identity_switch_sound(autoplay);
	}
}

// Stop notification
function identity_switch_stop_notify(prop)
{
    // Revert original favicon
    if (rcmail.env.favicon_href && rcmail.env.favicon_changed && (!prop || prop.action != 'check-recent')) {
        $('<link rel="shortcut icon" href="'+rcmail.env.favicon_href+'"/>').replaceAll('link[rel="shortcut icon"]');
        rcmail.env.favicon_changed = 0;
    }
}

// Browser notification: window.focus and favicon change
function identity_switch_basic()
{
    var w = rcmail.is_framed() ? window.parent : window;
    w.focus();

    var src = rcmail.assets_path('plugins/identity_switch/assets');

    // We cannot simply change a href attribute, we must to replace the link element (at least in FF)
	var link = $('<link rel="shortcut icon">').attr('href', src + '/alert.ico');
 	var olink = $('link[rel="shortcut icon"]', w.document);
    if (!rcmail.env.favicon_href)
        rcmail.env.favicon_href = olink.attr('href');

    rcmail.env.favicon_changed = 1;
    link.replaceAll(olink);
}

// Desktop notification
// - Require window.Notification API support (Chrome 22+ or Firefox 22+)
function identity_switch_desktop(title, msg, timeout, errmsg)
{
	if (!('Notification' in window) || window.Notification.permission !== "granted") {
		alert(decodeURIComponent(errmsg));
		window.Notification.requestPermission();
		return;
	}
		 
    var popup = new window.Notification(decodeURIComponent(title), {
                dir: "auto",
                lang: "",
                body: decodeURIComponent(msg),
                icon: rcmail.assets_path('plugins/identity_switch/assets/alert.gif')
            });
	popup.onclick = function() { this.close(); };
	setTimeout(function() { popup.close(); }, timeout * 1000);
}

// Sound notification
function identity_switch_sound(errmsg) {
    var src = rcmail.assets_path('plugins/identity_switch/assets/alert');

	if (!('Notification' in window) || window.Notification.silent) {
		alert(decodeURIComponent(errmsg));
		return;
	}
		 
	if (!('Navigator' in window) && window.Navigator.getAutoplayPolicy &&
		window.Navigator.getAutoplayPolicy('mediaelement') != 'allowed') {
		alert(decodeURIComponent(errmsg));
		window.Notification.requestPermission();
		return;
	}
	
    new Audio(src + '.mp3').play();
}