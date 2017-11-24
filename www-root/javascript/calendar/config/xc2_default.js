// date format
// -----------
xcDateFormat="yyyy-mm-dd";


// css conf
// --------
xcCSSPanel="panel";

xcCSSHeadBlock="row_head";
xcCSSHead="head";

xcCSSArrowMonthPrev=["arrow_prev", "arrow_prev_over", "arrow_prev_down"];
xcCSSArrowMonthNext=["arrow_next", "arrow_next_over", "arrow_next_down"];

xcCSSArrowYearPrev=["arrow_prev", "arrow_prev_over", "arrow_prev_down"];
xcCSSArrowYearNext=["arrow_next", "arrow_next_over", "arrow_next_down"];

xcCSSWeekdayBlock="row_week";
xcCSSWeekday="weekday";

xcCSSDayBlock="row_day";
xcCSSDay=["day", "day_over", "day_down", "day_disabled"];
xcCSSDayCurrent=["day_current", "", ""]; // on-over-down
xcCSSDaySpecial=["day_special", "day_over", "day_down", "day_disabled"];
xcCSSDayOther=["day_other", "day_other_over", "day_down", "day_disabled"];
xcCSSDayOtherCurrent=["day_other_current", "", ""];
xcCSSDayOtherSpecial=["", "", "", ""];   // on-over-down-off
xcCSSDayEmpty="day_empty";

xcCSSVersion="2.9";

xcCSSFootBlock="row_foot";
xcCSSFootToday=["foot", "foot_over", "foot_down"];
xcCSSFootClear=["foot", "foot_over", "foot_down"];
xcCSSFootBack=["foot", "foot_over", "foot_down"];
xcCSSFootClose=["foot", "foot_over", "foot_down"];
xcCSSFootReset=["foot", "foot_over", "foot_down"];
xcCSSFootOther=[];


// layout conf
// -----------
xcMonthNames=["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
xcMonthShortNames=["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
xcMonthPrefix="";
xcMonthSuffix="";

xcYearDigits=["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
xcYearPrefix="";
xcYearSuffix="";

xcHeadSeparator=" ";   // separator string between year and month
xcHeadTagOrder=1;      // 1: month/year, 0: year/month
xcHeadTagAdjustment=1; // 1: 100% width, 0: actual width

xcArrowMonth=["&#139;", "&#155;"];
xcArrowYear=["&#171;", "&#187;"];
xcArrowSwitch=[1, 1];  // [year, month] 1:on, 0:off
xcArrowPosition=0;     // 0:in head block, 1:in foot block

// names for days
xcWeekdayNames=["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
xcWeekdayShortNames=["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
xcWeekdayDisplay=["S", "M", "T", "W", "T", "F", "S", "S"];

// foot links
xcFootTags=["Today", "Clear", "Back", "Close", "Reset", "_Today_", "_Back_", "_Reset_"];
xcFootTagSwitch=[1, 0, 0, 2, 0, 0, 0, 0]; // [today, clear, back, close, reset, _today_, _back_, _reset_] non-zero:display order, 0:off
xcFootTagAdjustment=0;                    // 1: % width, 0: actual width

// customized foot links
xcFootButtons=[];
xcFootButtonSwitch=[];
xcFootButtonLinks=[];

// easy workaround for grid style
xcGridWidth=0;         // used as cellspacing

// others
xcBaseZIndex=20001;      // z-index for calendar layers
xcMultiCalendar=0;     // 1:multi-calendar, 0:single-calendar
xcShowCurrentDate=1;   // 1:highlight current date/today, 0:no highlight
xcWeekStart=1;         // 0:Sunday, 1:Monday
xcAutoHide=1000;          // 0: no auto hide, non-zero:auto hide interval in ms
xcStickyMode=0;        // 0:non-sticky, 1:sticky
xcShowPrevNextMonth=1; // 0:not show, 1: show

// day contents
xcDayContents=["&nbsp;", "1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30", "31"];
xcDayContentsDisabled=xcDayContents;
xcDayContentsCurrent=xcDayContents;


// mod conf
// --------
xcModPath="../script/";
xcMods=[{"order": 0,  "mod": "Month/Year List",   "script": "mod_list.js"},
        {"order": 0,  "mod": "Date Range",        "script": "mod_date.js"},
        {"order": 0,  "mod": "Special Days",      "script": "mod_days.js"},
        {"order": 0,  "mod": "Date Info",         "script": "mod_info.js"},
        {"order": 0,  "mod": "Journal",           "script": "mod_journal.js"},
        {"order": 0,  "mod": "Date Link",         "script": "mod_link.js"},
        {"order": 0,  "mod": "Long Date Format",  "script": "mod_long.js"},
        {"order": 0,  "mod": "Tiles",             "script": "mod_tiles.js"},
        {"order": 0,  "mod": "Date Tips",         "script": "mod_tips.js"},
        {"order": 0,  "mod": "Time",              "script": "mod_time.js"}];

// Month/Year List Mod
xcCSSMonthYearList="list";

xcMonthListFormat="Month"; // Month, MONTH, Mon, Mon, mm
xcYearListRange=5;
xcYearListPrevRange="&#171;&nbsp;";
xcYearListNextRange="&nbsp;&#187;";

// Date Link Mod & Journal Mod
xcLinkBasePath="";
xcLinkTargetWindow="_blank";
xcLinkTargetWindowPara="location=1,menubar=1,resizable=1,scrollbars=1,status=1,titlebar=1,toolbar=1";

// Journal Mod
xcLinkPrefix="";
xcLinkSuffix=".html";
xcLinkDateFormat="yyyymmdd";

// Date Tips Mod
xcDateTipTiming=1000;
xcDateTipBoxTitleSwitch=1; // 1: show title, 0:don't show
xcDateTipBoxPosition=6;    // 0:date top, 3:date right, 6:date bottom, 9:date left
xcDateTipBoxAlign=1;       // 0:left, 1:center, 2:right, for position of 0 and 6
xcDateTipBoxValign=0;      // 0:top, 1:middle, 2:bottom, for position of 3 and 9
xcDateTipBoxOffsetX=0;
xcDateTipBoxOffsetY=6;
xcCSSDateTipBoxTitle="tip_title";
xcCSSDateTipBox="tip_box";

// Time Mod
// xcHours=["00", "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11",
//          "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23"];     // full list of hours
// xcHours=["00", "03", "06", "09", "12", "15", "18", "21"];                             // simplified
xcHours=["00", "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12"];  // for AM/PM setting
//xcMinutes=["00", "01", "02", "03", "04", "05", "06", "07", "08", "09",
//           "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", 
//           "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", 
//           "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", 
//           "40", "41", "42", "43", "44", "45", "46", "47", "48", "49", 
//           "50", "51", "52", "53", "54", "55", "56", "57", "58", "59"];                // full list of minutes
xcMinutes=["00", "05", "10", "15", "20", "25", "30", "35", "40", "45", "50", "55"];      // simplified
//xcSeconds=["00", "01", "02", "03", "04", "05", "06", "07", "08", "09",
//           "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", 
//           "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", 
//           "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", 
//           "40", "41", "42", "43", "44", "45", "46", "47", "48", "49", 
//           "50", "51", "52", "53", "54", "55", "56", "57", "58", "59"];                // full list of seconds
xcSeconds=["00", "05", "10", "15", "20", "25", "30", "35", "40", "45", "50", "55"];      // simplified
xcAMPM=["AM", "PM"];       // to be used when "am" presents in date format
xcTimeBlockOrder=1;        // foot link order for time block
xcCSSTimeBlock=["","",""]; // CSS classes for the block of select lists, ["mouse-out", "mouse-over", "mouse-down"]
xcCSSTimeList="time_list"; // CSS class for the select list

// Popup-Window Version
xcWindowWidth=215;
xcWindowHeight=195;
xcWindowTemplate="../script/xc2_template.html";


// tooltips
xc_Today_is="Today is ";
xc_Clear_the_date_input="Clear the date input";
xc_Scroll_to="Scroll to ";
xc_Close_the_calendar="Close the calendar";
xc_Pick_the_default_date_of="Pick the default date of ";
xc_Today="Today";
xc_Scroll_to_this_date="Scroll to this date";
xc_Pick_the_default_date="Pick the default date";