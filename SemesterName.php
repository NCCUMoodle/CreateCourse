<?php
 date_default_timezone_set("Asia/Taipei");  //設定時區為台北時區
    
 $Year = date("Y");
 $Month = date("m");
     
/*       
 $Year = "2026";  //測試用
 $Month = "06";   //測試用
*/ 
	 
   //西元年月轉換成學年
 switch($Month)
 {
  case "01" :
  case "02" :
  case "03" :
  case "04" :
  case "05" :
   $ChineseYear = $Year - 1911 - 1;
   $SubSemester01 = "2";
   $SubSemester02 = "2";
      
   $SemesterChineseName01 = $ChineseYear . $SubSemester01;
   $SemesterChineseName02 = $ChineseYear . $SubSemester02;
       
   $SemesterYear = $Year;
   $SemesterEnglishName01 = $SemesterYear . " Spring Semester";
   $SemesterEnglishName02 = $SemesterYear . " Spring Semester";
  break;   
  case "06" :
   $ChineseYear = $Year - 1911 - 1;
   $SubSemester01 = "2";
   $SemesterChineseName01 = $ChineseYear . $SubSemester01;
       
   $SemesterYear = $Year;
   $SemesterEnglishName01 = $SemesterYear . " Spring Semester";
     
      
   $ChineseYear = $Year - 1911;
   $SubSemester02 = "1";
   $SemesterChineseName02 = $ChineseYear . $SubSemester02;
      
   $SemesterYear = $Year;
   $SemesterEnglishName02 = $SemesterYear . " Fall Semester";   
  break;
  case "07" :
  case "08" :
  case "09" :
  case "10" :
  case "11" :
   $ChineseYear = $Year - 1911;
   $SubSemester01 = "1";
   $SubSemester02 = "1";
      
   $SemesterChineseName01 = $ChineseYear . $SubSemester01;
   $SemesterChineseName02 = $ChineseYear . $SubSemester02;
	   
   $SemesterYear = $Year;
   $SemesterEnglishName01 = $SemesterYear . " Fall Semester";
   $SemesterEnglishName02 = $SemesterYear . " Fall Semester";
  break;
  case "12" :
   $ChineseYear = $Year - 1911;
   $SubSemester01 = "1";
   $SemesterChineseName01 = $ChineseYear . $SubSemester01;
      
   $SemesterYear = $Year;
   $SemesterEnglishName01 = $SemesterYear . " Fall Semester";
	
	
   $ChineseYear = $Year - 1911;
   $SubSemester02 = "2";
   $SemesterChineseName02 = $ChineseYear . $SubSemester02;
       
   $SemesterYear = $Year + 1;
   $SemesterEnglishName02 = $SemesterYear . " Spring Semester";
  break;
 }
     
 if(strcmp($SemesterChineseName01, $SemesterChineseName02)==0)
 {
  $SameSemesterCName = true;
 }
 else
 {
  $SameSemesterCName = false;
 }
     
 if(strcmp($SemesterEnglishName01,$SemesterEnglishName02)==0)
 {
  $SameSemesterEName = true;
 }
 else
 {
  $SameSemesterEName = false;
 }
	  
?>
