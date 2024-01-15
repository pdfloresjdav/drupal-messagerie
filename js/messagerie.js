(function($) {

$.fn.myTest = function(data) {
    setTimeout(function(){ $('.loader').remove();
    $('.user-photop').removeClass('forum-dis-non'); }, 3000); 
  };

$.fn.removeChooseCl = function(data) {
    $('.row').removeClass('messagerie-choose');
};

$(document).on("click", '.more-sub-articles', function(event) {
  console.log("entre more");
    var data = event.currentTarget;
    var page = $(data).attr('page');
    var tid = $(data).attr('tid');
    var status = $(data).attr('status');
    var address = new Object;
    address['page']=page;
    address['tid']=tid;
    address['status']=status;

    var url = '/meteodusi_ajax';
    
    $.ajax({
      url: url,
      type: 'POST',
      data: address,
      dataType: 'json',
      success: function(data){
          console.log("backkkk");
        $('.k-button').remove();
        $('#meteodusi_list_content').append(data[0].data);
      }
    });

  });

/*$(document).on("mouseleave", '.destin-row', function(event) {
  var data = event.currentTarget;
  var destin = $('.destin-row').val();
  var res = destin.split(",");
  var conStr='<span class="destina-tag" style="color: #777; margin-left: 13px;">Destinataire: </span>';
  jQuery.each( res, function( i, val ) {
    if(val !== ''){
      conStr = conStr+'<span class="destina-user-tag" style="background-color: #00AAC3; border-radius: 15px; color: white; padding: 5px; margin-right: 5px;">'+val+'</span>';
    }
  });
  if(destin !== ''){
    $('.mesdestina').html(conStr);
    $('.destin-row').css('display', 'none');
    $('.mesdestina').css('display', 'block');
  }
  
});*/

$(document).on("change", '.destin-row', function(event) {
  var destin = $('.destin-row').val();
  $('.mesdestina').html(destin);
});

/*$(document).on("mouseover", '.mesdestina', function(event) {
  var destin = $('.destin-row').val();
  var res = destin.split(",");
  var conStr='<span class="destina-tag" style="color: #777; margin-left: 13px;">Destinataire: </span>';
  jQuery.each( res, function( i, val ) {
    if(val !== ''){
      conStr = conStr+'<span class="destina-user-tag" style="background-color: #00AAC3; border-radius: 15px; color: white; padding: 5px; margin-right: 5px;">'+val+'</span>';
    }
  });

  $('.mesdestina').html(conStr);
  $('.destin-row').css('display', 'block');
  $('.mesdestina').css('display', 'none');

});*/

/*$(document).on("mouseover", '.subject-row', function(event) {
  var destin = $('.destin-row').val();
  var res = destin.split(",");
  var conStr='<span class="destina-tag" style="color: #777; margin-left: 13px;">Destinataire: </span>';
  jQuery.each( res, function( i, val ) {
    if(val !== ''){
      conStr = conStr+'<span class="destina-user-tag" style="background-color: #00AAC3; border-radius: 15px; color: white; padding: 5px; margin-right: 5px;">'+val+'</span>';
    }
  });
  if(destin !== ''){
    $('.mesdestina').html(conStr);
    $('.destin-row').css('display', 'none');
    $('.mesdestina').css('display', 'block');
  }
});*/

$(document).on("click", '.subject-row', function(event) {
  var destin = $('.destin-row').val();
  var res = destin.split(",");
  var conStr='<span style="color: #777; margin-left: 13px;">Destinataire: </span>';
  var destStr='';
  jQuery.each( res, function( i, val ) {
    if(val !== ''){
      var res1 = val.split("(");
      var n = val.includes("(");
      if(n){
        destStr = destStr + val +',';
        jQuery.each( res1, function( i, val ) {
          if(i !== 1){
          conStr = conStr+'<span class="destina-user-tag" style="background-color: #00AAC3; border-radius: 15px; color: white; padding: 5px; margin-right: 5px;">'+val+'</span>';
          }
        });
      }
    }
  });
  if(destin !== ''){
    $('.destin-row').val(destStr);
    $('.mesdestina').html(conStr);
    $('.destin-row').css('display', 'none');
    $('.mesdestina').css('display', 'block');
  }
});

$(document).on("mouseover", '.form-item-body-value', function(event) {
  var destin = $('.destin-row').val();
  var res = destin.split(",");
  var conStr='<span class="destina-tag" style="color: #777; margin-left: 13px;">Destinataire: </span>';
  var destStr='';
  jQuery.each( res, function( i, val ) {
    if(val !== ''){
      var res1 = val.split("(");
      var n = val.includes("(");
      if(n){
        destStr = destStr + val +',';
        jQuery.each( res1, function( i, val ) {
          if(i !== 1){
          conStr = conStr+'<span class="destina-user-tag" style="background-color: #00AAC3; border-radius: 15px; color: white; padding: 5px; margin-right: 5px;">'+val+'</span>';
          }
        });
      }
    }
  });
  if(destin !== ''){
    $('.destin-row').val(destStr);
    $('.mesdestina').html(conStr);
    $('.destin-row').css('display', 'none');
    $('.mesdestina').css('display', 'block');
  }
});

$(document).on("click", '.cke_inner', function(event) {
  var destin = $('.destin-row').val();
  var res = destin.split(",");
  var conStr='<span style="color: #777; margin-left: 13px;">Destinataire: </span>';
  var destStr='';
  jQuery.each( res, function( i, val ) {
    if(val !== ''){
      var res1 = val.split("(");
      var n = val.includes("(");
      if(n){
        destStr = destStr + val +',';
        jQuery.each( res1, function( i, val ) {
          if(i !== 1){
          conStr = conStr+'<span class="destina-user-tag" style="background-color: #00AAC3; border-radius: 15px; color: white; padding: 5px; margin-right: 5px;">'+val+'</span>';
          }
        });
      }
    }
  });
  if(destin !== ''){
    $('.destin-row').val(destStr);
    $('.mesdestina').html(conStr);
    $('.destin-row').css('display', 'none');
    $('.mesdestina').css('display', 'block');
  }
});


$(document).on("click", '.mesdestina', function(event) {
  var destin = $('.destin-row').val();
  var res = destin.split(",");
  var conStr='<span class="destina-tag" style="color: #777; margin-left: 13px;">Destinataire: </span>';
  var destStr='';
  jQuery.each( res, function( i, val ) {
    if(val !== ''){
      var res1 = val.split("(");
      var n = val.includes("(");
      if(n){
        destStr = destStr + val +',';
        jQuery.each( res1, function( i, val ) {
          if(i !== 1){
          conStr = conStr+'<span class="destina-user-tag" style="background-color: #00AAC3; border-radius: 15px; color: white; padding: 5px; margin-right: 5px;">'+val+'</span>';
          }
        });
      }
    }
  });
  $('.destin-row').val(destStr);
  $('.mesdestina').html(conStr);
  $('.destin-row').css('display', 'block');
  $('.mesdestina').css('display', 'none');

});

  
})(jQuery);




