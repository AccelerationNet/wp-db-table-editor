if(typeof(NS) == 'undefined') NS={};
(function(){
"use strict";
NS._plus = new RegExp('\\+','g');
NS.parseQuery = function(query) {
  if(!query) query = window.location.search.substring(1);
  var obj = {};
  var vars = query.split('&');
  for (var i = 0; i < vars.length; i++) {
    var pair = vars[i].split('=');
    var k = decodeURIComponent(pair[0]).replace(NS._plus, " ");
    var v = decodeURIComponent(pair[1]).replace(NS._plus, " ");
    if(obj[k]){
      if (angular.isArray(obj[k])) obj[k].push(v);
      else obj[k]= [obj[k],v];
    }
    else obj[k]=v;
  }
  return obj;
};
NS.encodeQuery =function(q) {
  var a =[];
  if(!q)q=NS.QUERY;
  $.each(q, function(k, v) {
    console.log(k, v);
    a.push(encodeURIComponent(k)+"="+encodeURIComponent(v));
  });
  return '?'+a.join('&');
};

NS.QUERY = NS.parseQuery();
var $ = jQuery;
NS.currentYear = (new Date().getFullYear());
NS.year = NS.QUERY['year'] || NS.currentYear;
NS.year = Number(NS.year);
NS.nextYear =function(){
  NS.QUERY['year']=Number(NS.year)+1;
  window.location = window.location.toString().replace(/\?.*/,NS.encodeQuery());
};
NS.lastYear =function(){
  NS.QUERY['year']=NS.year-1;
  window.location = window.location.toString().replace(/\?.*/,NS.encodeQuery());
};
NS.init = function(){
  var btns = $('.db-table-editor-buttons');
  var btnPrev = $('<button >Previous Year</button>').click(NS.lastYear);
  var btnNext =  ( NS.year < NS.currentYear ) ?
    $('<button>Next Year</button>').click(NS.nextYear) : null;
  console.log('Adding buttons', btnPrev, btnNext, btns);
  btns.append([ '<br>', btnPrev, btnNext]);
};

//NS.init();
jQuery(NS.init);
})();
