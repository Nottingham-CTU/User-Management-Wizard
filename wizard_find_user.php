<?php

define( 'MODULE_USER_MANAGEMENT_WIZARD', true );

if ( ! $module->isAccessAllowed() )
{
	echo 'You do not have the rights to access this page.';
	exit;
}

$internalUserRegex = $module->getSystemSetting( 'internal-user-regex' );
$showSearch = false;

if ( ! empty( $_POST ) )
{
	if ( isset( $_POST['project'] ) ) // project
	{
		if ( $_POST['project'] != '' && is_numeric( $_POST['project'] ) )
		{
			header( 'Location: ' . $module->getUrl( 'wizard_project_users.php?proj=' .
			                                        rawurlencode( $_POST['project'] ) ) );
		}
	}
	elseif ( isset( $_POST['username'] ) ) // internal user
	{
		// If user does not exist, redirect to create user.
		if ( $module->query( 'SELECT 1 FROM redcap_user_information WHERE ' .
		                     'username = ?', [ $_POST['username'] ] )->num_rows == 0 )
		{
			header( 'Location: ' . $module->getUrl( 'wizard_create_user.php?usertype=i&username=' .
			                                        rawurlencode( $_POST['username'] ) ) );
			exit;
		}
		// Otherwise redirect to user projects.
		header( 'Location: ' . $module->getUrl( 'wizard_user_projects.php?username=' .
		                                        rawurlencode( $_POST['username'] ) ) );
		exit;
	}
	else // external user
	{
		// Search database for user (exact match).
		$queryUser = $module->query( 'SELECT username FROM redcap_user_information ' .
		                             'WHERE user_firstname = ? AND user_lastname = ? ' .
		                             'AND user_email = ?',
		                             [ $_POST['firstname'], $_POST['lastname'], $_POST['email'] ] );
		// If precisely one match, redirect to user projects.
		if ( $queryUser->num_rows == 1 )
		{
			$infoUser = $queryUser->fetch_assoc();
			header( 'Location: ' . $module->getUrl( 'wizard_user_projects.php?username=' .
			                                        rawurlencode( $infoUser['username'] ) ) );
			exit;
		}
		// Otherwise search database for user (loose match).
		$queryUser = $module->query( 'SELECT username, user_firstname AS firstname, ' .
		                             'user_lastname AS lastname, user_email AS email ' .
		                             'FROM redcap_user_information ' .
		                             'WHERE user_firstname LIKE ? OR user_lastname LIKE ? ' .
		                             'OR user_email LIKE ? ORDER BY firstname, lastname',
		                             [ '%' . str_replace( [ '%', '_' ], [ '\\%', '\\_' ],
		                                                  $_POST['firstname'] ) . '%',
		                               '%' . str_replace( [ '%', '_' ], [ '\\%', '\\_' ],
		                                                  $_POST['lastname'] ) . '%',
		                               '%' . str_replace( [ '%', '_' ], [ '\\%', '\\_' ],
		                                                  $_POST['email'] ) . '%', ] );
		// If user search returns no records, redirect to create user.
		if ( $queryUser->num_rows == 0 )
		{
			header( 'Location: ' .
			        $module->getUrl( 'wizard_create_user.php?usertype=e' .
			                         '&firstname=' . rawurlencode( trim( $_POST['firstname'] ) ) .
			                         '&lastname=' . rawurlencode( trim( $_POST['lastname'] ) ) .
			                         '&email=' . rawurlencode( trim( $_POST['email'] ) ) ) );
			exit;
		}
		// Retrieve the records from the user search.
		$showSearch = true;
		$listUsers = [];
		while ( $infoUser = $queryUser->fetch_assoc() )
		{
			$listUsers[] = $infoUser;
		}
	}
}

if ( ! $showSearch )
{
	$listUserProjects = $module->getAccessibleProjects( USERID );
}

$HtmlPage = new HtmlPage();
$HtmlPage->PrintHeaderExt();

require_once APP_PATH_VIEWS . 'HomeTabs.php';


?>
<div style="height:70px"></div>
<?php

if ( $showSearch )
{

?>
<h3>Choose User</h3>
<p>&nbsp;</p>
<p>The following user accounts were found which fully or partially match the supplied details.</p>
<table class="mod-umw-table">
 <tr style="background:#ececec">
  <th>Username</th>
  <th>First name</th>
  <th>Last name</th>
  <th>Email address</th>
 </tr>
<?php
	foreach ( $listUsers as $infoUser )
	{
?>
 <tr class="mod-umw-trhover">
  <td>
   <a href=<?php echo '"', $module->getUrl( 'wizard_user_projects.php?username=' .
                                            rawurlencode( $infoUser['username'] ) ),
                      '">', htmlspecialchars( $infoUser['username'] ); ?></a>
  </td>
  <td><?php echo $module->searchHighlight( $infoUser['firstname'], $_POST['firstname'] ); ?></td>
  <td><?php echo $module->searchHighlight( $infoUser['lastname'], $_POST['lastname'] ); ?></td>
  <td><?php echo $module->searchHighlight( $infoUser['email'], $_POST['email'] ); ?></td>
 </tr>
<?php
	}
?>
</table>
<p>&nbsp;</p>
<p>
 <b>Can't find the user you're looking for?</b>
 <a href="<?php echo $module->getUrl( 'wizard_create_user.php?usertype=e' .
                                      '&firstname=' . rawurlencode( trim( $_POST['firstname'] ) ) .
                                      '&lastname=' . rawurlencode( trim( $_POST['lastname'] ) ) .
                                      '&email=' . rawurlencode( trim( $_POST['email'] ) ) );
?>">Create a new user account</a>
</p>
<p>&nbsp;</p>
<script type="text/javascript">
$(function()
{
  $('head').append('<style type="text/css">.mod-umw-table{width:100%;border:solid 1px #ccc}' +
                   '.mod-umw-table th,.mod-umw-table td{padding:3px}' +
                   '.mod-umw-trhover{background:#f7f7f7}' +
                   '.mod-umw-trhover:hover{background:#cdf}</style>')
})
</script>
<?php

}
else
{

?>
<h2><i class="fas fa-users"></i> User Management Wizard</h2>
<p>&nbsp;</p>
<h3>Enter User Details</h3>
<p>&nbsp;</p>
<p>Please choose the type of user:</p>
<p>&nbsp;</p>
<div id="sectionInternal">
  <h4>Internal User</h4>
  <div class="sectionDetails">
    <form method="post">
      Username: <input type="text" name="username" required<?php
	if ( $internalUserRegex != '' )
	{
		echo ' pattern="' . $internalUserRegex . '"';
	}
?>>
      <br>
      <input type="submit" value="Next">
    </form>
  </div>
</div>
<p>&nbsp;</p>
<div id="sectionExternal">
  <h4>External User</h4>
  <div class="sectionDetails">
    <form method="post">
      <table>
        <tr>
          <td>First name: </td>
          <td><input type="text" name="firstname" size="40" required></td>
        </tr>
        <tr>
          <td>Last name: </td>
          <td><input type="text" name="lastname" size="40" required></td>
        </tr>
        <tr>
          <td>Email address: </td>
          <td><input type="text" name="email" size="40" required
               pattern="^(((?<=.)\.)?[A-Za-z0-9!#$%&'*+\/=?^_`|{}~-]+)+@([A-Za-z0-9-]+(\.(?=.))?)+$"></td>
        </tr>
      </table>
      <input type="submit" value="Next">
    </form>
  </div>
</div>
<p>&nbsp;</p>
<p>&nbsp;</p>
<h3>Show Project Users</h3>
<p>&nbsp;</p>
<form method="post">
 <p>
 Select project:
  <select name="project">
   <option></option>
<?php
	foreach ( $listUserProjects as $projectID => $projectName )
	{
?>
   <option value="<?php echo $projectID; ?>"><?php echo htmlspecialchars( $projectName ); ?></option>
<?php
	}
?>
  </select>
  <input type="submit" value="Go">
 </p>
</form>
<p>&nbsp;</p>

<script type="text/javascript">
$(function()
{
  var vUserSections = $('#sectionInternal, #sectionExternal')
  vUserSections.find( 'div.sectionDetails' ).css( 'display', 'none' )
  vUserSections.css( 'cursor', 'pointer' )
  vUserSections.css( 'padding', '10px' )
  vUserSections.css( 'border', 'solid 1px #000000' )
  vUserSections.css( 'border-radius', '10px' )
  vUserSections.css( 'background', '#f7f7f7' )
  $('#sectionInternal').on( 'click', function()
  {
    var vSection = $('#sectionInternal')
    var vOtherSection = $('#sectionExternal')
    vSection.css( 'cursor', '' )
    vOtherSection.css( 'cursor', 'pointer' )
    vSection.find( 'div.sectionDetails' ).css( 'display', '' )
    vOtherSection.find( 'div.sectionDetails' ).css( 'display', 'none' )
  })
  $('#sectionExternal').on( 'click', function()
  {
    var vSection = $('#sectionExternal')
    var vOtherSection = $('#sectionInternal')
    vSection.css( 'cursor', '' )
    vOtherSection.css( 'cursor', 'pointer' )
    vSection.find( 'div.sectionDetails' ).css( 'display', '' )
    vOtherSection.find( 'div.sectionDetails' ).css( 'display', 'none' )
  })
})
</script>
<?php

}

$HtmlPage->PrintFooterExt();
