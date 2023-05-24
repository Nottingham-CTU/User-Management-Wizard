<?php

define( 'MODULE_USER_MANAGEMENT_WIZARD', true );

if ( ! $module->isAccessAllowed() )
{
	echo 'You do not have the rights to access this page.';
	exit;
}

// Determine user type: i = internal, e = external.
$userType = ( isset( $_GET['usertype'] ) && $_GET['usertype'] == 'i' ) ? 'i' : 'e';

$internalUserRegex = $module->getSystemSetting( 'internal-user-regex' );
$internalEmailRegex = $module->getSystemSetting( 'internal-email-regex' );

if ( ! empty( $_POST ) )
{
	if ( isset( $_POST['checkusername'] ) )
	{
		if ( ( $internalUserRegex != '' &&
		       preg_match( "/$internalUserRegex/", $_POST['checkusername'] ) ) ||
		     $module->query( 'SELECT 1 FROM redcap_user_information WHERE ' .
		                     'username = ?', [ $_POST['username'] ] )->num_rows > 0 )
		{
			echo 'false';
			exit;
		}
		echo 'true';
		exit;
	}

	if ( isset( $_POST['checkemail'] ) )
	{
		if ( $internalEmailRegex != '' &&
		     preg_match( "/$internalEmailRegex/", $_POST['checkemail'] ) )
		{
			echo 'false';
			exit;
		}
		echo 'true';
		exit;
	}

	if ( $_POST['firstname'] == '' || $_POST['lastname'] == '' || $_POST['username'] == '' ||
	     $_POST['email'] == '' ||
	     $module->query( 'SELECT 1 FROM redcap_user_information WHERE ' .
		                 'username = ?', [ $_POST['username'] ] )->num_rows > 0 ||
	     ( $internalUserRegex != '' &&
	       ( $userType == 'i' && !preg_match( "/$internalUserRegex/", $_POST['username'] ) ) ||
	       ( $userType == 'e' && preg_match( "/$internalUserRegex/", $_POST['username'] ) ) ) )
	{
		exit;
	}

	if ( $userType == 'i' ) // internal user
	{
		// If user not in the allowlist, add them.
		if ( $module->query( 'SELECT 1 FROM redcap_user_allowlist WHERE ' .
		                     'username = ?', [ $_POST['username'] ] )->num_rows == 0 )
		{
			$module->addUserToAllowlist( $_POST['username'] );
		}
		// Add the user profile.
		$module->addInternalUserProfile( $_POST['username'], $_POST['firstname'],
		                                 $_POST['lastname'], $_POST['email'] );
		// Email the user to notify them of account creation.
		$fromUser = $module->getSystemSetting( 'admin-user' );
		if ( $fromUser == '' ||
		     $module->query( 'SELECT 1 FROM redcap_user_information WHERE username = ?',
		                     [ $fromUser ] )->num_rows == 0 )
		{
			$fromUser = USERID;
		}
		$fromUser = $module->query( 'SELECT * FROM redcap_user_information WHERE username = ?',
		                            [ $fromUser ] );
		$fromUser = $fromUser->fetch_assoc();
		REDCap::email( $_POST['email'], $fromUser['user_email'],
		               'REDCap ' . $GLOBALS['lang']['control_center_101'],
		               '<html><body>' . $GLOBALS['lang']['global_21'] . '<br><br>' .
		               $GLOBALS['lang']['control_center_4488'] . ' &quot;<b>' . $_POST['username'] .
		               '</b>&quot;.<br><br><a href="' . APP_PATH_WEBROOT_FULL . '">' .
		               APP_PATH_WEBROOT_FULL . '</a></body></html>', '', '',
		               $fromUser['user_firstname'] . ' ' . $fromUser['user_lastname'] );
	}
	else // external user
	{
		// Add the user profile.
		$module->addExternalUserProfile( $_POST['username'], $_POST['firstname'],
		                                 $_POST['lastname'], $_POST['email'], $_POST['sponsor'] );
	}

	// Redirect to user projects.
	header( 'Location: ' . $module->getUrl( 'wizard_user_projects.php?username=' .
		                                    rawurlencode( $_POST['username'] ) ) );
	exit;
}

$HtmlPage = new HtmlPage();
$HtmlPage->PrintHeaderExt();

require_once APP_PATH_VIEWS . 'HomeTabs.php';


?>
<div style="height:70px"></div>
<h3>Create New User</h3>
<p>&nbsp;</p>
<div id="formContainer">
  <h4>User Details</h4>
  <div class="sectionDetails">
    <form method="post">
      <table>
        <tr>
          <td>First name:&nbsp; </td>
          <td><input type="text" name="firstname" size="50" required<?php
echo isset($_GET['firstname'])
     ? ( ' value="' . htmlspecialchars( ucfirst( $_GET['firstname'] ) ) . '"' ) : '';
?>></td>
        </tr>
        <tr>
          <td>Last name:&nbsp; </td>
          <td><input type="text" name="lastname" size="50" required<?php
echo isset($_GET['lastname'])
     ? ( ' value="' . htmlspecialchars( ucfirst( $_GET['lastname'] ) ) . '"' ) : '';
?>></td>
        </tr>
        <tr>
          <td>Username:&nbsp; </td>
          <td><input type="text" name="username" size="50" required<?php
echo isset($_GET['username']) ? ( ' value="' . htmlspecialchars( $_GET['username'] ) . '"' ) : '';
echo $userType == 'i' ? ' readonly' : ''; // username pre-entered if internal user
?>></td>
        </tr>
        <tr>
          <td>Email address:&nbsp; </td>
          <td><input type="text" name="email" size="50" required
               pattern="^(((?<=.)\.)?[A-Za-z0-9!#$%&'*+\/=?^_`|{}~-]+)+@([A-Za-z0-9-]+(\.(?=.))?)+$"<?php
echo isset($_GET['email']) ? ( ' value="' . htmlspecialchars( $_GET['email'] ) . '"' ) : '';
?>></td>
        </tr>
<?php

if ( SUPER_USER && $userType == 'e' /* external user */ )
{

?>
        <tr>
          <td>Account requested by:&nbsp; </td>
          <td><input type="text" name="sponsor" size="50"<?php
	if ( $internalUserRegex != '' )
	{
		echo ' pattern="' . $internalUserRegex . '"';
	}
?>></td>
        </tr>
<?php

}

?>
      </table>
      <input type="submit" value="Next" style="margin-top:12px">
    </form>
  </div>
</div>

<script type="text/javascript">
$(function()
{
<?php

if ( $userType == 'e' ) // external user
{

?>
  var vUsernameField = $('[name="username"]')
  vUsernameField.on( 'blur', function()
  {
    if ( this.value == '' )
    {
      this.setCustomValidity('')
    }
    else
    {
      $.post( '', { checkusername : this.value }, function( vResponse )
      {
        if ( vResponse == 'true' )
        {
          vUsernameField[0].setCustomValidity('')
        }
        else
        {
          vUsernameField[0].setCustomValidity('The supplied username cannot be used.')
        }
      })
    }
  })
  var vEmailField = $('[name="email"]')
  vEmailField.on( 'blur', function()
  {
    if ( this.value == '' )
    {
      this.setCustomValidity('')
    }
    else
    {
      $.post( '', { checkemail : this.value }, function( vResponse )
      {
        if ( vResponse == 'true' )
        {
          vEmailField[0].setCustomValidity('')
        }
        else
        {
          vEmailField[0].setCustomValidity('The supplied email address cannot be used.')
        }
      })
    }
  })
  var vNameFields = $('[name="firstname"], [name="lastname"]')
  vNameFields.on( 'blur', function()
  {
    var vFirstname = $('[name="firstname"]').val()
    var vLastname = $('[name="lastname"]').val()
    if ( vFirstname != '' && vLastname != '' && vUsernameField.val() == '' )
    {
      $('[name="username"]').val( (vFirstname.substring(0,1)+vLastname).toLowerCase() )
      vUsernameField.blur()
    }
  })
  vEmailField.blur()
  vNameFields.blur()
  $('[name="sponsor"]').autocomplete({
    source: app_path_webroot+"UserRights/search_user.php?searchEmail=1",
    minLength: 4,
    delay: 150,
    select: function( event, ui ) {
      $(this).val(ui.item.value);
      //$('#user_search_btn').click();
      return false;
    }
  })
  .data('ui-autocomplete')._renderItem = function( ul, item ) {
    return $("<li></li>")
      .data("item", item)
      .append("<a>"+item.label+"</a>")
      .appendTo(ul);
  }
<?php

}

?>
  var vForm = $('#formContainer')
  vForm.css( 'padding', '10px' )
  vForm.css( 'border', 'solid 1px #000000' )
  vForm.css( 'border-radius', '10px' )
  vForm.find('td').css( 'padding', '2px' )
})
</script>
<?php

$HtmlPage->PrintFooterExt();
