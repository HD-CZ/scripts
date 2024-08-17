<?php 
//Tutorials:
// http://jecas.cz/pdo

if($_GET['hash'] != "wali32jd89sp6sxw")
    throw new Exception("Error:Incorrect security hash!", 1); 

// Připojovací údaje
define('SQL_HOST', 'localhost');
define('SQL_DBNAME', 'hd_main');
define('SQL_USERNAME', 'hd_main');
define('SQL_PASSWORD', '1^qsyF36');

$dsn = 'mysql:dbname=' . SQL_DBNAME . ';host=' . SQL_HOST . '';
$user = SQL_USERNAME;
$password = SQL_PASSWORD;

try 
{
	$pdo = new PDO($dsn, $user, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	// echo "connected<br>";
}
catch (PDOException $e) 
{
	die('Connection failed: ' . $e->getMessage());
} 


$inputEmail = isset($_GET['userEmail']) ? $_GET['userEmail'] : null;//'jindrichsiruce.k@gmail.com';
$metaValue = isset($_GET['data']) ? $_GET['data'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null; //setData getData updateData getMSDataStructure

switch ($action) {
    case "get":
        $vysledek = getUserCmsMemberObject($inputEmail);
        break;
    case "set":
        $vysledek = setUserCmsMemberObject($inputEmail, $metaValue);
        break;
    case "getUserId":
        $vysledek = getUserId($inputEmail);
        break;
    case "getMSDataStructure":
        return getMSDataStructure();
        break;
    default:
        throw new Exception("Error:UnknownAction",1);
}
	

// $vysledek = readUserCmsMemberMeta($inputEmail);
// $vysledek = insertCmsMemberMetaValue($inputEmail, $metaValue);
// $vysledek = updateCmsMemberMetaValue($inputEmail, $metaValue);
$responseObj = new \stdClass();
$responseObj->email = $inputEmail;
$responseObj->inputData = $metaValue;
$responseObj->action = $action;
$responseObj->result = $vysledek;

echo json_encode($responseObj);
return;

// echo $vysledek;
// echo '<pre>'; print_r($metaData); echo '</pre>';


// testing:
//http://humandesign.cz/scripts/userMetaCMSUpdate.php?userEmail=jindrichsi.r.ucek@gmail.com&hash=wali32jd89sp6sxw&action=set&data=d

function getUserId($userEmail)
{
    $dotaz = $GLOBALS['pdo']->prepare("SELECT ID FROM wh21478_users WHERE user_email = ?");
  // Vykonání dotazu
    $dotaz->execute(array(
    	$userEmail
  ));
  $vysledek = $dotaz->fetchAll();
    // echo '<pre>'; print_r($vysledek); echo '</pre>';
	if(isset($vysledek[0][0]))
      return $vysledek[0][0];
}


function getUserCmsMemberObject($userEmail)
{
    $dotaz = $GLOBALS['pdo']->prepare("SELECT meta_value FROM wh21478_usermeta WHERE user_id = (SELECT ID FROM wh21478_users WHERE user_email = ?) AND meta_key = 'cms_member'");
  // Vykonání dotazu
    $dotaz->execute(array(
    	$userEmail
  ));
  $vysledek = $dotaz->fetchAll();
  // $jsonResult = json_encode(unserialize($vysledek[0][0])); 
  // echo '<pre>'; print_r(unserialize($vysledek[0][0])); echo '</pre>';
  // echo '<pre>'; print_r($jsonResult); echo '</pre>';
   
     if(isset($vysledek[0][0]))
      return json_encode(unserialize($vysledek[0][0]), JSON_FORCE_OBJECT);
     else
      if(getUserId($userEmail))
        return ""; //no access to MS
      else
        return null; //not existing user
}

 
function setUserCmsMemberObject($userEmail, $metaValue)  
{
  if(!isset($metaValue))
    throw new Exception('Error:DataNotSet', 1);

  $metaValue = serialize(json_decode($metaValue,true)); 

  if(getUserCmsMemberObject($userEmail) != null)
    $dotaz = $GLOBALS['pdo']->prepare("UPDATE wh21478_usermeta SET meta_value = :metaValue WHERE(user_id = (SELECT ID FROM wh21478_users WHERE user_email = :userEmail) AND meta_key = 'cms_member')");
  else
    $dotaz = $GLOBALS['pdo']->prepare("INSERT into wh21478_usermeta (user_id, meta_key, meta_value) VALUES((SELECT ID FROM wh21478_users WHERE user_email = :userEmail), 'cms_member', :metaValue)");
  // Vykonání dotazu
  $vysledek = $dotaz->execute(array(
    ":userEmail" => $userEmail,
    ":metaValue" => $metaValue
  ));
  return $vysledek;
}

function getMSDataStructure()
 {
    $dotaz = $GLOBALS['pdo']->prepare("SELECT option_value FROM wh21478_options WHERE option_name = 'member_basic'");
    // Vykonání dotazu
    $dotaz->execute();
    $vysledek = $dotaz->fetchAll();

    $members = unserialize($vysledek[0][0]);
     // echo '<pre>'; print_r($members); echo '</pre>';

    return member_profile_fields($members);
}



function member_profile_fields( $members ) { 
  // $members = get_option('member_basic');
  // if($user) $value=get_the_author_meta( 'cms_member', $user->ID );

    // echo '<pre>'; print_r($members); echo '</pre>'; 
  ?>

  <h3><?php echo ("Členské sekce"); ?></h3>
  <?php if(isset($members['members']) && is_array($members['members'])) { ?>
  <table class="wp-list-table widefat fixed pages" style="width:100%">
      <thead>
        <tr>
            <th><?php echo ("Zařadit do členské sekce"); ?></th>   
            <th><?php echo ("Zařadit do členské úrovně"); ?></th>   
            <th><?php echo ("Datum registrace"); ?></th>
            <th><?php echo ("Čas registrace"); ?></th>
            <th><?php echo ("Členství do"); ?></th>
        </tr>
      </thead>
        <?php
        $i=1;
        foreach($members['members'] as $id=>$member) { ?>
        <tr <?php if($i==1) echo 'class="alt"';  ?>>
          <td>
            <input type="checkbox" id="member_section_<?php echo $id; ?>_level" class="member_section_checkbox" name="member[<?php echo $id; ?>][section]" value="1" <?php if(isset($value[$id]) && isset($value[$id]['section'])) echo 'checked="checked"'; ?> />
            <label for="member_section_<?php echo $id; ?>_level"><strong><?php echo $member['name']; ?></strong> <?php if(!isset($member['dashboard']) || !$member['dashboard']) echo '<div style="color: red;">'.('Tato členská sekce nemá nastavenou žádnou stránku jako nástěnku.').'</div>'; ?></label>
          </td>

          <td> 
            <?php
            if(isset($member['levels'])) {
                foreach($member['levels'] as $lid=>$level) { ?>
                    <div>
                        <input type="checkbox" id="member_section_<?php echo $id; ?>_level_<?php echo $lid; ?>" name="member[<?php echo $id; ?>][levels][<?php echo $lid; ?>]" value="1" <?php if(isset($value[$id]) && isset($value[$id]['levels'][$lid])) echo 'checked="checked"'; ?> />
                        <label for="member_section_<?php echo $id; ?>_level_<?php echo $lid; ?>"><?php echo $level['name']; ?></label>
                    </div>
                <?php 
                }
            } 
            else echo (''); //Členská sekce neobsahuje žádné členské úrovně.
            ?>
          </td>
          <td>
            <input class="cms_datepicker" type="text" name="member[<?php echo $id; ?>][date]" value="<?php if(isset($value[$id]) && isset($value[$id]['date'])) echo $value[$id]['date']; ?>" style="width:100px"/>
          </td>
          <td>
            <input class="cms_timepicker" type="text" name="member[<?php echo $id; ?>][time]" value="<?php if(isset($value[$id]) && isset($value[$id]['time'])) echo $value[$id]['time']; ?>" style="width:100px"/>
          </td>
          <td>
            <input class="cms_datepicker" type="text" name="member[<?php echo $id; ?>][end]" value="<?php if(isset($value[$id]) && isset($value[$id]['end'])) echo $value[$id]['end']; ?>" style="width:100px"/>
          </td>
        </tr>
        <?php 
        $i=$i==1? 2:1;
        } ?>
  </table>
  <?php } else { ?>
        <div class="cms_error_box"><?php echo('Není vytvořena žádná členská sekce.'); ?></div>
  <?php } 
}   
