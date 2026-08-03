<?php
/**
*  合併課程時，課名、課號需重組。
*  第一筆為主要合併課程的完整的課號（學期別＋課號），其他合併課程為顯示課號。
*  組合完合併課程的課號，Moodle以此作為合併課程的IDNumber。
*  各課程的課號以半形「/」區隔。
*  相同課名只顯示一次，不同課名會以全形「／」做區隔並顯示。
*  課號範例：1081_307917001/357871001
*  課名範例：1081_307917001/357871001_財務報表分析
*  課名範例：1081_000218051/208021011_總體經濟學／國際金融
*  課名範例：1081_253049001/861002011/863008001_國際關係理論／語料庫語言學與語言教學
*/
    
 require_once('../config.php');
 require_once($CFG->dirroot.'/course/lib.php');
 require_once($CFG->dirroot.'/group/lib.php');
 require_once('SemesterName.php');
    
 function MergeCourse($SchoolAffairsDBHost, $SchoolAffairsDBPort, $SchoolAffairsDBName, $SchoolAffairsDBUsername, $SchoolAffairsDBPassword,$MoodleDBHost, $MoodleDBPort, $MoodleDBName, $MoodleDBUsername, $MoodleDBPassword, $SemesterChineseName, $SemesterFullName, $EndDateTimestamp, $ChineseYear, $SubSemester)
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
     
   $SourceCourseIDNumber = array();
   $AllSourceCourseIDNumber = array();
   $SourceMergeCourseIDNumber = array();
   $AllMergeFullCourseName = array();
   $AllMergeChineseCourseName = array();
   $AllMergeFullCourseIDNumber = array();
   $MoodleMergeFullCourseIDNumber = array();
   $MoodleMergeFullCourseName = array();
   $AllMergeCourseIDNumber = array();
   $MergeFullCourseIDNumber = array();
   $SplitSourceMergeCourseIDNumber = array();
   $SplitSourceMergeCourseIDNumberCode = array();
   $SplitMainCourseChineseName = array();
   $SplitMainCourseFullEnglishName = array();
   $SplitSubCourseFullName = array();
   $SplitSubCourseFullEnglishName = array();
   $CheckMoodleMargeCourse = array();
   $HideMergeCourseIDNumber = array();
   $UniqueAllMergeCourseChineseName = array();
      
   $GetDateTime = date("Ymd_His");
      
   $FileName = "/var/moodlelog/CreateMerageCoure/CreateMergeCourse_" . $GetDateTime . ".log";
     
   $fp = fopen($FileName, "w");
      
   echo "Get Source Merage Course Data........................." . "<br>";
      
   $SQL = "select count(*) as TotalSemester ";
   $SQL .= "from mdl_course_categories ";
   $SQL .= "where parent = '0' ";
   $SQL .= "and name = '" . $SemesterFullName . "' ";
      
   $Data = $MoodleMySQLCon->query($SQL);
   $Rows = $Data->fetchAll();
      
   foreach($Rows as $MoodleMySQLRow)
   {
    $FindMoodleSemester = $MoodleMySQLRow["TotalSemester"];
   }
     
   if($FindMoodleSemester==0)
   {
    $Msg = "Moodle 未建立「" . $SemesterChineseName . "」學期類別(Category)！";
       
    $Log = $Msg . "\r\n";
      
    echo $Msg . "<br>";
      
    fwrite($fp, $Log);
      
    fclose($fp);
      
    exit;
   }
   else
   {
    $SQL = "select id ";
    $SQL .= "from mdl_course_categories ";
    $SQL .= "where parent = '0' ";
    $SQL .= "and name = '" . $SemesterFullName . "' ";
      
    $Data = $MoodleMySQLCon->query($SQL);
    $Rows = $Data->fetchAll();
      
    foreach($MoodleMySQLCon->query($SQL) as $MoodleMySQLRow)
    {
     $SemesterCategoryID = $MoodleMySQLRow["id"];
    }
       
    $SQL = "select startdate, enddate ";
    $SQL .= "from semesterdate ";
    $SQL .= "where semester = '" . $SemesterChineseName . "' ";
      
    foreach($SchoolAffairsMySQLCon->query($SQL) as $SchoolAffairsMySQLRow)
    {
     $SemesterStartDate = $SchoolAffairsMySQLRow["startdate"];
    }
       
    $Msg = $SemesterChineseName . " 學期 開學日期：" . $SemesterStartDate;
      
    $Log = $Msg . "\r\n";
      
    echo $Msg . "<br>";
      
    fwrite($fp, $Log);
     
    $StartDateTimestamp = strtotime($SemesterStartDate);
      
    $WeekCode = date("w", $StartDateTimestamp);
      
    switch($WeekCode)
    {
     case 0:
      $FirstWeekStartDate = date("Y/m/d", strtotime("+1 day", $StartDateTimestamp));
       $FirstWeekEndDate = date("Y/m/d", strtotime("+7 day", $StartDateTimestamp));
     break;
     case 1:
      $FirstWeekStartDate = $SemesterStartDate;
      $FirstWeekEndDate = date("Y/m/d", strtotime("+6 day", $StartDateTimestamp));
     break;
     case 2:
      $FirstWeekStartDate = date("Y/m/d", strtotime("-1 day", $StartDateTimestamp));
      $FirstWeekEndDate = date("Y/m/d", strtotime("+5 day", $StartDateTimestamp));
     break;
     case 3:
      $FirstWeekStartDate = date("Y/m/d", strtotime("-2 day", $StartDateTimestamp));
      $FirstWeekEndDate = date("Y/m/d", strtotime("+4 day", $StartDateTimestamp));
     break;
     case 4:
      $FirstWeekStartDate = date("Y/m/d", strtotime("-3 day", $StartDateTimestamp));
      $FirstWeekEndDate = date("Y/m/d", strtotime("+3 day", $StartDateTimestamp));
     break;
     case 5:
      $FirstWeekStartDate = date("Y/m/d", strtotime("-4 day", $StartDateTimestamp));
      $FirstWeekEndDate = date("Y/m/d", strtotime("+2 day", $StartDateTimestamp));
     break;
     case 6:
      $FirstWeekStartDate = date("Y/m/d", strtotime("-5 day", $StartDateTimestamp));
      $FirstWeekEndDate = date("Y/m/d", strtotime("+1 day", $StartDateTimestamp));
     break;
    }
	
   }  
     
//================從中繼資料庫取得該學期所有申請合併課程的課程資料和選課清單=============Start===============
      
   $SQL = "select idnumber, mrg_tpe, mrg_sub ";
   $SQL .= "from subformoodle ";       //取得正式資料Table
   //$SQL .= "from subformoodle_testAP ";  //取得測試資料Table
   $SQL .= "where idnumber like '" . $SemesterChineseName . "%' ";
   $SQL .= "and upd_source = 'APY' ";  //老師已申請開課
   $SQL .= "and mrg_tpe is not null ";
   $SQL .= "and mrg_tpe > '0' ";
   $SQL .= "and mrg_sub is not null ";
   $SQL .= "and length(trim(mrg_sub)) > 0 ";
   $SQL .= "union ";
   $SQL .= "select idnumber, mrg_tpe, mrg_sub ";
   $SQL .= "from subformoodle ";       //取得正式資料Table
   //$SQL .= "from subformoodle_testAP ";  //取得測試資料Table
   $SQL .= "where idnumber like '" . $SemesterChineseName . "%' ";
   $SQL .= "and category like '%遠距教學收播通識科目%' ";  //遠距課程無授課老師可以申請，系統直接開課
   $SQL .= "and mrg_tpe is not null ";
   $SQL .= "and mrg_tpe > '0' ";
   $SQL .= "and mrg_sub is not null ";
   $SQL .= "and length(trim(mrg_sub)) > 0 ";
     
   $Data = $SchoolAffairsMySQLCon->query($SQL);
   $Rows = $Data->fetchAll();
   $TotalSourceCourseData = count($Rows);
     
   $W = 0;
   foreach($SchoolAffairsMySQLCon->query($SQL) as $SchoolAffairsMySQLRow)
   {
    $SourceCourseIDNumber[$W] = $SchoolAffairsMySQLRow["idnumber"];
    $SourceMergeCourseType[$W] = $SchoolAffairsMySQLRow["mrg_tpe"];
    $SourceMergeCourseIDNumber[$W] = $SchoolAffairsMySQLRow["mrg_sub"];
      
    $W++;
   }
     
//================從中繼資料庫取得該學期所有申請合併課程的課程資料和選課清單=============End=================  
      
   $E = 0;  
   for($i=0;$i<$TotalSourceCourseData;$i++)
   {
      
//==============組合合併課程的課號，Moodle以此作為合併課程的IDNumber===============Start=============
      
    $MergeCourseID = "";
      
    $AllMergeCourseIDNumber[$E] = $SourceCourseIDNumber[$i] . "," . $SourceMergeCourseIDNumber[$i];
      
    $FullMergeCourseIDNumber = $SourceCourseIDNumber[$i] . "," . $SourceMergeCourseIDNumber[$i];
       
    $SplitSourceMergeCourseIDNumber  = explode(",", $SourceMergeCourseIDNumber[$i]);  
      
    $TotalSlaveMargeCourse = count($SplitSourceMergeCourseIDNumber);
      
    $MergeAllCourseIDNumber = $SourceCourseIDNumber[$i] . "/";
       
    for($j=0;$j<$TotalSlaveMargeCourse;$j++)
    {
     $SplitSourceMergeCourseIDNumberCode = explode("_", $SplitSourceMergeCourseIDNumber[$j]);
      
     $SplitMergeCourseIDNumberCodeSize = count($SplitSourceMergeCourseIDNumberCode);
      
     $MaxItem = $TotalSlaveMargeCourse - 1;
        
     if($j==$MaxItem)
     {
      $MergeCourseID = $MergeCourseID . $SplitSourceMergeCourseIDNumberCode[1];
     } 
     else
     {
      $MergeCourseID = $MergeCourseID . $SplitSourceMergeCourseIDNumberCode[1] . "/";
     } 
      
     unset($SplitSourceMergeCourseIDNumberCode);
    }
      
    $MergeAllCourseIDNumber .= $MergeCourseID;
      
    $MergeFullCourseIDNumber[$i] = $MergeAllCourseIDNumber;
      
//==============組合合併課程的課號，Moodle以此作為合併課程的IDNumber===============End===============
      
//================組合合併課程的課程名稱=======================Start====================
      
    $SplitMergeCourseIDNumber  = explode(",", $FullMergeCourseIDNumber);
      
    $TotalMergeCourse = count($SplitMergeCourseIDNumber);
      
	  
	 //由於資料庫欄位長度的限制，合併超過3門課，課程名稱僅顯示前2門不同課名稱的課程
    if($TotalMergeCourse>3)
    {
     for($j=0;$j<$TotalMergeCourse;$j++)
     {
      $SQL = "select distinct fullname, shortname ";
      $SQL .= "from subformoodle ";       //取得正式資料Table
      //$SQL .= "from subformoodle_testAP ";  //取得測試資料Table
      $SQL .= "where idnumber = '" . $SourceCourseIDNumber[$i] . "' ";
		 
      $Data = $SchoolAffairsMySQLCon->query($SQL);
      $Rows = $Data->fetchAll();
      $TotalCourseData = count($Rows);
         
      foreach($SchoolAffairsMySQLCon->query($SQL) as $SchoolAffairsMySQLRow)
      {
       $SourceCourseFullChineseName = $SchoolAffairsMySQLRow["fullname"];
       $SourceCourseFullEnglishName = $SchoolAffairsMySQLRow["shortname"];
      }
         
      $SplitCourseFullChineseName = explode("_", $SourceCourseFullChineseName);
      $CourseChineseName = $SplitCourseFullChineseName[2];	 
         
      $SplitCourseFullEnglishName = explode("_", $SourceCourseFullEnglishName);   
      $SourceCoursePartEnglishName = $SplitCourseFullEnglishName[2];
         
      $SplitCoursePartEnglishName = explode("'", $SourceCoursePartEnglishName);
         
      $DataSize = count($SplitCoursePartEnglishName);
         
      $ApostropheCh = "\'";
         
      $CourseEnglishName = "";
          
      for($n=0;$n<$DataSize;$n++)
      {
       if($n==0)
       {
        $CourseEnglishName = $SplitCoursePartEnglishName[$n];
       }
       else
       {
        $CourseEnglishName = $ApostropheCh . $SplitCoursePartEnglishName[$n];
       }
      }
     }
    }
    else
    {
     for($j=0;$j<$TotalMergeCourse;$j++)
     {
      $SQL = "select distinct fullname, shortname ";
      $SQL .= "from subformoodle ";       //取得正式資料Table
      //$SQL .= "from subformoodle_testAP ";  //取得測試資料Table
      $SQL .= "where idnumber = '" . $SplitMergeCourseIDNumber[$j] . "' ";
          
      $Data = $SchoolAffairsMySQLCon->query($SQL);
      $Rows = $Data->fetchAll();
      $TotalCourseData = count($Rows);
        
      foreach($SchoolAffairsMySQLCon->query($SQL) as $SchoolAffairsMySQLRow)
      {
       $SourceCourseFullChineseName = $SchoolAffairsMySQLRow["fullname"];
       $SourceCourseFullEnglishName = $SchoolAffairsMySQLRow["shortname"];
      }
         
      $SplitCourseFullChineseName = explode("_", $SourceCourseFullChineseName);
      $CoursePartChineseName = $SplitCourseFullChineseName[2];
      $AllMergeCourseChineseName[$j] = $CoursePartChineseName;  
         
      $SplitCourseFullEnglishName = explode("_", $SourceCourseFullEnglishName);   
      $SourceCoursePartEnglishName = $SplitCourseFullEnglishName[2];
         
      $SplitCoursePartEnglishName = explode("'", $SourceCoursePartEnglishName);
         
      $DataSize = count($SplitCoursePartEnglishName);
         
      $ApostropheCh = "\'";
         
      $DBCourseEnglishName = "";
         
      for($n=0;$n<$DataSize;$n++)
      {
       if($n==0)
       {
        $DBCourseEnglishName .= $SplitCoursePartEnglishName[$n];
       }
       else
       {
        $DBCourseEnglishName .= $ApostropheCh . $SplitCoursePartEnglishName[$n];
       }
      }
        
      $AllMergeCourseEnglishName[$j] = $DBCourseEnglishName;
     }
        
     $CourseChineseName = "";
       
     $UniqueAllMergeCourseChineseName = array_unique($AllMergeCourseChineseName);
      
     $TotalMergeChineseCourseSize = count($UniqueAllMergeCourseChineseName);
       
     for($j=0;$j<$TotalMergeChineseCourseSize;$j++)
     {
      if($j==0)
      {
       $CourseChineseName = $CourseChineseName .  $UniqueAllMergeCourseChineseName[$j];
      }
      else
      {
       $CourseChineseName = $CourseChineseName . "／" . $UniqueAllMergeCourseChineseName[$j];
      }
     }
       
     $CourseEnglishName = "";
        
     $UniqueAllMergeCourseEnglishName = array_unique($AllMergeCourseEnglishName);
       
     $TotalMergeEnglishCourseSize = count($UniqueAllMergeCourseEnglishName);
       
     for($j=0;$j<$TotalMergeEnglishCourseSize;$j++)
     {
      if($j==0)
      {
       $CourseEnglishName = $CourseEnglishName .  $UniqueAllMergeCourseEnglishName[$j];
      }
      else
      {
       $CourseEnglishName = $CourseEnglishName . "／" . $UniqueAllMergeCourseEnglishName[$j];
      }
     }
    }	   
      
//===============組合被合併課程的課程名稱==============End========================
      
    unset($AllMergeCourseChineseName);
    unset($AllMergeCourseEnglishName);
     
//================組合合併課程的課程名稱=======================End======================
       
    $CourseFullName = $MergeAllCourseIDNumber . "_" . $CourseChineseName . "_" . $CourseEnglishName;
	$CourseChineseFullName = $MergeAllCourseIDNumber . "_" . $CourseChineseName;
	  
    $AllMergeFullCourseName[$E] = $CourseFullName;
    $AllMergeChineseCourseName[$E] = $CourseChineseFullName;
    $AllMergeFullCourseIDNumber[$E] = $MergeAllCourseIDNumber;
      
    unset($SplitSourceMergeCourseIDNumber);
     
    unset($UniqueAllMergeCourseChineseName);
     
    $E++;
   }
     
      
//==========隱藏解除合併的課程和開啟未停開的單獨課程=======Start==========
      
   $SQL = "select idnumber, fullname ";
   $SQL .= "from mdl_course ";
   $SQL .= "where idnumber like '" . $SemesterChineseName . "%/%' ";
   $SQL .= "and visible = '1' ";
    
   $Data = $MoodleMySQLCon->query($SQL);
   $Rows = $Data->fetchAll();
   $TotalMoodleCourseData = count($Rows);
       
   $W = 0;
   foreach($MoodleMySQLCon->query($SQL) as $MoodleMySQLRow)
   {
    $MoodleMergeFullCourseIDNumber[$W] = $MoodleMySQLRow["idnumber"];
    $MoodleMergeFullCourseName[$W] = $MoodleMySQLRow["fullname"];
      
    $W++;
   }
      
   for($i=0;$i<$TotalMoodleCourseData;$i++)
   {
    $CheckMoodleMargeCourse[$i] = "N"; 
   }
      
   for($i=0;$i<$TotalSourceCourseData;$i++)
   {
    $SourceUnMargeCourseIDNumber = $AllMergeFullCourseIDNumber[$i];
      
    for($j=0;$j<$TotalMoodleCourseData;$j++)
    {
     $MoodleCourseIDNumber = $MoodleMergeFullCourseIDNumber[$j];
       
     if(strcmp($SourceUnMargeCourseIDNumber,$MoodleCourseIDNumber)==0)
     {
      $CheckMoodleMargeCourse[$j] = "Y";
     }
    }
   }
      
   for($i=0;$i<$TotalMoodleCourseData;$i++)
   {
    $CheckData = $CheckMoodleMargeCourse[$i];
      
    if(strcmp($CheckData,"N")==0)
    {
     $SQL = "select distinct idnumber ";
     $SQL .= "from subformoodle ";
     $SQL .= "where idnumber like '" . $SemesterChineseName . "%' ";
       
     $Data = $SchoolAffairsMySQLCon->query($SQL);
     $Rows = $Data->fetchAll();
     $TotalCheckSourceCourseData = count($Rows);
       
     $W = 0;
     foreach($SchoolAffairsMySQLCon->query($SQL) as $SchoolAffairsMySQLRow)
     {
      $AllSourceCourseIDNumber[$W] = $SchoolAffairsMySQLRow["idnumber"];
       
      $W++;
     }
       
     $MoodleCourseIDNumber = $MoodleMergeFullCourseIDNumber[$i];
       
//================隱藏合併課程================Start=================
       
     $SQL = "update mdl_course ";
     $SQL .= "set visible = '0', visibleold = '0' ";
     $SQL .= "where visible = '1' ";
     $SQL .= "and idnumber = '" . $MoodleCourseIDNumber . "' ";
        
     $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
     $MoodleMySQLRS->execute();
      
     $Msg = "解除合併並隱藏的課程： " . $MoodleMergeFullCourseName[$i];  
       
     echo $Msg . "<br>";
      
     $LogMsg = $Msg . "\r\n";
       
     fwrite($fp, $LogMsg);
       
//================隱藏合併課程================End===================
        
     $CourseSemesterIDnumber = explode("_", $MoodleCourseIDNumber);
     $SingleCourseIDnumber = explode("/", $CourseSemesterIDnumber[1]);
        
     $TotalSingleCourse = count($SingleCourseIDnumber);
       
//==============開啟未停開的單獨課程==============Start=================
        
     for($j=0;$j<$TotalSingleCourse;$j++)
     {
      $EachCourseIDNumber = $CourseSemesterIDnumber[0] . "_" . $SingleCourseIDnumber[$j];
          
      for($k=0;$k<$TotalCheckSourceCourseData;$k++)
      {
       $UnMargeSourceCourseIDNumber = $AllSourceCourseIDNumber[$k];
         
       if(strcmp($EachCourseIDNumber,$UnMargeSourceCourseIDNumber)==0)
       {
        $SQL = "update mdl_course ";
        $SQL .= "set visible = '1', visibleold = '1' ";
        $SQL .= "where visible = '0' ";
        $SQL .= "and idnumber = '" . $EachCourseIDNumber . "' ";
         
        $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
        $MoodleMySQLRS->execute();
          
        $SQL = "select fullname ";
        $SQL .= "from mdl_course ";
        $SQL .= "where idnumber = '" . $EachCourseIDNumber . "' ";
           
        foreach($MoodleMySQLCon->query($SQL) as $MoodleMySQLRow)
        {
         $EachCourseName = $MoodleMySQLRow["fullname"];
        }
         
        $Msg = "開啟未停開的單獨課程： " . $EachCourseName;  
         
        echo $Msg . "<br>";
          
        $LogMsg = $Msg . "\r\n";
          
        fwrite($fp, $LogMsg);
       }
      }
     }
      
//==============開啟未停開的單獨課程==============End===================
      
    }	  
   } 	  
       
//==========隱藏解除合併的課程和開啟未停開的單獨課程=======End============
      
   $SQL = "select idnumber ";
   $SQL .= "from mdl_course ";
   $SQL .= "where visible = '0' ";
   $SQL .= "and idnumber like '" . $SemesterChineseName . "%/%' ";
      
   $W=0; 
   foreach($MoodleMySQLCon->query($SQL) as $MoodleMySQLRow)
   {
    $HideMergeCourseIDNumber[$W] = $MoodleMySQLRow["idnumber"];
      
    $W++;
   } 
     
   $TotalHideMergeCourse = count($HideMergeCourseIDNumber);
      
//==================恢復合併課程============Start=========================
       
   for($i=0;$i<$TotalSourceCourseData;$i++)
   {
    $GetSourceMergeCourseIDNumber = $MergeFullCourseIDNumber[$i];
      
    for($j=0;$j<$TotalHideMergeCourse;$j++)
    {
     $GetMoodleHideMergeCourseIDNumber = $HideMergeCourseIDNumber[$j];
         
     if(strcmp($GetSourceMergeCourseIDNumber,$GetMoodleHideMergeCourseIDNumber)==0)
     {
       
//==============開啟被隱藏的合併課程==============Start=================
        
      $SQL = "update mdl_course ";
      $SQL .= "set visible = '1', visibleold = '1' ";
      $SQL .= "where visible = '0' ";
      $SQL .= "and idnumber = '" . $HideMergeCourseIDNumber[$j] . "' ";
        
      $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
      $MoodleMySQLRS->execute();
       
      $SQL = "select fullname ";
      $SQL .= "from mdl_course ";
      $SQL .= "where idnumber = '" . $HideMergeCourseIDNumber[$j] . "' ";
        
      foreach($MoodleMySQLCon->query($SQL) as $MoodleMySQLRow)
      {
       $MergeCourseName = $MoodleMySQLRow["fullname"];
      }
        
      $Msg = "開啟被隱藏的合併課程： " . $MergeCourseName;
        
      echo $Msg . "<br>";
        
      $LogMsg = $Msg . "\r\n";
       
      fwrite($fp, $LogMsg);
       
//==============開啟被隱藏的合併課程==============End===================
        
      $MoodleCourseIDNumber = $HideMergeCourseIDNumber[$j];   
       
      $CourseSemesterIDnumber = explode("_", $MoodleCourseIDNumber);
      $SingleCourseIDnumber = explode("/", $CourseSemesterIDnumber[1]);
         
      $TotalSingleCourse = count($SingleCourseIDnumber);
         
//============隱藏因解除合併開啟的單獨課程============Start=================
          
      for($k=0;$k<$TotalSingleCourse;$k++)
      {
       $EachCourseIDNumber = $CourseSemesterIDnumber[0] . "_" . $SingleCourseIDnumber[$k];
          
       $SQL = "update mdl_course ";
       $SQL .= "set visible = '0', visibleold = '0' ";
       $SQL .= "where visible = '1' ";
       $SQL .= "and idnumber = '" . $EachCourseIDNumber . "' ";
          
       $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
       $MoodleMySQLRS->execute();
         
       $SQL = "select fullname ";
       $SQL .= "from mdl_course ";
       $SQL .= "where idnumber = '" . $EachCourseIDNumber . "' ";
         
       foreach($MoodleMySQLCon->query($SQL) as $MoodleMySQLRow)
       {
        $EachCourseName = $MoodleMySQLRow["fullname"];
       }
         
       $Msg = "隱藏因解除合併開啟的單獨課程： " . $EachCourseName;  
         
       echo $Msg . "<br>";
         
       $LogMsg = $Msg . "\r\n";
         
       fwrite($fp, $LogMsg);
      }
        
      //=======隱藏因解除合併開啟的單獨課程============End================
        
     }
    }
   }	   
      
  //==================恢復合併課程============End===========================
      
   unset($SplitMergeCourseIDNumber);	 
     
   for($i=0;$i<$TotalSourceCourseData;$i++)
   {
      
   //============組合課程大綱超連結======Start============================
      
    $SplitMergeCourseIDNumber  = explode(",", $AllMergeCourseIDNumber[$i]);
       
    $TotalMergeCourse = count($SplitMergeCourseIDNumber);
      
    $ChineseCourseOutlineHTML = "";
    $FullCourseOutlineHTML = "";
      
    for($j=0;$j<$TotalMergeCourse;$j++)
    {
     $SQL = "select distinct fullname, shortname ";
     $SQL .= "from subformoodle ";       //取得正式資料Table
     //$SQL .= "from subformoodle_testAP ";  //取得測試資料Table
     $SQL .= "where idnumber = '" . $SplitMergeCourseIDNumber[$j] . "' ";
       
     $Data = $SchoolAffairsMySQLCon->query($SQL);
     $Rows = $Data->fetchAll();
     $TotalCourseData = count($Rows);
       
     foreach($SchoolAffairsMySQLCon->query($SQL) as $SchoolAffairsMySQLRow)
     {
      $SourceCourseFullChineseName = $SchoolAffairsMySQLRow["fullname"];
      $SourceCourseFullEnglishName = $SchoolAffairsMySQLRow["shortname"];
     }
       
     $FullCourseIDNumberPart = explode("_", $SplitMergeCourseIDNumber[$j]);
       
     $CourseIDNumber = $FullCourseIDNumberPart[1];
       
     $CourseNum = substr($CourseIDNumber, 0, 6);
     $CourseGop = substr($CourseIDNumber, 6, 2);
     $CourseS = substr($CourseIDNumber, 8, 1);
       
     $CourseOutlineURL = "https://newdoc.nccu.edu.tw/teaschm/" . $SemesterChineseName;
     $CourseOutlineURL .= "/schmPrv.jsp-yy=" . $ChineseYear . "&smt=" .  $SubSemester;
     $CourseOutlineURL .= "&num=" . $CourseNum . "&gop=" . $CourseGop . "&s=" . $CourseS . ".html";
      
     $SplitSourceCourseFullEnglishName = explode("_", $SourceCourseFullEnglishName);
     $SourceCoursePartEnglishName = $SplitSourceCourseFullEnglishName[2];
       
     $SplitSourceCoursePartEnglishName = explode("'", $SourceCoursePartEnglishName);
        
     $DataSize = count($SplitSourceCoursePartEnglishName);
       
     $ApostropheCh = "\'";
       
     $CourseEnglishName = "";
       
     for($n=0;$n<$DataSize;$n++)
     {
      if($n==0)   
      {
       $CourseEnglishName .= $SplitSourceCoursePartEnglishName[$n];
      }
      else
      {
       $CourseEnglishName .= $ApostropheCh . $SplitSourceCoursePartEnglishName[$n];
      }
     }
        
     $ChineseCourseOutlineHTML .= "<p><a href=\"" .$CourseOutlineURL  . "\" target=\"_blank\">";
     $ChineseCourseOutlineHTML .= $SourceCourseFullChineseName . "_課程大綱_course outline</a></p>";
       
     $CourseFullName = $SourceCourseFullChineseName . "_" . $CourseEnglishName;
      
     $FullCourseOutlineHTML .= "<p><a href=\"" .$CourseOutlineURL  . "\" target=\"_blank\">";
     $FullCourseOutlineHTML .= $CourseFullName . "_課程大綱_course outline</a></p>";
      
    }
       
   //============組合課程大綱超連結======End==============================
      
   //================隱藏合併前的單獨課程======Start=========================
        
   //============隱藏主要合併課程的單獨課程============Start=================
       
    $SQL = "update mdl_course ";
    $SQL .= "set visible = '0', visibleold = '0' ";
    $SQL .= "where visible = '1' ";
    $SQL .= "and idnumber = '" . $SourceCourseIDNumber[$i] . "' ";
       
    $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
    $MoodleMySQLRS->execute();
       
   //============隱藏主要合併課程的單獨課程============End===================
      
   //============隱藏被合併課程的單獨課程============Start===================
        
    $SubMargeCourseIDNumber = $SourceMergeCourseIDNumber[$i];
      
    $SplitSourceMergeCourseIDNumber  = explode(",", $SourceMergeCourseIDNumber[$i]);   
      
    $TotalSlaveMargeCourse = count($SplitSourceMergeCourseIDNumber);
      
    for($j=0;$j<$TotalSlaveMargeCourse;$j++)     
    {
     $SQL = "update mdl_course ";
     $SQL .= "set visible = '0', visibleold = '0' ";
     $SQL .= "where visible = '1' ";
     $SQL .= "and idnumber = '" . $SplitSourceMergeCourseIDNumber[$j] . "' ";
       
     $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
     $MoodleMySQLRS->execute();
    }
        
//============隱藏被合併課程的單獨課程============End=====================
     
	 
//==============建立合併課程==================Start============
       
    $SQL = "select count(*) as TotalCourse ";
    $SQL .= "from mdl_course ";
    $SQL .= "where idnumber = '" . $MergeFullCourseIDNumber[$i] . "' ";
        
    $Data = $MoodleMySQLCon->query($SQL);
    $Rows = $Data->fetch();
    $FindMoodleMergeCourse = $Rows[0];
	  
//===========確認是否有建立合併課程==============Start==========
       
    if($FindMoodleMergeCourse==0)
    {
		
     $SQL = "select category ";
     $SQL .= "from mdl_course ";
     $SQL .= "where idnumber = '" . $SourceCourseIDNumber[$i] . "' ";
       
     foreach($MoodleMySQLCon->query($SQL) as $MoodleMySQLRow)
     {
      $CourseCategoryID = $MoodleMySQLRow["category"];
     }
        
//============建立合併課程======Start============================
        
   //字串長度太長，透過Moodle API 寫進資料庫時會出現錯誤。
   //先以較短的字串透過Moodle API 建立課程後，再以 SQL update 指令直接更新資料庫相關內容。
       
     $CourseData = new stdClass();
       
     $CourseData->fullname = $MergeFullCourseIDNumber[$i];
     $CourseData->shortname = $MergeFullCourseIDNumber[$i];
     $CourseData->idnumber = $MergeFullCourseIDNumber[$i];
     $CourseData->category  = $CourseCategoryID;
     $CourseData->format  = "weeks";
     $CourseData->newsitems = "5";
     $CourseData->startdate = $StartDateTimestamp;
     $CourseData->maxbytes = "52428800";  //50MB
     //$CourseData->maxbytes = "104857600"; //100MB
     $CourseData->visible = "1";
     $CourseData->visibleold = "1";
     $CourseData->enablecompletion = "1";
     $CourseData->showactivitydates = "1";
     $CourseData->summaryformat = "1";
     $CourseData->summary = $ChineseCourseOutlineHTML;
       
     create_course($CourseData);
       
     $SQL = "update mdl_course ";
     $SQL .= "set enddate = '" . $EndDateTimestamp . "' ";
     $SQL .= "where idnumber = '" . $MergeFullCourseIDNumber[$i] . "' ";
        
     $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
     $MoodleMySQLRS->execute();
       
     $SQL = "select id ";
     $SQL .= "from mdl_course ";
     $SQL .= "where idnumber = '" . $MergeFullCourseIDNumber[$i] . "' ";
       
     $Data = $MoodleMySQLCon->query($SQL); 
       
     foreach($Data as $row)
     {
      $MoodleDBCourseID = $row["id"];
     }
         
//========「根據單元節數來計算結束日期」的選項，預設為「選取」，老師調整週數後，課程結束日期會跑掉，造成學生找不到課程；因此把選項改為「取消選取」======Start========
		  
       $SQL = "update mdl_course_format_options ";
       $SQL .= "set value = '0' ";
       $SQL .= "where courseid = '" . $MoodleDBCourseID . "' ";
       $SQL .= "and format = 'weeks' ";
       $SQL .= "and name = 'automaticenddate' ";
          
       $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
       $MoodleMySQLRS->execute();
           
//========「根據單元節數來計算結束日期」的選項，預設為「選取」，老師調整週數後，課程結束日期會跑掉，造成學生找不到課程；因此把選項改為「取消選取」======End==========
         
//============新增「課程大綱」的URL於「課程摘要」，並把課程名稱更新成完整名稱===========Start=============
         
     $SQL = "update mdl_course ";
     $SQL .= "set fullname = '" . $AllMergeFullCourseName[$i] . "', shortname = '" . $AllMergeFullCourseName[$i] . "', summary = '" . $FullCourseOutlineHTML . "' ";
     $SQL .= "where id = '" . $MoodleDBCourseID . "' ";
       
     $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
     $MoodleMySQLRS->execute();
       
//============新增「課程大綱」的URL於「課程摘要」，並把課程名稱更新成完整名稱===========End===============
       
//==========取得Moodle系統設定（全域）的開課最大週數======Start========
       
     $SQL = "select value ";
     $SQL .= "from mdl_config_plugins ";
     $SQL .= "where plugin = 'moodlecourse' ";
     $SQL .= "and name = 'numsections' ";
       
     foreach($MoodleMySQLCon->query($SQL) as $MoodleMySQLRow)
     {
      $MoodleSectionNum = $MoodleMySQLRow["value"];
     }
       
//==========取得Moodle系統設定（全域）的開課最大週數======End==========
        
//========根據系統設定每學期最大週數，建立各週次的Section並設定每週名稱，讓授課教師於各個Section建立課程活動======Start========
		
     for($j=0;$j<$MoodleSectionNum;$j++)
     {
      $SectionNum = $j + 1;
        
      if($SectionNum==1)
      {
       $WeekName = "Week 01 " . $FirstWeekStartDate . " ~ " . $FirstWeekEndDate;
      }
      else
      {
       $DiffWeek = $j;
       $DiffDay = 7 * $DiffWeek;
       $MoveDay = "+" . $DiffDay . " day";
         
       $WeekStartDateTimestamp = strtotime($FirstWeekStartDate); 	  
       $WeekEndDateTimestamp = strtotime($FirstWeekEndDate);
        
       $WeekStartDate = date("Y/m/d", strtotime($MoveDay, $WeekStartDateTimestamp));
       $WeekEndDate = date("Y/m/d", strtotime($MoveDay, $WeekEndDateTimestamp));
         
       if($SectionNum<10)
       {
        $WeekTitle = "Week 0" . $SectionNum . " ";
       }
       else
       {
        $WeekTitle = "Week " . $SectionNum . " ";
       }
        
       $WeekName = $WeekTitle . $WeekStartDate . " ~ " . $WeekEndDate;
      }
        
      $SQL = "insert into mdl_course_sections ";
      $SQL .= "(course, section, name, summary, summaryformat, sequence, visible, timemodified) ";
      $SQL .= "values ";
      $SQL .= "('" . $MoodleDBCourseID . "', '" . $SectionNum . "', '" . $WeekName . "', '', '1', '', '1', '" . time() . "') ";
       
      $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
      $MoodleMySQLRS->execute();
     }
       
//========根據系統設定每學期最大週數，建立各週次的Section並設定每週名稱，讓授課教師於各個Section建立課程活動======End==========
	   
//============建立合併課程======End============================
       
//==========更改「公告」討論區模組名稱由中文改為中文與英文並列========Start=======
       
     $SQL = "update mdl_forum ";
     $SQL .= "set name = '公告 Announcements' ";
     $SQL .= "where course = '" . $MoodleDBCourseID . "' ";
       
     $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
     $MoodleMySQLRS->execute();
        
//==========更改「公告」討論區模組名稱由中文改為中文與英文並列========End=========
       
//========新增「課程大綱」的URL模組於「課程公告」之後===========Start=============
        
     $SplitMergeCourseIDNumber  = explode(",", $AllMergeCourseIDNumber[$i]);
       
     $TotalMergeCourse = count($SplitMergeCourseIDNumber);
        
     for($j=0;$j<$TotalMergeCourse;$j++)
     {
      $SQL = "select distinct fullname, shortname ";
      $SQL .= "from subformoodle ";       //取得正式資料Table
      //$SQL .= "from subformoodle_testAP ";  //取得測試資料Table
      $SQL .= "where idnumber = '" . $SplitMergeCourseIDNumber[$j] . "' ";
        
      $Data = $SchoolAffairsMySQLCon->query($SQL);
      $Rows = $Data->fetchAll();
      $TotalCourseData = count($Rows);
        
      foreach($SchoolAffairsMySQLCon->query($SQL) as $SchoolAffairsMySQLRow)
      {
       $SourceCourseFullChineseName = $SchoolAffairsMySQLRow["fullname"];
       $SourceCourseFullEnglishName = $SchoolAffairsMySQLRow["shortname"];
      }
       
      $FullCourseIDNumberPart = explode("_", $SplitMergeCourseIDNumber[$j]);
        
      $CourseIDNumber = $FullCourseIDNumberPart[1];
         
      $CourseNum = substr($CourseIDNumber, 0, 6);
      $CourseGop = substr($CourseIDNumber, 6, 2);
      $CourseS = substr($CourseIDNumber, 8, 1);
          
      $CourseOutlineURL = "https://newdoc.nccu.edu.tw/teaschm/" . $SemesterChineseName;
      $CourseOutlineURL .= "/schmPrv.jsp-yy=" . $ChineseYear . "&smt=" .  $SubSemester;
      $CourseOutlineURL .= "&num=" . $CourseNum . "&gop=" . $CourseGop . "&s=" . $CourseS . ".html";
        
      $SplitSourceCourseFullEnglishName = explode("_", $SourceCourseFullEnglishName);
      $SourceCoursePartEnglishName = $SplitSourceCourseFullEnglishName[2];
        
      $SplitSourceCoursePartEnglishName = explode("'", $SourceCoursePartEnglishName);
       
      $DataSize = count($SplitSourceCoursePartEnglishName);
       
      $ApostropheCh = "\'";
         
      $CourseEnglishName = "";
        
      for($n=0;$n<$DataSize;$n++)
      {
       if($n==0)   
       {
        $CourseEnglishName .= $SplitSourceCoursePartEnglishName[$n];
       }
       else
       {
        $CourseEnglishName .= $ApostropheCh . $SplitSourceCoursePartEnglishName[$n];
       }
      }
         
      $CourseFullName = $SourceCourseFullChineseName . "_" . $CourseEnglishName;
         
      $CourseOutlineName = $CourseFullName . "_課程大綱_course outline";
         
      $SQL = "insert into mdl_url ";
      $SQL .= "(course, name, intro, introformat, externalurl, display, displayoptions, parameters, timemodified) ";
      $SQL .= "values ";
      $SQL .= "('" . $MoodleDBCourseID . "', '" . $CourseOutlineName . "', '', '1', '" . $CourseOutlineURL . "', ";
      $SQL .= "'3', 'a:0:{}', 'a:0:{}', '" . time() . "') ";
        
      $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
      $MoodleMySQLRS->execute();
        
      $SQL = "select id, timemodified ";
      $SQL .= "from mdl_url ";
      $SQL .= "where course = '" . $MoodleDBCourseID . "' ";
      $SQL .= "and name = '" . $CourseOutlineName . "' ";
       
      foreach($MoodleMySQLCon->query($SQL) as $row)
      {
       $DBURLItemID = $row["id"];
       $DBURLModifyTime = $row["timemodified"];
      }
        
      $SQL = "select id, sequence ";
      $SQL .= "from mdl_course_sections ";
      $SQL .= "where course = '" . $MoodleDBCourseID . "' ";
      $SQL .= "and section = '0' ";
        
      foreach($MoodleMySQLCon->query($SQL) as $row)
      {
       $DBCourseSectionID = $row["id"];
       $DBCourseSectionSequence = $row["sequence"];
      }
        
      $SQL = "select id ";
      $SQL .= "from mdl_modules ";
      $SQL .= "where name = 'url' ";
        
      foreach($MoodleMySQLCon->query($SQL) as $row)
      {
       $DBModuleID = $row["id"];
      }
        
      $SQL = "insert into mdl_course_modules ";
      $SQL .= "(course, module, instance, section, idnumber, added) ";
      $SQL .= "values ";
      $SQL .= "('" . $MoodleDBCourseID . "', '" . $DBModuleID . "', '" . $DBURLItemID . "', ";
      $SQL .= "'" . $DBCourseSectionID . "', '', '" . $DBURLModifyTime . "') ";
         
      $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
      $MoodleMySQLRS->execute();
         
      $SQL = "select id ";
      $SQL .= "from mdl_course_modules ";
      $SQL .= "where module = '" . $DBModuleID . "' ";
      $SQL .= "and course = '" . $MoodleDBCourseID . "' ";
      $SQL .= "and instance = '" . $DBURLItemID . "' ";
        
      foreach($MoodleMySQLCon->query($SQL) as $row)
      {
       $DBCourseModuleID = $row["id"];
      }
         
      $NewCourseSectionSequence = $DBCourseSectionSequence . "," . $DBCourseModuleID;
         
      $SQL = "update mdl_course_sections  ";
      $SQL .= "set sequence = '" . $NewCourseSectionSequence . "' ";
      $SQL .= "where course = '" . $MoodleDBCourseID . "' ";
      $SQL .= "and section = '0' ";
        
      $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
      $MoodleMySQLRS->execute();
     }
       
//========新增「課程大綱」的URL模組於「課程公告」之後===========End===============
        
     $SQL = "select id ";
     $SQL .= "from mdl_context ";
     $SQL .= "where contextlevel = '50' ";
     $SQL .= "and instanceid = '" . $MoodleDBCourseID . "' ";
      
     foreach($MoodleMySQLCon->query($SQL) as $row)
     {
      $DBCourseContextID = $row["id"];
     }
      
//============新增「導覽」區塊==========Start==========
       
     $SQL = "insert into mdl_block_instances ";
     $SQL .= "(blockname, parentcontextid, showinsubcontexts, requiredbytheme, pagetypepattern, defaultregion, defaultweight, configdata, timecreated, timemodified) ";
     $SQL .= "values ";
     $SQL .= "('navigation', '" . $DBCourseContextID . "', '1', '1', 'course-view-*', 'side-pre', '2', '', '" . time() . "', '" . time() . "') ";
        
     $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
     $MoodleMySQLRS->execute();
      
//============新增「導覽」區塊==========End============    
       
//============新增「最新公告」區塊==========Start==========
       
     $SQL = "insert into mdl_block_instances ";
     $SQL .= "(blockname, parentcontextid, showinsubcontexts, pagetypepattern, defaultregion, defaultweight, configdata, timecreated, timemodified) ";
     $SQL .= "values ";
     $SQL .= "('news_items', '" . $DBCourseContextID."', '0', 'course-view-*', 'side-pre', '0', '', '" . time() . "', '" . time() . "') ";
       
     $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
     $MoodleMySQLRS->execute();
       
//============新增「最新公告」區塊==========End============		  
       
//============新增「活動」區塊==========Start==========
       
     $SQL = "insert into mdl_block_instances ";
     $SQL .= "(blockname, parentcontextid, showinsubcontexts, pagetypepattern, defaultregion, defaultweight, configdata, timecreated, timemodified) ";
     $SQL .= "values ";
     $SQL .= "('activity_modules', '" . $DBCourseContextID . "', '0', 'course-view-*', 'side-pre', '0', '', '" . time() . "', '" . time() . "') ";
      
     $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
     $MoodleMySQLRS->execute();
       
//============新增「活動」區塊==========End============
       
//============新增「未來事件」區塊==========Start==========
       
     $SQL = "insert into mdl_block_instances ";
     $SQL .= "(blockname, parentcontextid, showinsubcontexts, pagetypepattern, defaultregion, defaultweight, configdata, timecreated, timemodified) ";
     $SQL .= "values ";
     $SQL .= "('calendar_upcoming', '" . $DBCourseContextID . "', '0', 'course-view-*', 'side-pre', '0', '', '" . time() . "', '" . time() . "') ";
       
     $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
     $MoodleMySQLRS->execute();
      
//============新增「未來事件」區塊==========End============
       
//============新增「搜尋所有討論區」區塊==========Start==========
       
     $SQL = "insert into mdl_block_instances ";
     $SQL .= "(blockname, parentcontextid, showinsubcontexts, pagetypepattern, defaultregion, defaultweight, configdata, timecreated, timemodified) ";
     $SQL .= "values ";
     $SQL .= "('search_forums', '" . $DBCourseContextID . "', '0', 'course-view-*', 'side-pre', '2', '', '" . time() . "', '" . time() . "') ";
       
     $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
     $MoodleMySQLRS->execute();
       
//============新增「搜尋所有討論區」區塊==========End============          
        
//=============頁面定位「導覽」區塊==========Start==========
       
     $SQL = "insert into mdl_block_positions ";
     $SQL .= "(blockinstanceid, contextid, pagetype, subpage, visible, region, weight) ";
     $SQL .= "values ";
     $SQL .= "('9', '" . $DBCourseContextID . "', 'course-view-weeks', '', '1', 'side-pre', '-6') ";
       
     $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
     $MoodleMySQLRS->execute();
       
//=============頁面定位「導覽」區塊==========End============
       
//=============頁面定位「最新公告」區塊==========Start==========
       
     $SQL = "select id ";
     $SQL .= "from mdl_block_instances ";
     $SQL .= "where parentcontextid = '" . $DBCourseContextID . "' ";
     $SQL .= "and blockname = 'news_items' ";
       
     foreach($MoodleMySQLCon->query($SQL) as $MoodleMySQLRow)
     {
      $DBBlockInstanceID = $MoodleMySQLRow["id"];
     }
       
     $SQL = "insert into mdl_block_positions ";
     $SQL .= "(blockinstanceid, contextid, pagetype, subpage, visible, region, weight) ";
     $SQL .= "values ";
     $SQL .= "('" . $DBBlockInstanceID . "', '" . $DBCourseContextID . "', ";
     $SQL .= "'course-view-weeks', '', '1', 'side-pre', '-5') ";
      
     $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
     $MoodleMySQLRS->execute();
       
//=============頁面定位「最新公告」區塊==========End============          
       
//=============頁面定位「系統管理」區塊==========Start==========
        
     $SQL = "insert into mdl_block_positions ";
     $SQL .= "(blockinstanceid, contextid, pagetype, subpage, visible, region, weight) ";
     $SQL .= "values ";
     $SQL .= "('10', '" . $DBCourseContextID . "', 'course-view-weeks', '', '1', 'side-pre', '-4') ";
       
     $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
     $MoodleMySQLRS->execute();
       
//=============頁面定位「系統管理」區塊==========End============
       
//=============頁面定位「活動」區塊==========Start==========
       
     $SQL = "select id from mdl_block_instances ";
     $SQL .= "where parentcontextid = '" . $DBCourseContextID . "' ";
     $SQL .= "and blockname = 'activity_modules' ";
       
     foreach($MoodleMySQLCon->query($SQL) as $MoodleMySQLRow)
     {
      $DBBlockInstanceID = $MoodleMySQLRow["id"];
     }
       
     $SQL = "insert into mdl_block_positions ";
     $SQL .= "(blockinstanceid, contextid, pagetype, subpage, visible, region, weight) ";
     $SQL .= "values ";
     $SQL .= "('" . $DBBlockInstanceID . "', '" . $DBCourseContextID . "', ";
     $SQL .= "'course-view-weeks', '', '1', 'side-pre', '-3') ";
      
     $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
     $MoodleMySQLRS->execute();
      
//=============頁面定位「活動」區塊==========End============
       
//=============頁面定位「未來事件」區塊==========Start==========
       
     $SQL = "select id from mdl_block_instances ";
     $SQL .= "where parentcontextid = '" . $DBCourseContextID . "' ";
     $SQL .= "and blockname = 'calendar_upcoming' ";
      
     $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
     $MoodleMySQLRS->execute();
      
     $Rows = $MoodleMySQLRS->fetchAll(PDO::FETCH_ASSOC);
       
     foreach($MoodleMySQLCon->query($SQL) as $MoodleMySQLRow)
     {
      $DBBlockInstanceID = $MoodleMySQLRow["id"];
     }
       
     $SQL = "insert into mdl_block_positions ";
     $SQL .= "(blockinstanceid, contextid, pagetype, subpage, visible, region, weight) ";
     $SQL .= "values ";
     $SQL .= "('" . $DBBlockInstanceID . "', '" . $DBCourseContextID . "', ";
     $SQL .= "'course-view-weeks', '', '1', 'side-pre', '0') ";
       
     $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
     $MoodleMySQLRS->execute();
      
//=============頁面定位「未來事件」區塊==========End============
       
     $Msg = "建立合併課程： " . $AllMergeFullCourseName[$i];  
      
     echo $Msg . "<br>";
      
     $LogMsg = $Msg . "\r\n";
      
     fwrite($fp, $LogMsg);
       
//========使用者填單時選擇學生名單不需要做課程分組====Start============
        
     if(strcmp($SourceMergeCourseType[$i],"1")==0)
     {
      $Msg = "「" . $AllMergeFullCourseName[$i] . "」不建立合併課程的課程分組…………………";  
       
      echo $Msg . "<br>";
         
      $LogMsg = "「" . $AllMergeFullCourseName[$i] . "」不建立合併課程的課程分組…………………" . "\r\n";
        
      fwrite($fp, $LogMsg);
     } 
     
//========使用者填單時選擇學生名單不需要做課程分組====End==============
      
//========使用者填單時選擇學生名單需要做課程分組====Start==============
       
     if(strcmp($SourceMergeCourseType[$i],"2")==0)
     {
      $AllFullCourseIDNumber = $MergeFullCourseIDNumber[$i];
       
      $AllSemesterSubMergeCourseIDNumber = explode("_", $AllFullCourseIDNumber);
        
      $AllSubMergeCourseIDNumber = explode("/", $AllSemesterSubMergeCourseIDNumber[1]);
        
      $TotalMerageCourse = count($AllSubMergeCourseIDNumber);
         
          //==========拆解合併課程的Course ID Number後進行課程分組=========Start========
      for($j=0;$j<$TotalMerageCourse;$j++)
      {
         //拆解後，所有合併課程都沒有學期代號，需補上。	  
       $FullCourseIDNumber = $SemesterChineseName . "_" . $AllSubMergeCourseIDNumber[$j];
        
       $SQL = "select id ";
       $SQL .= "from mdl_groups ";
       $SQL .= "where courseid = '" . $MoodleDBCourseID . "' ";
       $SQL .= "and idnumber = '" . $FullCourseIDNumber . "' ";
         
       $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
       $MoodleMySQLRS->execute();
         
       $Rows = $MoodleMySQLRS->fetchAll(PDO::FETCH_ASSOC);
        
       $TotalMergeCourseData = $MoodleMySQLRS->rowCount();
        
//===============建立課程分組============Start================
         
       if($TotalMergeCourseData==0)
       {
        echo "「" . $AllMergeFullCourseName[$i] .  "」建立合併課程的課程分組…………………" . "<br>";
          
        $GroupData = new stdClass();
 
        $GroupData->courseid = $MoodleDBCourseID;
        $GroupData->idnumber = $FullCourseIDNumber;
        $GroupData->name = $FullCourseIDNumber;
        $GroupData->descriptionformat = "1";
           
        $newgroupid = groups_create_group($GroupData);
          
        $Msg = "建立 &nbsp;&nbsp;「" . $AllMergeFullCourseName[$i] . "」&nbsp;&nbsp;" . "的課程分組： " . $FullCourseIDNumber;  
         
        echo $Msg . "<br>";
	     
        $LogMsg = "建立  「" . $AllMergeFullCourseName[$i] . "」  的課程分組： " . $FullCourseIDNumber . "\r\n";
	     
        fwrite($fp, $LogMsg);
       }  //===============建立課程分組============End=================
      }  //==========拆解合併課程的Course ID Number後進行課程分組=========End==========
     }  //========使用者填單時選擇學生名單需要做課程分組====End================
    }  //===========確認是否有建立合併課程==============End============
     
//==============建立合併課程==================End==============    
	  
   }

   fclose($fp);
 
  }
  catch(PDOException $e)
  {
   echo $e->getMessage(); 
  }
   
 }
   
 function SemesterEndDate($Year, $SubSemester)
 { 
  switch($SubSemester)
  {
   case 1:
    $NewYear = $Year + 1;
    $SemesterEndDate = $NewYear . "-01-31 23:59";
    $EndDateTimestamp = strtotime($SemesterEndDate);
   break;
   case 2:
    $SemesterEndDate = $Year . "-07-31 23:59";
    $EndDateTimestamp = strtotime($SemesterEndDate);
   break;
  }
     
  return $EndDateTimestamp;
 }	 
     
 if($SameSemesterCName==true && $SameSemesterEName==true)
 {
  $SemesterChineseName = $SemesterChineseName01;
  $SemesterEnglishName = $SemesterEnglishName01;
  $SubSemester = $SubSemester01;
  $SemesterFullName = $SemesterChineseName . " - " . $SemesterEnglishName;
      
  $EndDateTimestamp = SemesterEndDate($Year, $SubSemester);	 
	 
  echo "SemesterName = " . $SemesterFullName . "<br>";
      
  MergeCourse($SchoolAffairsDBHost, $SchoolAffairsDBPort, $SchoolAffairsDBName, $SchoolAffairsDBUsername, $SchoolAffairsDBPassword,$MoodleDBHost, $MoodleDBPort, $MoodleDBName, $MoodleDBUsername, $MoodleDBPassword, $SemesterChineseName, $SemesterFullName, $EndDateTimestamp, $ChineseYear, $SubSemester);
 }
 else
 {
  $SemesterChineseName = $SemesterChineseName01;
  $SemesterEnglishName = $SemesterEnglishName01;
  $SubSemester = $SubSemester01;
  $SemesterFullName = $SemesterChineseName . " - " . $SemesterEnglishName;
	  
  $EndDateTimestamp = SemesterEndDate($Year, $SubSemester);
      
  echo "SemesterName = " . $SemesterFullName . "<br>";
      
  MergeCourse($SchoolAffairsDBHost, $SchoolAffairsDBPort, $SchoolAffairsDBName, $SchoolAffairsDBUsername, $SchoolAffairsDBPassword,$MoodleDBHost, $MoodleDBPort, $MoodleDBName, $MoodleDBUsername, $MoodleDBPassword, $SemesterChineseName, $SemesterFullName, $EndDateTimestamp, $ChineseYear, $SubSemester);
       
  echo "<br><br>";
      
  $SemesterChineseName = $SemesterChineseName02;
  $SemesterEnglishName = $SemesterEnglishName02;
  $SubSemester = $SubSemester02;
     
  $SemesterFullName = $SemesterChineseName . " - " . $SemesterEnglishName;
      
  $EndDateTimestamp = SemesterEndDate($Year, $SubSemester);
	 
  echo "SemesterName = " . $SemesterFullName . "<br>";
      
  MergeCourse($SchoolAffairsDBHost, $SchoolAffairsDBPort, $SchoolAffairsDBName, $SchoolAffairsDBUsername, $SchoolAffairsDBPassword,$MoodleDBHost, $MoodleDBPort, $MoodleDBName, $MoodleDBUsername, $MoodleDBPassword, $SemesterChineseName, $SemesterFullName, $EndDateTimestamp, $ChineseYear, $SubSemester);
 }
   
?>
