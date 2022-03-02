<?php
require "/home/onigirionegai/vendor/autoload.php";
require_once "./database.inc.php";
$table=new DataBaseTable('jusers',true,'./settings.ini');

session_name('jfavs');
session_start();

$q=$table->getData("uid:`{$_SESSION['user']}`");
$cur_usr=$q->fetch();
session_unset();

if (!empty($cur_usr['token']))
{
  list($service,$id)=explode(":",$cur_usr['token']);
  switch ($service)
  {
    case 'fb':
      session_destroy();
      print <<<HTML
<html>
    <head>
    </head>
    <body>
        <script>
  window.fbAsyncInit = function() {
    FB.init({
      appId      : '1974560535899927',
      cookie     : false,  // enable cookies to allow the server to access 
                          // the session
      xfbml      : true,  // parse social plugins on this page
      version    : 'v2.8' // use graph api version 2.8
    });

    FB.getLoginStatus(function(response) {
      FB.logout(function(response){
        window.location='https://onigirionegai.info/jfavs/';
      })
    });

  };

  // Load the SDK asynchronously
  (function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s); js.id = id;
    js.src = "https://connect.facebook.net/en_US/sdk.js";
    fjs.parentNode.insertBefore(js, fjs);
  }(document, 'script', 'facebook-jssdk'));
        </script>
    </body>
</html>
HTML;
      break;
    case 'gg':
      session_destroy();
      print <<<HTML
<html>
    <head>
        <meta name="google-signin-client_id" content="486150171601-j9rv5l7vdsjelthftnh6loor8i7b8aam.apps.googleusercontent.com">
    </head>
    <body>
        <script src="https://apis.google.com/js/platform.js?onload=onLoadCallback" async defer></script>
        <script>
            window.onLoadCallback = function(){
                gapi.load('auth2', function() {
                    gapi.auth2.init().then(function(){
                        var auth2 = gapi.auth2.getAuthInstance();
                        auth2.signOut().then(function () {
                            document.location.href = 'https://onigirionegai.info/jfavs/';
                        });
                    });
                });
            };
        </script>
    </body>
</html>
HTML;
      break;
    case 'lh':
    default:
      session_destroy();
      header("Location: https://onigirionegai.info/jfavs/");
      
  }
}
else
{
  session_destroy();
  header("Location: https://onigirionegai.info/jfavs/");
}