
<link rel="stylesheet" type="text/css" href="../apps/user_saml/css/saml.css" />

<form id="saml" action="#" method="post">
	<div id="samlSettings" class="personalblock">
    <strong><?php p($l->t('SAML Authentication backend'));?></strong>
	<ul>
		<li><a href="#samlSettings-1"><?php p($l->t('Basic'));?></a></li>
        <li><a href="#samlSettings-2"><?php p($l->t('Mapping'));?></a></li>
	</ul>
	<fieldset id="samlSettings-1">
	<?php foreach($_['tboxes1'] as $fld => $desc): ?>
		<p><label for="<?php p($l->t($fld)); ?>"><?php p($l->t($desc)); ?></label>
		<input type="text" id="<?php p($l->t($fld)); ?>"
		       name="<?php p($l->t($fld)); ?>" value="<?php p($_[$fld]); ?>" /></p>
	<?php endforeach; ?>
	<?php foreach($_['cboxes1'] as $fld => $desc): ?>
		<p><label for="<?php p($l->t($fld)); ?>"><?php p($l->t($desc)); ?></label>
		<input type="checkbox" id="<?php p($l->t($fld)); ?>"
		       name="<?php p($l->t($fld)); ?>"
			<?php p((($_[$fld] != false) ? 'checked="checked"' : '')); ?> /></p>
	<?php endforeach; ?>
	</fieldset>
	<fieldset id="samlSettings-2">
	<?php foreach($_['tboxes2'] as $fld => $desc): ?>
		<p><label for="<?php p($l->t($fld)); ?>"><?php p($l->t($desc)); ?></label>
		<input type="text" id="<?php p($l->t($fld)); ?>"
		       name="<?php p($l->t($fld)); ?>" value="<?php p($_[$fld]); ?>" /></p>
	<?php endforeach; ?>
	</fieldset>
	<input type="hidden" name="requesttoken" value="<?php echo $_['requesttoken'] ?>" id="requesttoken">
	<input type="submit" value="Save" />
	</div>

</form>
