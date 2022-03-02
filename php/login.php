<?php
require "/home/onigirionegai/vendor/autoload.php";
require_once "./database.inc.php";
$table=new DataBaseTable('users',true,'./settings.ini');

session_name('vn');
session_start();

switch ($_GET['action'])
{
  case 'fbcheck':
    $fb = new \Facebook\Facebook([
      'app_id' => '1974560535899927',
      'app_secret' => '8ef83313b5567643d467e2bc4b7035bd',
      'default_graph_version' => 'v2.10',
    ]   );

    try {
      // Get the \Facebook\GraphNodes\GraphUser object for the current user.
      $response = $fb->get('/me', $_REQUEST['token']);
      $_SESSION['accessToken']=$_REQUEST['token'];
    } catch(\Facebook\Exceptions\FacebookResponseException $e) {
      // When Graph returns an error
      echo 'Graph returned an error: ' . $e->getMessage();
      exit;
    } catch(\Facebook\Exceptions\FacebookSDKException $e) {
      // When validation fails or other local issues
      echo 'Facebook SDK returned an error: ' . $e->getMessage();
      exit;
    }

    $me = $response->getGraphUser();
    $q=$table->getData("token:`fb:{$me->getID()}`");
    $user=$q->fetch();
    if (!empty($user['uid']))
    {
      $_SESSION['user']=$user['uid'];
      header("location:".$_SERVER['HTTP_REFERER']);
    }
    else
    {
      list($data['first'],$data['last'])=explode(" ",$me->getName());
      $data['token']="fb:".$me->getID();
      header("location: http://test.onigirionegai.info/profile.html#".$uid);
    }
    break;
  case 'ggcheck':
    $client=new Google_Client(['client_id' => '486150171601-j9rv5l7vdsjelthftnh6loor8i7b8aam.apps.googleusercontent.com']);
    $payload=$client->verifyIdToken($_REQUEST['token']);
    if ($payload)
    {
      $_SESSION['accessToken']=$_REQUEST['token'];
      $q=$table->getData("token:`gg:{$payload['sub']}`");
      $user=$q->fetch(PDO::FETCH_ASSOC);
      if (!empty($user['uid']))
      {
        $_SESSION['user']=$user;
        header("Location: ".$_SERVER['HTTP_REFERER']);
      }
      else
      {
        $data['uname']=$payload['name'];
        $data['email']=$payload['email'];
        $data['token']="gg:".$payload['sub'];
        
        if ($uid=$table->putData($data))
        {
          header("location: http://test.onigirionegai.info/profile.html#".$uid);
        }
        else
        {
          header("location: http://test.onigirionegai.info/php/logout.php");
        }
      }
    }
    break;
  case 'loggedin':
  default:
    if (is_array($_SESSION['user']))
    {
      print json_encode($_SESSION['user']);
    }
    else
    {
      $user['active']=false;
      print json_encode($user);
    }
}