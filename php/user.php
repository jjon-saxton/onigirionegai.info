<?php
//ini_set('display_errors',true);
require_once './database.inc.php';
$table=new DataBaseTable('saves',true,'./settings.ini');

header("Content-type: text/json");

define ('IN_PHPBB',true);
$phpbb_root_path="/var/www/bbs/phpbb/";
$phpEx=substr(strchr(__FILE__,'.'),1);
include ($phpbb_root_path.'common.'.$phpEx);

$user->session_begin();
$auth->acl($user->data);
$user->setup();
$savedata=false;
if (is_array($user->data))
{
  $data=$user->data;
  
  $saveq=$table->getData("uid:`= {$data['user_id']}`");
  $savedata=$saveq->fetch(PDO::FETCH_ASSOC);
  
  switch ($data['user_rank'])
  {
    case 1:
    case 8:
      $data['level']=1; //Staff and Site Admin get hieghest security level
      break;
    case 7:
      $data['level']=2; //Next hieghest goes to Stars
      break;
    case 6:
      $data['level']=3; //Third goes to Angels
      break;
    case 5:
    case 4:
    case 3:
    case 2:
      $data['level']=4; //All other ranks get fourth level
      break;
    default:
      $data['level']=5; //Lowest level goes to anyone without a rank (guests, and new users)
  }
}

switch (request_var('action',''))
{
  case 'getname':
    if (empty($data) || $data['group_id'] == 1)
    {
      $data=array('username'=>false,'start_time'=>$data['session_start'],'session_id'=>$data['session_id']);
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
        $sel_id=request_var('id',0);
        $ep_tbl=new DataBaseTable('episodes',true,'./settings.ini');
        $ep_query=$ep_tbl->getData("num:`= {$sel_id}`",array('num','pid','next'));
        $episode=$ep_query->fetch(PDO::FETCH_ASSOC);
        $p_tbl=new DataBaseTable('chapters',true,'./settings.ini');
        $p_query=$p_tbl->getData("num:`= {$episode['pid']}`",array('num','cid'));
        $chapter=$p_query->fetch(PDO::FETCH_ASSOC);
        $c_tbl=new DataBaseTable('characters',true,'./settings.ini');
        $c_query=$c_tbl->getData("num:`= {$chapter['cid']}`",array('num','gname','sname'));
        $character=$c_query->fetch(PDO::FETCH_ASSOC);
        
        $progress[$character['num']][$chapter['id']][$episode['id']][]=request_var('choice',0);
        $save['uid']=$data['user_id'];
        $save['cur_level']=$data['level'];
        $save['progress']=json_encode($progress);
        
        $path="../project/dream-weaver/";
        
        if (!$savedata)
        {
          $method='putData';
        }
        else
        {
          $method='updateData';
        }
        
        if ($data['user_id'] >= 2 && $new=$table->$method($save))
        {
          if ($episode['next'] > 0)
          {
            $nquery=$ep_tbl->getData("num:`= {$episode['next']}`",array('num','pid','status','urls'));
            $next=$nquery->fetch(PDO::FETCH_ASSOC);
            
            if ($next['status'] == 1 || ($data['level'] == 1 && $next['status'] < 4) || ($data['level'] <= 2) && $next['status'] <= 2)
            {
              $options=json_decode($next['urls'],true);
              header("Location: ".$path.$options[request_var('choice',0)]);
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
        elseif ($data['user_id'] < 2) //Progress is not saved for anon users
        {
          if ($episode['next'] > 0)
          {
            $nquery=$ep_tbl->getData("num:`= {$episode['next']}`",array('num','pid','status','urls'));
            $next=$nquery->fetch(PDO::FETCH_ASSOC);
            
            if ($next['status'] == 1)
            {
              $options=json_decode($next['urls'],true);
              header("Location: ".$path.$options[request_var('choice',0)]);
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