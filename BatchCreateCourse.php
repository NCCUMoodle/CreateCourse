<?php
/**
* 此程式同步各系所的單獨課程，不包含合併課程。同步項目如下：
* 1. 建立新課程。
* 2. 隱藏停開課程。
*/

 require_once('../config.php');
 require_once($CFG->dirroot.'/course/lib.php');
 require_once('SemesterName.php');
    
 function CreateCourse($SchoolAffairsDBHost, $SchoolAffairsDBPort, $SchoolAffairsDBName, $SchoolAffairsDBUsername, $SchoolAffairsDBPassword,$MoodleDBHost, $MoodleDBPort, $MoodleDBName, $MoodleDBUsername, $MoodleDBPassword, $SemesterChineseName, $SemesterFullName, $EndDateTimestamp, $ChineseYear, $SubSemester)
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
      
   $SourceSemesterCategory = array();
   $SourceDeptCategoryName = array();
   $SourceCourseChineseName = array();
   $SourceCourseEnglishName = array();
   $SourceCourseIDNumber = array();
   $MoodleDBCategoryID = array();
   $MoodleCategoryName = array();
   $MoodleDepartmentName = array();
   $MoodleCourseIDNumber = array();
   $MoodleCourseName = array();
   $DisableMoodleCourse = array();
     
   $GetDateTime = date("Ymd_His");
     
   $FileName = "/var/moodlelog/CreateIndividualCoure/CreateIndividualCoure_" . $GetDateTime . ".log";
     
   $fp = fopen($FileName, "w");
	  
   $SQL = "select count(*) as TotalSemester from mdl_course_categories where name = '" . $SemesterFullName . "' ";
	  
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
       
    $SQL = "select startdate ";
    $SQL .= "from semesterdate ";
    $SQL .= "where semester = '" . $SemesterChineseName . "' ";
      
    foreach($SchoolAffairsMySQLCon->query($SQL) as $SchoolAffairsMySQLRow)
    {
     $SemesterStartDate = $SchoolAffairsMySQLRow["startdate"];
     $SemesterEndDate = $SchoolAffairsMySQLRow["enddate"];
    }
       
    $Msg = $SemesterChineseName . " 學期 開學日期：" . $SemesterStartDate;
      
    $Log = $Msg . "\r\n";
      
    echo $Msg . "<br>";
      
    fwrite($fp, $Log);
      
    $StartDateTimestamp = strtotime($SemesterStartDate);
            
    $StartWeekCode = date("w", $StartDateTimestamp);
      
    switch($StartWeekCode)
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
      
//===========從中繼資料庫取得該學期所有系所有申請的課程資料=======Start==============
      
   $SQL = "select distinct category, fullname, shortname, idnumber ";
   $SQL .= "from subformoodle ";       //取得正式資料Table
   //$SQL .= "from subformoodle_testAP ";  //取得測試資料Table
   $SQL .= "where idnumber like '" . $SemesterChineseName . "%' ";
   $SQL .= "and upd_source = 'APY' ";  //老師已申請開課
   $SQL .= "union ";
   $SQL .= "select distinct category, fullname, shortname, idnumber ";
   $SQL .= "from subformoodle ";       //取得正式資料Table
   //$SQL .= "from subformoodle_testAP ";  //取得測試資料Table
   $SQL .= "where idnumber like '" . $SemesterChineseName . "%' ";
   $SQL .= "and category like '%遠距教學收播通識科目%' ";  //遠距課程無授課老師可以申請，系統直接開課
   $SQL .= "order by idnumber ";
	  
   $Data = $SchoolAffairsMySQLCon->query($SQL);
   $Rows = $Data->fetchAll();
   $TotalSourceCourseData = count($Rows);
      
   $W = 0;
   foreach($SchoolAffairsMySQLCon->query($SQL) as $SchoolAffairsMySQLRow)
   {
    $SourceSemesterCategory[$W] = $SchoolAffairsMySQLRow["category"];
    $SourceCourseChineseName[$W] = $SchoolAffairsMySQLRow["fullname"];
    $SourceCourseEnglishName[$W] = $SchoolAffairsMySQLRow["shortname"];
    $SourceCourseIDNumber[$W] = $SchoolAffairsMySQLRow["idnumber"];
      
    $W++;
   }
      
//===========從中繼資料庫取得該學期所有系所有申請的課程資料=======End================
      
//===============建立各系所課程================Start======================
      
//=====拆出系所名稱，第一層為學期別。通識只有到第二層；其他課程的第二層為學院名稱，第三層為系所名稱=====Start=====
     
   for($i=0;$i<$TotalSourceCourseData;$i++)
   {
    $SplitSourceSemesterCategory = explode("/", $SourceSemesterCategory[$i]);
    $CategorySize = count($SplitSourceSemesterCategory);
      
    if($CategorySize==2)
    {
     $SourceDeptCategoryName[$i] = $SplitSourceSemesterCategory[1];
    }
      
    if($CategorySize==3)
    {
     $SourceDeptCategoryName[$i] = $SplitSourceSemesterCategory[2];
    }
   }
      
//=====拆出系所名稱，第一層為學期別。通識只有到第二層；其他課程的第二層為學院名稱，第三層為系所名稱=====End=======
      
//==========取得在Moodle上所有通識和系所類別的名稱和類別ID=========Start==============
      
   $SQL = "select id, name ";
   $SQL .= "from mdl_course_categories ";
   $SQL .= "where path like '/" . $SemesterCategoryID . "/%' ";
   $SQL .= "and depth = '2' ";
   $SQL .= "and name like '%通識%' ";
   $SQL .= "union ";
   $SQL .= "select id, name ";
   $SQL .= "from mdl_course_categories ";
   $SQL .= "where path like '/" . $SemesterCategoryID . "/%' ";
   $SQL .= "and depth = '3' ";
      
   $Data = $MoodleMySQLCon->query($SQL);
   $Rows = $Data->fetchAll();
   $TotalMoodleCategoryData = count($Rows);
      
   $W = 0;
   foreach($MoodleMySQLCon->query($SQL) as $MoodleMySQLRow)
   {
    $MoodleCategoryID[$W] = $MoodleMySQLRow["id"];
    $MoodleCategoryName[$W] = $MoodleMySQLRow["name"];
       
    $W++;
   }
       
//==========取得在Moodle上所有通識和系所類別的名稱和類別ID=========End================
       
       //所有學院資料
   for($i=0;$i<$TotalMoodleCategoryData;$i++)
   {
       //所有課程資料
    for($j=0;$j<$TotalSourceCourseData;$j++)
    {
     $SQL = "select count(*) as TotalCourse ";
     $SQL .= "from mdl_course ";
     $SQL .= "where idnumber = '" . $SourceCourseIDNumber[$j] . "' ";
        
     $Data = $MoodleMySQLCon->query($SQL);
     $Rows = $Data->fetch();
     $FindMoodleCourse = $Rows[0];
        
        //Moodle上沒課程，建立新課程
     if($FindMoodleCourse==0)
     {
        
        //找到對應的系所類別建立新課程
      if(strcmp($MoodleCategoryName[$i], $SourceDeptCategoryName[$j])==0)
      {
       
//============組合課程大綱超連結======Start============================
          
       $FullCourseIDNumberPart = explode("_", $SourceCourseIDNumber[$j]);
         
       $CourseIDNumber = $FullCourseIDNumberPart[1];
         
       $CourseNum = substr($CourseIDNumber, 0, 6);
       $CourseGop = substr($CourseIDNumber, 6, 2);
       $CourseS = substr($CourseIDNumber, 8, 1);
          
       $CourseOutlineURL = "https://newdoc.nccu.edu.tw/teaschm/" . $SemesterChineseName;
       $CourseOutlineURL .= "/schmPrv.jsp-yy=" . $ChineseYear . "&smt=" .  $SubSemester;
       $CourseOutlineURL .= "&num=" . $CourseNum . "&gop=" . $CourseGop . "&s=" . $CourseS . ".html";
          
       $SourceCourseFullEnglishName = $SourceCourseEnglishName[$j];
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
          
       $CourseFullName = $SourceCourseChineseName[$j] . "_" . $CourseEnglishName;
       $CourseChineseName = $SourceCourseChineseName[$j];
         
       $CourseOutlineHTML = "<p><a href=\"" .$CourseOutlineURL  . "\" target=\"_blank\">";
       $CourseOutlineHTML .= $CourseChineseName . "_課程大綱_course outline</a></p>";
           
//============組合課程大綱超連結======End==============================
          
//============建立課程======Start============================ 
          
       $CourseData = new stdClass();
          
   //字串長度太長，透過Moodle API 寫進資料庫時會出現錯誤。
   //先以較短的字串透過Moodle API 建立課程後，再以 SQL update 指令直接更新資料庫相關內容。
          
       $CourseData->fullname = $CourseChineseName;
       $CourseData->shortname = $CourseChineseName;
       $CourseData->idnumber = $SourceCourseIDNumber[$j];
       $CourseData->category  = $MoodleCategoryID[$i];
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
       $CourseData->summary = $CourseOutlineHTML;
          
       create_course($CourseData);
         
       $SQL = "update mdl_course ";
       $SQL .= "set enddate = '" . $EndDateTimestamp . "' ";
       $SQL .= "where idnumber = '" . $SourceCourseIDNumber[$j] . "' ";
         
       $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
       $MoodleMySQLRS->execute();
         
       $SQL = "select id ";
       $SQL .= "from mdl_course ";
       $SQL .= "where idnumber = '" . $SourceCourseIDNumber[$j] . "' ";
          
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
		  
       $CourseOutlineHTML = "<p><a href=\"" .$CourseOutlineURL  . "\" target=\"_blank\">";
       $CourseOutlineHTML .= $CourseFullName . "_課程大綱_course outline</a></p>";
          
       $SQL = "update mdl_course ";
       $SQL .= "set fullname = '" . $CourseFullName . "', shortname = '" . $CourseFullName . "', summary = '" . $CourseOutlineHTML . "' ";
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
		   
       for($k=0;$k<$MoodleSectionNum;$k++)
       {
        $SectionNum = $k + 1;
          
        if($SectionNum==1)
        {
         $WeekName = "Week 01 " . $FirstWeekStartDate . " ~ " . $FirstWeekEndDate;
        }
        else
        {
         $DiffWeek = $k;
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
         
//============建立課程======End==============================
          
//==========更改「公告」討論區模組名稱由中文改為中文與英文並列========Start=======
          
       $SQL = "update mdl_forum ";
       $SQL .= "set name = '公告 Announcements' ";
       $SQL .= "where course = '" . $MoodleDBCourseID . "' ";
          
       $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
       $MoodleMySQLRS->execute();
          
//==========更改「公告」討論區模組名稱由中文改為中文與英文並列========End=========
          
//========新增「課程大綱」的URL模組於「課程公告」之後===========Start=============
          
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
          
       $SQL = "select id from mdl_block_instances ";
       $SQL .= "where parentcontextid = '" . $DBCourseContextID . "' ";
       $SQL .= "and blockname = 'news_items' ";
          
       $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
       $MoodleMySQLRS->execute();
         
       foreach($MoodleMySQLCon->query($SQL) as $row)
       {
        $DBBlockInstanceID = $row["id"];
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
          
       $SQL = "select id ";
       $SQL .= "from mdl_block_instances ";
       $SQL .= "where parentcontextid = '" . $DBCourseContextID . "' ";
       $SQL .= "and blockname = 'activity_modules' ";
          
       foreach($MoodleMySQLCon->query($SQL) as $row)
       {
        $DBActivityBlockInstanceID = $row["id"];
       }
          
       $SQL = "insert into mdl_block_positions ";
       $SQL .= "(blockinstanceid, contextid, pagetype, subpage, visible, region, weight) ";
       $SQL .= "values ";
       $SQL .= "('" . $DBActivityBlockInstanceID . "', '" . $DBCourseContextID . "', ";
       $SQL .= "'course-view-weeks', '', '1', 'side-pre', '-3') ";
           
       $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
       $MoodleMySQLRS->execute();
          
//=============頁面定位「活動」區塊==========End============
          
//=============頁面定位「未來事件」區塊==========Start==========
          
       $SQL = "select id ";
       $SQL .= "from mdl_block_instances ";
       $SQL .= "where parentcontextid = '" . $DBCourseContextID . "' ";
       $SQL .= "and blockname = 'calendar_upcoming' ";
          
       foreach($MoodleMySQLCon->query($SQL) as $row)
       {
        $DBFutureEventsBlockInstanceID = $row["id"];
       }
          
       $SQL = "insert into mdl_block_positions ";
       $SQL .= "(blockinstanceid, contextid, pagetype, subpage, visible, region, weight) ";
       $SQL .= "values ";
       $SQL .= "('" . $DBFutureEventsBlockInstanceID . "', '" . $DBCourseContextID . "', ";
       $SQL .= "'course-view-weeks', '', '1', 'side-pre', '0') ";
          
       $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
       $MoodleMySQLRS->execute();
          
//=============頁面定位「未來事件」區塊==========End============
            
       $Msg = "「" . $MoodleCategoryName[$i] . "」&nbsp;&nbsp;" . "建立個別課程：「" . $SourceCourseChineseName[$j] . "」" . "<br>";
          
       $Log = "「" . $MoodleCategoryName[$i] . "」  建立個別課程：「" . $SourceCourseChineseName[$j] . "」" . "\r\n";
           
       echo $Msg;
         
       fwrite($fp, $Log);
      }  //找到對應的系所類別建立新課程  End
     }  //Moodle上沒課程，建立新課程  End
    }  //所有課程資料  End
   }  //所有學院資料  End
      
//===============建立各系所課程================End========================
        
//===============隱藏停開課程=================Start===================
       
   $SQL = "select Department.name as DepartmentName, ";
   $SQL .= "Course.fullname as CourseName, Course.idnumber as CourseIDNumber ";
   $SQL .= "from mdl_course as Course ";
   $SQL .= "left join mdl_course_categories as Department on Course.category = Department.id ";
   $SQL .= "where Course.idnumber like '" . $SemesterChineseName . "%' ";
   $SQL .= "and Course.idnumber not like '%/%' ";
   $SQL .= "and Course.visible = '1' ";
       
   $Data = $MoodleMySQLCon->query($SQL);
   $Rows = $Data->fetchAll();
   $TotalMoodleCourseData = count($Rows);
      
   $W = 0;
   foreach($MoodleMySQLCon->query($SQL) as $MoodleMySQLRow)
   {
    $MoodleDepartmentName[$W] = $MoodleMySQLRow["DepartmentName"];
    $MoodleCourseName[$W] = $MoodleMySQLRow["CourseName"];
    $MoodleCourseIDNumber[$W] = $MoodleMySQLRow["CourseIDNumber"];
       
    $W++;
   }
      
//=============隱藏單獨課程==============Start================
      
   for($i=0;$i<$TotalMoodleCourseData;$i++)
   {
    $DisableMoodleCourse[$i] = "Y";
   }
      
   for($i=0;$i<$TotalSourceCourseData;$i++)
   {
    for($j=0;$j<$TotalMoodleCourseData;$j++)
    {
     if(strcmp($SourceCourseIDNumber[$i],$MoodleCourseIDNumber[$j])==0)
 	{
      $DisableMoodleCourse[$j] = "N";
     }
    }
   }    
       
   for($i=0;$i<$TotalMoodleCourseData;$i++)
   {
    if(strcmp($DisableMoodleCourse[$i],"Y")==0)
    {
     $SQL = "update mdl_course ";
     $SQL .= "set visible = '0', visibleold = '0' ";
     $SQL .= "where idnumber = '" . $MoodleCourseIDNumber[$i] . "' ";
       
     $MoodleMySQLRS = $MoodleMySQLCon->prepare($SQL);
     $MoodleMySQLRS->execute();
       
     $Msg = "「" . $MoodleDepartmentName[$i] . "」&nbsp;&nbsp;" . "停開個別課程：「" . $MoodleCourseName[$i] . "」" . "<br>";
        
     $Log = "「" . $MoodleDepartmentName[$i] . "」 停開個別課程：「" . $MoodleCourseName[$i] . "」" . "\r\n";
        
     echo $Msg;
       
     fwrite($fp, $Log);
    }
   }
       
//=============隱藏單獨課程==============End==================
       
//===============隱藏停開課程=================End=====================
       
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
      
  CreateCourse($SchoolAffairsDBHost, $SchoolAffairsDBPort, $SchoolAffairsDBName, $SchoolAffairsDBUsername, $SchoolAffairsDBPassword,$MoodleDBHost, $MoodleDBPort, $MoodleDBName, $MoodleDBUsername, $MoodleDBPassword, $SemesterChineseName, $SemesterFullName, $EndDateTimestamp, $ChineseYear, $SubSemester);
 }
 else
 {
  $SemesterChineseName = $SemesterChineseName01;
  $SemesterEnglishName = $SemesterEnglishName01;
  $SubSemester = $SubSemester01;
  $SemesterFullName = $SemesterChineseName . " - " . $SemesterEnglishName;
	  
  $EndDateTimestamp = SemesterEndDate($Year, $SubSemester);
      
  CreateCourse($SchoolAffairsDBHost, $SchoolAffairsDBPort, $SchoolAffairsDBName, $SchoolAffairsDBUsername, $SchoolAffairsDBPassword,$MoodleDBHost, $MoodleDBPort, $MoodleDBName, $MoodleDBUsername, $MoodleDBPassword, $SemesterChineseName, $SemesterFullName, $EndDateTimestamp, $ChineseYear, $SubSemester);
  
  echo "<br><br>";
      
  $SemesterChineseName = $SemesterChineseName02;
  $SemesterEnglishName = $SemesterEnglishName02;
  $SubSemester = $SubSemester02;
  $SemesterFullName = $SemesterChineseName . " - " . $SemesterEnglishName;
      
  $EndDateTimestamp = SemesterEndDate($Year, $SubSemester);
      
  echo "SemesterName = " . $SemesterFullName . "<br>";
  
  CreateCourse($SchoolAffairsDBHost, $SchoolAffairsDBPort, $SchoolAffairsDBName, $SchoolAffairsDBUsername, $SchoolAffairsDBPassword,$MoodleDBHost, $MoodleDBPort, $MoodleDBName, $MoodleDBUsername, $MoodleDBPassword, $SemesterChineseName, $SemesterFullName, $EndDateTimestamp, $ChineseYear, $SubSemester);
 }
      	
?>
  