(function($){
  
  Drupal.entityreference_dragdrop = Drupal.entityreference_dragdrop ? Drupal.entityreference_dragdrop : {};
  
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
  };
  
  Drupal.behaviors.entityreference_dragdrop = {
    attach: function() {
      var $avail = $(".entityreference-dragdrop-available"),
        $select = $(".entityreference-dragdrop-selected");


      $avail.once('entityreference-dragdrop').each(function () {
        var key = $(this).data('key');
        $(this).sortable({
          connectWith: 'ul.entityreference-dragdrop[data-key=' + key + ']'
        });
      });

      $select.once('entityreference-dragdrop').each(function() {
        var key = $(this).data('key');
        $(this).sortable({
          connectWith: "ul.entityreference-dragdrop[data-key=" + key + ']',
          update: Drupal.entityreference_dragdrop.update
        });
      });

      $('.entityreference-dragdrop-filter').once('entityreference-dragdrop').each(function() {
        $(this).bind('keyup paste', function() {
          var $this = $(this);
          var val = $this.val().toLowerCase();
          if (val != '') {
            $this.parents('.entityreference-dragdrop-container').find('li').each(function(i, elem) {
              var $elem = $(elem);
              if ($elem.data('label').toLowerCase().indexOf(val) >= 0) {
                $elem.show();
              }
              else {
                $elem.hide();
              }
            });
          }
          else {
            $this.parents('.entityreference-dragdrop-container').find('li').show();
          }
        });
      });
    }
  };
})(jQuery);
