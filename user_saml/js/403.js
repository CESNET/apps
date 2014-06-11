(function() {
        var s403 = document.createElement('script');
        s403.type = 'text/javascript';
        (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(s403);
})();

$(document).ready(function(){
	$('<a class="primary" href="/">'+t('user_saml', 'Back')+'</a>').css({ 'padding':'10px' }).appendTo('ul');
});
