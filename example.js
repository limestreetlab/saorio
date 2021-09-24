$("document").ready(function() {
  
  var html = '';
  $("#btn").click(function(){
    $.get("templates/messages_frontend_elements.html", function(data){
      
      html = data;
      alert(html);

      let el = $(data).find("#hisChatBubble").prop("outerHTML");
      alert(el);

    });

  });

});