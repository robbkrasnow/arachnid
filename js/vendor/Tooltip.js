function Tooltip(tooltipId, width){
  var tooltipId = tooltipId;
  $("body").append("<div class='tooltip' id='"+tooltipId+"'></div>");

  if(width){
    $("#"+tooltipId).css("width", width);
  }

  hideTooltip();

  function showTooltip(content, event) {
    $("#"+tooltipId).html(content);
    $("#"+tooltipId).show();

    updatePosition(event);
  }

  function hideTooltip(){
    $("#"+tooltipId).hide();
  }

  function updatePosition(event){
    var ttid = "#"+tooltipId;
    var xOffset = 20;
    var yOffset = 10;

    var tooltipW = $(ttid).width();
    var tooltipH = $(ttid).height();
    var windowY = $(window).scrollTop();
    var windowX = $(window).scrollLeft();
    var curX = event.pageX;
    var curY = event.pageY;
    var ttleft = ((curX) < $(window).width() / 2) ? curX - tooltipW - xOffset*2 : curX + xOffset;
    if (ttleft < windowX + xOffset){
      ttleft = windowX + xOffset;
    } 
    var tttop = ((curY - windowY + yOffset*2 + tooltipH) > $(window).height()) ? curY - tooltipH - yOffset*2 : curY + yOffset;
    if (tttop < windowY + yOffset){
      tttop = curY + yOffset;
    } 
    $(ttid).css('top', tttop + 'px').css('left', ttleft + 'px');
  }

  return {
    showTooltip: showTooltip,
    hideTooltip: hideTooltip,
    updatePosition: updatePosition
  }
}
