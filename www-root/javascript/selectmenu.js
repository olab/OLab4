// Select Menu

// Copyright Xin Yang 2003
// Web Site: www.yxScripts.com
// EMail: m_yangxin@hotmail.com

// This script is free as long as the copyright notice remains intact.

var smListPool=new Array(), smTopLists=new Array(), smItemPool=new Array(), smCurrentList=null;

function smListOBJ(id, form_name, select_name, non_item_tag, sub_tag, back_tag) {
  this.id=id;
  this.form=form_name;
  this.select=select_name;
  this.non_item_tag=non_item_tag;
  this.sub_tag=sub_tag;
  this.back_tag=back_tag;
}

function smItemOBJ(id, num, value, desc) {
  this.id=id;
  this.num=num;
  this.value=value;
  this.desc=desc;
}

function smTopOBJ(id, value, desc, selected) {
  this.id=id;
  this.value=value;
  this.desc=desc;
  this.selected=(typeof(selected)!="undefined" && selected==1);
}

function smFindList(id) {
  for (var i=0; i<smListPool.length; i++) {
    if (smListPool[i].id==id) {
      return document.forms[smListPool[i].form][smListPool[i].select];
    }
  }

  return null;
}

function smSetList(id) {
  if (smCurrentList!=null && smCurrentList.id==id) {
    return;
  }

  smCurrentList=null;
  for (var i=0; i<smListPool.length; i++) {
    if (smListPool[i].id==id) {
      smCurrentList=smListPool[i];
      break;
    }
  }
}

function addList(id, form_name, select_name, non_item_tag, sub_tag, back_tag) {
  smListPool[smListPool.length]=new smListOBJ(id, form_name, select_name, non_item_tag, sub_tag, back_tag);
}

function addItem(id, num, value, desc) {
  smItemPool[smItemPool.length]=new smItemOBJ(id, num, value, desc!=""?desc:value);
}

function addTopList(id, start, end, desc, idx) {
  smSetList(id);

  if (smCurrentList!=null) {
    for (var i=0; i<smItemPool.length; i++) {
      if (smItemPool[i].id==id && smItemPool[i].num>=start && smItemPool[i].num<=end) {
        if (start==end) {
          smTopLists[smTopLists.length]=new smTopOBJ(id, smItemPool[i].value, desc!=""?desc:smItemPool[i].desc, idx);
        }
        else {
          smTopLists[smTopLists.length]=new smTopOBJ(id, smCurrentList.sub_tag+":"+start+","+end+","+idx, desc, 0);
        }

        break;
      }
    }
  }
}

function emptyList(id) {
  var list=smFindList(id);

  for (var i=list.options.length-1; i>=0; i--) {
    list.options[i]=null;
  }
}

function setTopList(id, mode) {
  var list=smFindList(id);

  var o=0, selected=0;
  for (var i=0; i<smTopLists.length; i++) {
    if (smTopLists[i].id==id) {
      if (smTopLists[i].selected && mode) {
        selected=o;
      }
      list.options[o++]=new Option(smTopLists[i].desc, smTopLists[i].value);
    }
  }

  list.selectedIndex=selected;
}

function setSubList(id, start, end) {
  var list=smFindList(id);

  var o=0;
  for (var i=0; i<smItemPool.length; i++) {
    if (smItemPool[i].id==id && smItemPool[i].num>=start && smItemPool[i].num<=end) {
      list.options[o++]=new Option(smItemPool[i].desc, smItemPool[i].value);
    }
  }
}

function initList() {
    for (var i=0; i<smListPool.length; i++) {
      emptyList(smListPool[i].id);
      setTopList(smListPool[i].id, true);
    }
}

function updateList(id) {
  var list=smFindList(id);
  var option=list.options[list.options.selectedIndex];

  smSetList(id);

  if (option.value==smCurrentList.back_tag) {
    emptyList(id);
    setTopList(id, false);
  }
  else if (option.value.substring(0,smCurrentList.sub_tag.length)==smCurrentList.sub_tag) {
    var range=option.value.substring(smCurrentList.sub_tag.length+1).split(",");
    emptyList(id);
    setSubList(id, range[0], range[1]);
    list.selectedIndex=range[2]-1;
  }
}

function resetList(id) {
  emptyList(id);
  setTopList(id, true);
}

function checkList(id) {
  var list=smFindList(id);
  var value=list.options[list.selectedIndex].value;

  smSetList(id);
  return (value!=smCurrentList.sub_tag && value!=smCurrentList.back_tag && value!=smCurrentList.non_item_tag);
}
