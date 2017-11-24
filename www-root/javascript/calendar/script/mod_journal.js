// Mod: Journal for Xin Calendar 2 (In-Page/Popup-Window)
// Copyright 2004  Xin Yang    All Rights Reserved.

function xc_dh(){var hc=xcLinkBasePath+xcLinkPrefix+xc_fw(this.date,xc_da,xcLinkDateFormat)+xcLinkSuffix;if(xcLinkTargetWindow){return ['window.open("'+hc+'","'+xcLinkTargetWindow+'","'+xcLinkTargetWindowPara+'");',0]}else{return ['location.href="'+hc+'";',0]}};
