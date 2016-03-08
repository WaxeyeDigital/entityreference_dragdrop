(function($){
  
  Drupal.entityreference_dragdrop = {};
  
  Drupal.entityreference_dragdrop.update = function (event, ui) {
    var items = [];
    var key = $(event.target).attr("data-key");
    $(".entityreference-dragdrop-selected[data-key=" + key + "] li[data-key=" + key + "]").each(function(index) {
      items.push($(this).attr('data-id'));
    });
    $("input.entityreference-dragdrop-values[data-key=" + key +"]").val(items.join(','));
    
    if (drupalSettings.entityreference_dragdrop[key] != -1) {
      if (items.length > drupalSettings.entityreference_dragdrop[key]) {
        $(".entityreference-dragdrop-message[data-key=" + key + "]").show();
        $(".entityreference-dragdrop-selected[data-key=" + key + "]").css("border", "1px solid red");
      }
      else {
        $(".entityreference-dragdrop-message[data-key=" + key + "]").hide();
        $(".entityreference-dragdrop-selected[data-key=" + key + "]").css("border", "");
      }
    }
  }
  
  Drupal.behaviors.entityreference_dragdrop = {
    attach: function() {
      var $avail = $(".entityreference-dragdrop-available");
      var $select = $(".entityreference-dragdrop-selected");

      $avail.sortable({
        connectWith: "ul.entityreference-dragdrop"
      });

      $select.sortable({
        connectWith: "ul.entityreference-dragdrop",
        update: Drupal.entityreference_dragdrop.update
      });
    }
  };
})(jQuery);
