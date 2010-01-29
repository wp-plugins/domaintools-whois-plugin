var DT = new Object();
DT.j = jQuery.noConflict();
var tooltip_div = document.getElementById('tooltip_div');

var tooltip_frm = document.createElement('iframe');
tooltip_frm.setAttribute('id', 'tooltip_iframe');
tooltip_frm.setAttribute('src', '');
tooltip_frm.setAttribute('border', '0');
tooltip_frm.setAttribute('frameborder', '0');
tooltip_frm.setAttribute('scrolling', 'no');
tooltip_frm.style.backgroundColor = "white";
tooltip_frm.style.width = "300px";
tooltip_frm.style.height = "350px";
tooltip_frm.style.border = "0";
tooltip_frm.style.textAlign = "center";
tooltip_div.appendChild(tooltip_frm);

tooltip_frm.update = function(url)
{
    tooltip_frm.src = url;
}

DT.j(function(){

    DT.j('a.tooltip').cluetip({
        local: true,
        cursor: 'pointer',
        cluetipClass: 'jtip',
        arrows: true,
        dropShadow: true,
        hoverIntent: false,
        titleAttribute: 'tt_title',
        sticky: true,
        mouseOutClose: true,
        clickThrough: true,
        closePosition: 'title',
        closeText: 'X',
        width: '316px'
    });

});
