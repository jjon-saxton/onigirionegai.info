<?php



class DataBaseSchema extends PDO

{

 private $ini;



 public function __construct($schema=null, $file='database.ini')

 {

  if (!$settings=parse_ini_file($file,TRUE)) throw new exception('Unable to open '.$file.'!');



  $dns=$settings['database']['driver'] .

  ':host='.$settings['database']['host'] .

  ((!empty($settings['database']['port'])) ? (';port='.$settings['database']['port']) : '') .

  ((!empty($schema)) ? (';dbname='.$schema) : ';dbname='.$settings['schema']['name']);



   parent::__construct($dns, $settings['schema']['username'],$settings['schema']['password']);



  $this->ini=$settings;

  $this->ini['name']=$file;

  return $settings;

 }



 public function addTable($table,array $cols=null)

 {

  if(!$cols)

  {

   $cols[]="`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY";

   $cols[]="`name` VARCHAR(50) NOT NULL";

  }

  $cols_stmt=implode(', ',$cols);

  $cols_stmt=rtrim($cols_stmt,', ');

  if ($this->query("CREATE TABLE `".$this->ini['schema']['tableprefix'].$table."` (".$cols_stmt.") ENGINE = MyISAM"))

  {

   return new DataBaseTable($table,null,$this->ini['name']);

  }

  else

  {

   throw new Exception("New Table Not Created!");

  }

 }



 public function showTables()

 {

  if ($query=$this->query("SHOW TABLES"))

  {

   return $query->fetchALL(PDO::FETCH_ASSOC);

  }

 }

 

 public function createBackup($file,$type='mis')

 {

   $tables=$this->showTables();

   $key="Tables_in_{$this->ini['schema']['name']}";

   

   switch ($type)

   {

     case 'sql':

   $sql=null; //TODO CREATE DATABASE?

   foreach ($tables as $info)

   {

     $table=new DataBaseTable($info[$key],false);

     $sql.=$table->dump($type)."\n";

   }

   

   if (file_put_contents($file,$sql))

   {

     return true;

   }

   else

   {

     trigger_error("Could not create database backup file '{$file}'",E_USER_WARNING);

     return false;

   }

     break;

     case 'mis':

     default:

     $text=array();

     foreach ($this->ini as $set=>$val)

     {

       if ($set != "name")

       {

         $text[$set]=null;

         foreach ($val as $tset=>$tval)

         {

           $text[$set].=$tset."= \"{$tval}\"\n";

         }

       }

     }

     

     foreach ($tables as $info)

     {

       $table=new DataBaseTable($info[$key],false);

       $text[$info[$key]]=$table->dump($type);

     }

     

     $ini=null;

     foreach ($text as $section=>$values)

     {

       $ini.="[{$section}]\n{$values}\n";

     }

     

     return file_put_contents($file,$ini);

   }

 }

 

 public function restoreBackup(array $is)

 {

   foreach ($is as $sec=>$set)

   {

     if ($sec != "schema" && $sec != "database")

     {

      $table=new DataBaseTable($set,false);

      foreach ($set as $row)

      {

        $r[]=$table->putData($row);

      }

     }

   }

   

   if (!empty($r) && is_array($r))

   {

     return true;

   }

   else

   {

     return false;

   }

 }

}



class DataBaseTable extends DataBaseSchema

{

 public $table;

 protected $indices;

 protected $results;



 public function __construct($table,$short=true,$file='database.ini')

 {

  $settings=parent::__construct($schema,$file);

  if ($short)

  {

    $this->table=$settings['schema']['tableprefix'].$table;

  }

  else

  {

    $this->table=$table;

  }



  $fieldlist=$this->getFields();

  foreach ($fieldlist as $field)

  {

   $this->fieldlist[]=$field->Field;

   switch ($field->Key)

   {

    case 'PRI':

    $this->indices['primary']=$field->Field;

    break;

   }

  }

  unset($fieldlist);

 }



 public function getFields()

 {

  $describe=$this->query("SHOW COLUMNS FROM ".$this->table);



  return $describe->fetchAll(PDO::FETCH_OBJ);

 }



 public function updateFields(array $cols,$inline=false)

 {

  if ($inline == true)

  {

   $state=$this->getFields();

   var_dump($state);

   //TODO check $cols to ensure it is a full list columns with new columns added in

   //TODO alter the table to put columns in the right order instead of just appending them at the end.

  }

  else

  {

   $cols_stmt=implode(', ',$cols);

   $cols_stmt=rtrim($cols_stmt,', ');

   if ($this->query("ALTER TABLE `".$this->table."` ADD (".$cols_stmt.")"))

   {

     $fieldlist=$this->getFields();

     foreach ($fieldlist as $field)

     {

      $this->fieldlist[]=$field->Field;

      switch ($field->Key)

      {

       case 'PRI':

       $this->indices['primary']=$field->Field;

       break;

     }

    }

    unset($fieldlist);

    return true;

   }

   else

   {

    throw new Exception("Could not alter '{$this->table}'");

    return false;

   }

  }

 }



 public function getData($q=null,array $cols=null,$sort=null,$limit=0,$offset=0, array $keycols=null)

 {

  $fieldlist=$this->fieldlist;

  if (!empty($cols))

  {

   $collist=implode(", ",$cols);

   $collist=rtrim($collist,", ");

  }

  else

  {

   $collist='*';

  }



  $sql="SELECT {$collist} FROM ".$this->table;



  if (preg_match_all("/(?P<key>(?:[a-z][a-z0-9_]*))(:)`(?P<values>.*?)`/is",$q,$filters) > 0) //new query string dilimeters for more flexability with values

  {

   $where=array();

   $i=0;

   foreach ($filters['key'] as $key)

   {

    $where[$key]=explode("|",$filters['values'][$i]);

    ++$i;

   }

   $q=trim(preg_replace("/(?P<key>(?:[a-z][a-z0-9_]*))(:)`(?P<values>.*?)`/is","",$q)." ");

  }

  elseif (preg_match_all("/(?P<key>(?:[a-z][a-z0-9_]*))(:)'(?P<values>.*?)'/is",$q,$filters) > 0) //Old style for compatability, will be deprecated.

  {

   $where=array();

   $i=0;

   foreach ($filters['key'] as $key)

   {

    $where[$key]=explode("|",$filters['values'][$i]);

    ++$i;

   }

   $q=trim(preg_replace("/(?P<key>(?:[a-z][a-z0-9_]*))(:)'(?P<values>.*?)'/is","",$q)." ");

  }



  $q=rtrim($q,",");

  if (empty($q))

  {

   $where_sql=" WHERE";

  }

  else

  {

   $where_sql=" WHERE MATCH (".$keycols." AGAINST ('".$q."')";

  }

  if (@is_array($where))

  {

   foreach ($where as $col=>$value)

   {

    if (in_array($col,$fieldlist))

    {

     $where_group=null;

     foreach ($value as $item)

     {

      if (preg_match("/^(<)(>)( )(?P<date1>[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) (\d{2}\:\d{2}))( )(?P<date2>[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) (\d{2}\:\d{2}))/",$item,$compare) > 0)

      {

       $where_group.="`".$col."` BETWEEN '".$compare['date1']."' AND '".$compare['date2']."'";

      }

      elseif (preg_match("/(<)(>)( )(?P<digit1>\\d+)( )(?P<digit2>\\d+)/",$item,$compare) > 0) // digits between

      {

       $where_group.="`".$col."` BETWEEN ".$compare['digit1']." AND ".$compare['digit2'];

      }

      elseif (preg_match("/^(?P<operator>.*)( )(?P<date>[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) (\d{2}\:\d{2})$)/",$item,$compare) > 0) //compare dates

      {

       $where_group.="`".$col."` ".$compare['operator']." '".$compare['date']."'";

      }

      elseif (preg_match("/(?P<operator>.*)( )(?P<digit>\\d+)/",$item,$compare) > 0) //compare digits

      {

       $where_group.="`".$col."` ".$compare['operator']." ".$compare['digit'];

      }

      else

      {

       $where_group.="`".$col."` LIKE \"{$item}\"";

      }

     }

     $wheres[]="(".$where_group.")";

    }

   }

   $where_sql.=" ".implode($wheres," AND ");

   $where_sql=preg_replace("/WHERE AND/","WHERE",$where_sql); //Prevents illegal WHERE AND combo

   if ($where_sql != "WHERE") //add the where string as long as it appears legal

   {

    $sql.=" ".$where_sql;

   }

  }

  

  if ($sort)

  {

   if (preg_match("/(?P<col>.*)( )(?P<operator><|>)/",$sort,$orderby))

   {

    if ($orderby['operator'] == '>')

    {

     $sql.=" ORDER BY `".$orderby['col']."` DESC";

    }

    else

    {

     $sql.=" ORDER BY `".$orderby['col']."` ASC";

    }

   }

   else

   {

    $sql.=" ORDER BY `".$sort."` ASC";

   }

  }

  if ($limit > 0)

  {

   $sql.=" LIMIT ".$limit;

  }

  if ($offset > 0)

  {

   $sql.=" OFFSET ".$offset;

  }



  try

  {

   $result=$this->query($sql);

  }

  catch (Exception $err)

  {

   trigger_error("SQL Server Error: ".$err->getMessage(),E_USER_ERROR);

  }



  return $result;

 }

 

 public function getByQuery($stmt)

 {

   $sql="SELECT * FROM `{$this->table}` ".$stmt;

   try

   {

     $result=$this->query($sql);

   }

   catch (Exception $err)

   {

     trigger_error("SQL Server Error: ".$err->getMessage(),E_USER_ERROR);

   }

   

   return $result;

 }



 public function updateData(array $fieldarray)

 {

  $update=array();



  $q="UPDATE `{$this->table}` SET ";

  foreach ($fieldarray as $field=>$value)

  {

   if (in_array($field,$this->fieldlist))

   {

    if ($this->indices['primary'] == $field)

    {

     $where="WHERE `{$field}`='{$value}'";

     $key=$value;

    }

    else

    {

     $q.=" `{$field}`=:{$field}, ";

     $update[":{$field}"]=$value;

    }

   }

   else

   {

    unset($fieldarray[$field]);

   }

  }

  $q=rtrim($q,", ").' '.$where;

  $query=$this->prepare($q);



  $this->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

  



  if (empty($update))

  {

   throw new Exception("Empty data set! Please ensure you pass an array of new value!");

   return false;

  }

  elseif ($query->execute($update))

  {

   return $key;

  }

  else

  {

   throw new Exception("Could not update data!");

   return false;

  }

 }



 public function deleteData(array $fieldarray)

 {

  $q="DELETE FROM {$this->table}";



  foreach ($fieldarray as $field=>$value)

  {

   if (in_array($field,$this->fieldlist))

   {

    if ($this->indices['primary'] == $field)

    {

     $q.=" WHERE `{$field}`='{$value}'";

    }

   }

  }

  $query=$this->prepare($q);



  $this->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

  if ($query->execute())

  {

   return true;

  }

  else

  {

   throw new Exception("Could not delete data");

   return false;

  }

 }



 public function putData(array $fieldarray)

 {

  $q="INSERT INTO ".$this->table;

  $cols=null;

  $placeholders=null;

  $values=array();



  foreach ($fieldarray as $field=>$value)

  {

   if (in_array($field,$this->fieldlist))

   {

    $cols.="`".$field."`,";

    $placeholders.=":".$field.",";

    $values[':'.$field]=$value;

   }

  }

  $cols=rtrim($cols,",");

  $placeholders=rtrim($placeholders,",");



  $q.=" ({$cols}) VALUES ({$placeholders})";

  $query=$this->prepare($q);



 $this->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

 if ($query->execute($values))

  {

   $id=$this->lastInsertID();

   if ($id > 0)

   {

    return $id;

   }

   else

   {

    return true;

   }

  }

  else

  {

   throw new Exception("Could not add data");

  }

 }



 public function emptyData()

 {

  try

  {

   $this->query("TRUNCATE `".$this->table."`");

  }

  catch (Exception $err)

  {

   trigger_error("SQL Server Error: ".$err->getMessage(),E_USER_ERROR);

  }



  return true;

 }

 

 public function dump($type='mis',$file=null)

 {

   $fields=$this->getFields();

   switch ($type)

   {

     case 'rss':

     //TODO make RSS XML from table data

     break;

     case 'sql':

     $sql="CREATE TABLE `{$this->table}` (";

     foreach ($fields as $col)

     {

       $sql.="`{$col->Field}` ".strtoupper($col->Type)." ";

       if ($col->Null == "NO")

       {

        $sql.="NOT NULL ";

       }

       if ($col->Key == "PRI")

       {

         $sql.="PRIMARY KEY ";

       }

       $sql.=strtoupper($col->Extra).", ";

     }

     $sql=rtrim($sql,", ");

     $sql.=") ENGINE MY_ISAM;\n";

     $query=$this->getData();

     while ($row=$query->fetch(PDO::FETCH_ASSOC))

     {

       $sql.="INSERT INTO `{$this->table}` SET ";

       foreach ($row as $col=>$val)

       {

         $sql.="{$col}='{$val}', ";

       }

       $sql=rtrim($sql,", ");

       $sql.=";\n";

     }

     $sql=rtrim($sql,"\n");

     if (empty($file))

     {

       return $sql;

     }

     elseif (file_put_contents($file,$sql))

     {

       return true;

     }

     else

     {

       trigger_error("Could not save {$file}!",E_USER_WARNING);

       return false;

     }

     break;

     case 'mis':

     default:

     $query=$this->getData();

     $ini=null;

     while ($row=$query->fetch(PDO::FETCH_ASSOC))

     {

       $id=$row[$this->indices['primary']];

       foreach ($row as $col=>$data)

       {

         $ini.=$id."[$col] = \"{$data}\"\n";

       }

     }

     

     if (empty($file))

     {

       return $ini;

     }

     elseif (file_put_contents($file,$ini))

     {

       return true;

     }

     else

     {

       trigger_error("Cannot save MIS INI '{$file}'",E_USER_WARNING);

       return false;

     }

   }

 }



 public function drop()

 {

  try

  {

   $this->query("DROP TABLE `".$this->table."`");

  }

  catch (Exception $e)

  {

   trigger_error("SQL Server Error: ".$e->getMessage(),E_USER_ERROR);

   return false;

  }



  return true;

 }

}

