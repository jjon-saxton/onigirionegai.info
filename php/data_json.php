<?php

require_once './database.inc.php';

header("Content-type: text/json");

session_name('vn');
session_start();

$tbl=new DataBaseTable($_GET['table'],true,'./settings.ini');

switch ($_GET['action'])
{
  case 'add':
    $result=$tbl->putData($_POST);
    //TODO encode result?
    break;
  case 'search':
  default:
    $result=$tbl->getData($_GET['q']);
    $data=$result->fetchAll(PDO::FETCH_ASSOC);
    
    switch ($_GET['table'])
    {
      case 'characters':
        foreach ($data as $item)
        {
          if (!empty($item['requirements']) && $_SESSION['user']['level'] != 1) //character has special unlock requirements
          {
            $item['locked']=true; //TODO unlock requirements need to be checked
          }
          else
          {
            $item['locked']=false;
          }
          $list[]=$item;
        }
        break;
      case 'chapters':
      case 'episodes':
        $saved=json_decode($_SESSION['user']['progress'],true);
        foreach ($data as $item)
        {
          $item['locked']=true;
          if ($item['status'] == 1) //released chapters are accessable to anyone, even guests!
          {
            $item['locked']=false;
          }
          elseif ($item['status'] == 2 && (!empty($_SESSION['user']['level']) && $_SESSION['user']['level'] <= 2)) //early released chapters are only accessible to stars and admins
          {
            $item['locked']=false;
          }
          elseif ($item['status'] == 3 && $_SESSION['user']['level'] == 1) //testing chapters are only accessable to admins
          {
            $item['locked']=false;
          }
          
          $prev=$item['num']-1;
          if ($prev != 0 && empty($saved))
          {
            $item['locked']=true; //SANITY CHECK! Make sure the current user has save data, just lock the episode if they don't, unless it is the first episode!
          }
          elseif ($prev > 0)
          {
            $previous=$tbl->getData("num:`= {$prev}` pid:`= {$item['pid']}`",array('id')); //Basically looks for the episode id of the previous episode
            $previous=$previous->fetch(PDO::FETCH_OBJ);
            $chk_id=$previous->id; //this is done to check if the user has read that episode
            
            if (is_array($saved))
            {  
              $saved_parents=array_column($saved,intval($item['pid']));
              if (!is_array(($saved_parents[0][$chk_id])))
              {
                $item['locked']=true; //if the user has not read the previous episode, lock this one
              } //otherwise keep the previous lock status
            }
          }
          
          $list[]=$item;
        }
        break;
      default:
        $list=$data;
    }
    
    print json_encode($list);
}