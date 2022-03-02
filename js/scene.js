// JavaScript Document

window.addEventListener('hashchange',function(){
  if (window.location.hash){
    var name=window.location.hash;
    loadHash(name);
  }else{
    loadDefault();
  }
})

$(function(){
  if (window.location.hash){
    var name=window.location.hash;
    loadHash(name);
  }else{
    loadDefault();
  }
  
  $("[data-toggle='tooltip'], span.term").tooltip();

  $(".text").css('cursor','pointer').click(function(){
    $(this).find('p:visible:first').fadeOut('slow',function(){
      if ($(this).is(":last-child")){
        if ($(this).parent().next().is(".text")){
          $(this).parent().hide();
          $(this).parent().next().fadeIn('fast',function(){
            $(this).find("p:first").fadeIn('fast');
          })
        }
        else {
          $(".scene div:visible:first").fadeOut('slow',function(){
            var next=$(this).next().attr('id');
            window.location='#'+next;
          });
        }
      } else {
        $(this).next().fadeIn('slow');
      }
    });
  });
  
  $(".end").css('cursor','pointer').click(function(){
    window.history.back();
  });
  
  $("a#logBtn").click(function(e){
    e.preventDefault();
    
    var last=$(".scene > div:visible:last");
    
    if (last.hasClass('log')){
      last.find('div').removeClass('log');
      last.removeClass('log');
    }
    else {
      last.find('div').addClass('log');
      last.addClass('log');
    }
  })
});

function loadDefault(){
  $("div.scene div:visible:first").hide();
  $(".scene :first").fadeIn('fast',function(){
    $(this).find(".text :first").fadeIn('fast');
  });
}

function loadHash(name){
  $("div.scene div:visible:first").hide();
  setTimeout(function(){
    $("div.scene "+name).fadeIn('fast',function(){
      $(this).find(".text, .choice").show();
      $(this).find(".text p:first").fadeIn('fast');
    });
  },300); //Small interval to let vue finish rendering when needed
}

