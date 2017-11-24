// Mod: Date Info for Xin Calendar 2 (In-Page/Popup-Window)
// Copyright 2004  Xin Yang    All Rights Reserved.

function addDateInfo(co,cr,ct,cs,ec,date,gz,hl){var iq=getDateNumbers(date,ec||xcDateFormat);xc_eh(co,"eg",(hl?iq[0]:"")+iq[1]+iq[2],[cr,ct,cs||"",gz],0)};function xc_cl(date){return date.substring(4)};function xc_bq(style,cp,width,fp){if(this.date!=""){var gg=this.ge("eg",this.date)||this.ge("eg",xc_cl(this.date));if(gg){cp+=xcDIV(gg[2],gg[3],"")}};return xc_fg(style,cp,width,fp)};function xc_bh(bj){return bj.ge("eg",bj.date)||bj.ge("eg",xc_cl(bj.date))};xc_fd[xc_fd.length]=xc_bh;
