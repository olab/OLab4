// Mod: Date Link for Xin Calendar 2 (In-Page/Popup-Window)
// Copyright 2004  Xin Yang    All Rights Reserved.

function addDateLink(co,cu,cv,ec,date,kc){xc_eh(co,"eh",xc_fw(date,ec||xcDateFormat,xc_da),[cu,cv,kc],0)};function xc_dh(){var gh=xc_bi(this);if(gh){if(xcLinkTargetWindow){return ['window.open("'+xcLinkBasePath+gh[2]+'","'+xcLinkTargetWindow+'","'+xcLinkTargetWindowPara+'");',0]}else{return ['location.href="'+xcLinkBasePath+gh[2]+'";',0]}}else{return ["",1]}};function xc_dk(){var gh=xc_bi(this);if(gh){return 'this.title="'+xcLinkBasePath+gh[2]+'";'}else{return ""}};function xc_bi(bj){return bj.ge("eh",bj.date)};xc_fd[xc_fd.length]=xc_bi;
