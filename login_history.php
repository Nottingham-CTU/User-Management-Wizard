<?php

define( 'MODULE_USER_MANAGEMENT_WIZARD', true );

$loginHistoryVisibility = $module->getSystemSetting( 'login-history-visibility' );

// Prohibit access to this page if the user is not allowed to access the wizard.
if ( ! $module->isAccessAllowed() || ( SUPER_USER != 1 && $loginHistoryVisibility == 'A' ) )
{
	echo 'You do not have the rights to access this page.';
	exit;
}


$queryLoginHistory =
	$module->query( "SELECT `user`, ts, if(`event` = 'LOGIN_SUCCESS', 1, " .
	                "if(`event` = 'LOGIN_FAIL', 0, 2)) success, ifnull((SELECT 1 " .
	                "FROM redcap_auth WHERE username = `user` LIMIT 1), 0) tblusr, " .
	                "ip, browser_name, browser_version FROM redcap_log_view lv " .
	                "WHERE (`event` IN ('LOGIN_SUCCESS','LOGIN_FAIL') OR (page = " .
	                "'Authentication/two_factor_verify_code.php' AND ts NOT IN (SELECT ts FROM " .
	                "redcap_log_view WHERE `event` = 'LOGIN_SUCCESS' AND `user` = lv.user ))) " .
	                ( SUPER_USER == 1 || $loginHistoryVisibility != 'P' ? '' :
	                  "AND ( `user` = '[not_valid_username]' OR `user` IN ( SELECT DISTINCT " .
	                  "username FROM redcap_user_rights WHERE project_id IN (SELECT DISTINCT " .
	                  "project_id FROM redcap_user_rights WHERE username = ?) ) ) " ) .
	                "ORDER BY ts DESC LIMIT 50",
	                SUPER_USER == 1 || $loginHistoryVisibility != 'P' ? [] : [ USERID ] );
$listLoginHistory = [];
while( $res = $queryLoginHistory->fetch_assoc() )
{
	$listLoginHistory[] = $res;
}

$authMethod = $module->query( 'SELECT `value` FROM redcap_config WHERE field_name = ?',
                              ['auth_meth_global'] )->fetch_assoc()['value'];
$hasAllowlist = $module->query( 'SELECT `value` FROM redcap_config WHERE field_name = ?',
                              ['enable_user_allowlist'] )->fetch_assoc()['value'] == '1';
$showInternal = ( $hasAllowlist && ! in_array( $authMethod, [ 'none', 'table' ] ) );
$showExternal = ( $authMethod == 'table' || substr( $authMethod, -6 ) == '_table' );

$HtmlPage = new HtmlPage();
$HtmlPage->PrintHeaderExt();

require_once APP_PATH_VIEWS . 'HomeTabs.php';

?>
<div style="height:70px"></div>
<h3>REDCap Login History</h3>
<table class="logintable">
 <tr>
  <th style="width:20%">Username</th>
  <th style="width:20%">User type</th>
  <th style="width:25%">Login date/time</th>
  <th style="width:15%">Login successful</th>
  <th style="width:20%">Web browser</th>
 </tr>
<?php
foreach ( $listLoginHistory as $infoLoginHistory )
{
	$username = $module->escapeHTML( $infoLoginHistory['user'] );
	if ( $infoLoginHistory['user'] != '[not_valid_username]' )
	{
		$username = '<a href="' .
		            $module->escapeHTML($module->getUrl( 'wizard_user_projects.php?username=' .
		                                             rawurlencode( $infoLoginHistory['user'] ) ) ) .
		            '">' . $username . '</a>';
	}
	$userType = '';
	if ( $showInternal && $showExternal && $infoLoginHistory['user'] != '[not_valid_username]' )
	{
		if ( $infoLoginHistory['tblusr'] )
		{
			$userType = $module->escapeHTML( $module->getSystemSetting('external-user-heading') )
			            ?: 'External User';
		}
		else
		{
			$userType = $module->escapeHTML( $module->getSystemSetting('internal-user-heading') )
			            ?: 'Internal User';
		}
	}
	$browser = '';
	if ( $infoLoginHistory['browser_name'] != 'unknown' )
	{
		$browser = $infoLoginHistory['browser_name'];
		if ( $infoLoginHistory['browser_version'] != 'unknown' )
		{
			$browser .= ' ' . $infoLoginHistory['browser_version'];
		}
	}
	$background = $infoLoginHistory['success'] == 1 ? '#ddffee' : '#ffeeee';
	$successText = $infoLoginHistory['success'] == 2 ? ' (invalid 2FA)' : '';
	$successText = $infoLoginHistory['success'] == 1 ? 'Yes' : ( '<b>No' . $successText . '</b>' );
?>
 <tr style="background:<?php echo $background; ?>">
  <td><b><?php echo $username; ?></b></td>
  <td><?php echo $userType; ?></td>
  <td><?php echo date( 'd M Y H:i:s', strtotime( $infoLoginHistory['ts'] ) ); ?></td>
  <td><?php echo $successText; ?></td>
  <td><?php echo $module->escapeHTML( ucfirst( $browser ) ); ?></td>
 </tr>
<?php
}
?>
</table>
<p>&nbsp;</p>
<script type="text/javascript">
$('head').append('<style type="text/css">.logintable {width:100%} .logintable th, ' +
                 '.logintable td {border:solid 1px #000;padding:3px} .logintable tr:hover td ' +
                 '{background:#ffffdd !important} #pagecontainer {max-width:1000px}</style>')
</script>
<?php


$HtmlPage->PrintFooterExt();
