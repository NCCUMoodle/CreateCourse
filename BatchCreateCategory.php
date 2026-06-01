<?php
 require_once('../config.php');
 require_once($CFG->dirroot.'/course/lib.php');
 require_once('SemesterName.php');
	
 function CreateCategory($SchoolAffairsDBHost, $SchoolAffairsDBPort, $SchoolAffairsDBName, $SchoolAffairsDBUsername, $SchoolAffairsDBPassword,$MoodleDBHost, $MoodleDBPort, $MoodleDBName, $MoodleDBUsername, $MoodleDBPassword, $SemesterFullName)
 { 
  try
  {
   $SchoolAffairsMySQLCon = new PDO("mysql:host=$SchoolAffairsDBHost;port=$SchoolAffairsDBPort;dbname=$SchoolAffairsDBName", $SchoolAffairsDBUsername, $SchoolAffairsDBPassword);
      
   $SchoolAffairsMySQLCon->exec("SET CHARACTER SET utf8mb4");
   $SchoolAffairsMySQLCon->exec("SET NAMES utf8mb4");
      
   $SchoolAffairsMySQLCon->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      
   echo "School Affairs DB 連線成功！.................." . "<br>";
      
   $MoodleMySQLCon = new PDO("mysql:host=$MoodleDBHost;port=$MoodleDBPort;dbname=$MoodleDBName", $MoodleDBUsername, $MoodleDBPassword);
      
   $MoodleMySQLCon->exec("SET CHARACTER SET utf8mb4");
   $MoodleMySQLCon->exec("SET NAMES utf8mb4");
      
   $MoodleMySQLCon->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   
   echo "Moodle DB 連線成功！.................." . "<br>";
      
   date_default_timezone_set("Asia/Taipei");  //設定時區為台北時區
	  
   $DBCollageID = array();
   $DBCollageFullName = array();
   $DBDepartmentCID = array();
   $DBDepartmentFullName = array();
     
   $SQL = "select count(*) as TotalSemester from mdl_course_categories where name = '" . $SemesterFullName . "' ";
      
   $Data = $MoodleMySQLCon->query($SQL);
   $Rows = $Data->fetchAll();
     
   foreach($Rows as $MoodleMySQLRow)
   {
    $FindMoodleSemester = $MoodleMySQLRow["TotalSemester"];
   }
     
   if($FindMoodleSemester>0)
   {
	echo $SemesterFullName . " 學期已建立！.................." . "<br>";
   }
   else
   {
    $GetDateTime = date("Ymd_His");
    $FileName = "/var/moodlelog/CreateCategory/CreateCategory_" . $GetDateTime . ".log";
       
    $fp = fopen($FileName, "w");
       
//==============取得各學院名稱==========Start====================
       
    $SQL = "select id, ChineseName, EnglishName from college where disable = '0' ";
      
    $Data = $SchoolAffairsMySQLCon->query($SQL);
    $Rows = $Data->fetchAll();
    $TotalCollageData = count($Rows);
      
    $W = 0;	  
    foreach($SchoolAffairsMySQLCon->query($SQL) as $SchoolAffairsMySQLRow)
    {
     $DBCollageID[$W] = $SchoolAffairsMySQLRow["id"];
        
     $CollageChineseName = $SchoolAffairsMySQLRow["ChineseName"];
     $CollageEnglishName = $SchoolAffairsMySQLRow["EnglishName"];
        
     $DBCollageFullName[$W] = $CollageChineseName . "-" . $CollageEnglishName;
       
     $W++;
    }
      
	  
//==============取得各學院名稱==========end======================
       
//==============取得各系所名稱==========Start====================
      
    $SQL = "select ChineseName, EnglishName, CollegeID ";
    $SQL .= "from department where disable = '0' ";
       
    $Data = $SchoolAffairsMySQLCon->query($SQL);
    $Rows = $Data->fetchAll();
    $TotalDepartmentData = count($Rows);
      
    $W = 0;	  
    foreach($SchoolAffairsMySQLCon->query($SQL) as $SchoolAffairsMySQLRow)
    {
     $DBDepartmentCID[$W] = $SchoolAffairsMySQLRow["CollegeID"];
        
     $DepartmentChineseName = $SchoolAffairsMySQLRow["ChineseName"];
     $DepartmentEnglishName = $SchoolAffairsMySQLRow["EnglishName"];
       
     $DBDepartmentFullName[$W] = $DepartmentChineseName . "-" . $DepartmentEnglishName;
       
     $W++;
    }
       
//==============取得各系所名稱==========End======================
      
//==============建立學期類別============Start====================
     
    $SemesterData = new stdClass();  
       
    $SemesterData->name = $SemesterFullName;
    $SemesterData->parent = "0";
    $SemesterData->visible = "1";
      
    $SemesterCategory = core_course_category::create($SemesterData);
      
    $Msg = "建立學期類別：" . "&nbsp;&nbsp;" . $SemesterFullName . "<br>";
    $Log = "建立學期類別：" . "  " . $SemesterFullName . "\r\n";
      
    echo $Msg;
     
    fwrite($fp, $Log);
        
//==============建立學期類別============End======================
       
//===========取得學期類別在Moodle資料庫的Category ID==========Start==============
       
    $SQL = "select id from mdl_course_categories where name = '" . $SemesterFullName . "' ";
      
    foreach($MoodleMySQLCon->query($SQL) as $MoodleMySQLRow)
    {
     $SemesterDBCategoryID = $MoodleMySQLRow["id"];
    }
       
//===========取得學期類別在Moodle資料庫的Category ID==========End================
       
//===========在學期類別下建立學院類別（含通識類別）=============Start============
       
    $CategoryData = new stdClass();
      
    for($i=0;$i<$TotalCollageData;$i++)
    {
     $CategoryData->name = $DBCollageFullName[$i];
     $CategoryData->parent = $SemesterDBCategoryID;
     $CategoryData->visible = "1";
       
     $CollageCategory = core_course_category::create($CategoryData);
       
     $Msg = "建立學院類別（含通識類別）：" . "&nbsp;&nbsp;" . $DBCollageFullName[$i] . "<br>";
     $Log = "建立學院類別（含通識類別）：" . "  " . $DBCollageFullName[$i] . "\r\n";
       
     echo $Msg;
      
     fwrite($fp, $Log);
    }
      
//===========在學期類別下建立學院類別（含通識類別）=============End==============
       
//===========在學院類別下建立系所類別=================Start======================
       
    for($i=0;$i<$TotalCollageData;$i++)
    {
       
//===========取得學院期類別在Moodle資料庫的Category ID==========Start==============
       
     $SQL = "select id from mdl_course_categories where name = '" . $DBCollageFullName[$i] . "' ";
       
     foreach($MoodleMySQLCon->query($SQL) as $MoodleMySQLRow)
     {
      $MoodleCollageID = $MoodleMySQLRow["id"];
     }
       
//===========取得學院期類別在Moodle資料庫的Category ID==========End================
        
//==================建立系所類別=================Start======================
        
     for($j=0;$j<$TotalDepartmentData;$j++)
     {
      if(strcmp($DBCollageID[$i],$DBDepartmentCID[$j])==0)
      {
       $DepartmentData = new stdClass();
          
       $DepartmentData->name = $DBDepartmentFullName[$j];
       $DepartmentData->parent = $MoodleCollageID;
       $DepartmentData->visible = "1";
           
       $DepartmentCategory = core_course_category::create($DepartmentData);
          
       echo "建立系所類別" . "&nbsp;&nbsp;&nbsp;&nbsp;";
       echo "學院：" . "&nbsp;&nbsp;" . $DBCollageFullName[$i] . "&nbsp;&nbsp;&nbsp;&nbsp;";
       echo "系所：" . "&nbsp;&nbsp;" . $DBDepartmentFullName[$j] . "<br>";
           
       $Msg = "建立系所類別" . "&nbsp;&nbsp;&nbsp;&nbsp;";
       $Msg .= "學院：" . "&nbsp;&nbsp;" . $DBCollageFullName[$i] . "&nbsp;&nbsp;&nbsp;&nbsp;";
       $Msg .= "系所：" . "&nbsp;&nbsp;" . $DBDepartmentFullName[$j] . "<br>";
          
       $Log = "建立系所類別" . "    ";
       $Log .= "學院：" . "  " . $DBCollageFullName[$i] . "    ";
       $Log .= "系所：" . "  " . $DBDepartmentFullName[$j] . "\r\n";
           
       echo $Msg;
           
       fwrite($fp, $Log);
      }
     }
        
//==================建立系所類別=================End========================
       
    }
       
//===========在學院類別下建立系所類別=================End========================
       
    fclose($fp);
   }
	 
  }
  catch(PDOException $e)
  {
   echo $e->getMessage(); 
  }
 }
   
 if($SameSemesterCName==true && $SameSemesterEName==true)
 {
  $SemesterChineseName = $SemesterChineseName01;
  $SemesterEnglishName = $SemesterEnglishName01;
  $SemesterFullName = $SemesterChineseName . " - " . $SemesterEnglishName;
      
  echo "SemesterName = " . $SemesterFullName . "<br>";
     
  CreateCategory($SchoolAffairsDBHost, $SchoolAffairsDBPort, $SchoolAffairsDBName, $SchoolAffairsDBUsername, $SchoolAffairsDBPassword,$MoodleDBHost, $MoodleDBPort, $MoodleDBName, $MoodleDBUsername, $MoodleDBPassword, $SemesterFullName);
 }
 else
 {
  $SemesterChineseName = $SemesterChineseName01;
  $SemesterEnglishName = $SemesterEnglishName01;
  $SemesterFullName = $SemesterChineseName . " - " . $SemesterEnglishName;
	  
  echo "SemesterName = " . $SemesterFullName . "<br>";
      
  CreateCategory($SchoolAffairsDBHost, $SchoolAffairsDBPort, $SchoolAffairsDBName, $SchoolAffairsDBUsername, $SchoolAffairsDBPassword,$MoodleDBHost, $MoodleDBPort, $MoodleDBName, $MoodleDBUsername, $MoodleDBPassword, $SemesterFullName);
       
  echo "<br><br>";
      
  $SemesterChineseName = $SemesterChineseName02;
  $SemesterEnglishName = $SemesterEnglishName02;
  $SemesterFullName = $SemesterChineseName . " - " . $SemesterEnglishName;
      
  echo "SemesterName = " . $SemesterFullName . "<br>";
      
  CreateCategory($SchoolAffairsDBHost, $SchoolAffairsDBPort, $SchoolAffairsDBName, $SchoolAffairsDBUsername, $SchoolAffairsDBPassword,$MoodleDBHost, $MoodleDBPort, $MoodleDBName, $MoodleDBUsername, $MoodleDBPassword, $SemesterFullName);
 }
   
?>
