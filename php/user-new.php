<?php

require_once './database.inc.php';

header("Content-type: text/json");

define ('IN_PHPBB',true);
$phpbb_root_path="/var/www/bbs/phpbb/";
$phpEx=substr(strchr(__FILE__,'.'),1);
include ($phpbb_root_path.'common.'.$phpEx);

$user->session_begin();
$auth->acl($user->data);
$user->setup();
if (is_array($user->data))
{
  $data=$user->data;
}

switch (request_var('action',0))
{
  case 'getname':
    if (empty($data) || $data['group_id'] == 1)
    {
      $data['uname']=false;
    }
    print json_encode($data);
    break;
  case 'getchoice':
    $data=json_decode($_SESSION['user']['progress'],true); //TODO fix progress
    print json_encode($data[request_var('chara',0)][request_var('ch',0)][request_var('ep',0)]);
    break;
  case 'save':
  default:
    $progress=json_decode($data['progress'],true); //TODO fix progress
    switch (request_var('level',0))
    {
      case 'chapter':
      case 'episode':
      default:
        $ep_tbl=new DataBaseTable('episodes',true,'./settings.ini');
        $ep_query=$ep_tbl->getData("id:`= {request_var('id']}`",array('id','pid','next'));
        $episode=$ep_query->fetch(PDO::FETCH_ASSOC);
        $p_tbl=new DataBaseTable('chapters',true,'./settings.ini');
        $p_query=$p_tbl->getData("id:`= {$episode['pid']}`",array('id','cid'));
        $chapter=$p_query->fetch(PDO::FETCH_ASSOC);
        $c_tbl=new DataBaseTable('characters',true,'./settings.ini');
        $c_query=$c_tbl->getData("num:`= {$chapter['cid']}`",array('num','gname','sname'));
        $character=$c_query->fetch(PDO::FETCH_ASSOC);
        
        $progress[$character['num']][$chapter['id']][$episode['id']][]=request_var('choice'];
        $data['progress']=json_encode($progress);
        
        $path="../project/dream-weaver/";
        
        if (!$savedata || $new=$table->updateData($data))
        {
          if ($episode['next'] > 0)
          {
            $nquery=$ep_tbl->getData("id:`= {$episode['next']}`",array('id','pid','status','urls'));
            $next=$nquery->fetch(PDO::FETCH_ASSOC);
            
            if ($next['status'] == 1 || ($_SESSION['user']['level'] == 1 && $next['status'] < 4) || ((!empty($_SESSION['user']['level']) && $_SESSION['user']['level'] <= 2) && $next['status'] <= 2))
            {
              $options=json_decode($next['urls'],true);
              header("Location: ".$path.$options[request_var('choice']]);
            }
            else
            {
              header("Location: ../soon.html");
            }
          }
          else
          {
            header("Location: ".$path."/#".$character['gname'].$character['sname']);
          }
        }
        else
        {
          header("Location: ../error.html");
        }
    }
}