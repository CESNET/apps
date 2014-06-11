<link rel="stylesheet" type="text/css" href="../apps/user_saml/css/saml.css" />
<div id="clientsettings">
<form id="clientform" class="formLayout" action="#" method="post">
<fieldset class="personalblock">
	<h2><?php p($l->t('Access credentials for sync apps'));?></h2>
	<strong><?php p($l->t('Username'));?></strong><br></br>
	<input original-title="" id="user_name_login" name="user_name_login" value="<?php p($_['user_name_login']); ?>" type="text">
</fieldset>
</form>

<form id="clientpasswordform" class="formLayout" action="#" method="post">
<fieldset class="personalblock">
	<strong><?php p($l->t('Password'));?></strong>
	&ndash; <em><?php p($l->t('Set password for your sync app here'));?></em>
	<br></br>
	<input original-title="" id="client_pwd1" name="password" placeholder="<?php p($l->t('Password'));?>" type="password">
	<input original-title="" id="client_pwd2" name="confirm_password" placeholder="<?php p($l->t('Repeat password'));?>" type="password">
	<input original-title="" id="client_setpwd" value="<?php p($l->t('Set password'));?>" type="submit">
	<div id="cpasswordchanged"><?php p($l->t('Password successfully set!'));?></div>
	<div id="cpassworderror"></div>
	<span class="msg"></span>
	<input type="hidden" name="requesttoken" value="<?php print_unescaped($_['requesttoken']); ?>" id="requesttoken">
</fieldset>
</form>
</div>
