jQuery(document).ready(function($) {
  var $avail = $(".entityreference-dragdrop-available");
  var $select = $(".entityreference-dragdrop-selected");
  
  $avail.sortable({
    connectWith: "ul.entityreference-dragdrop"
  });

  $select.sortable({
    connectWith: "ul.entityreference-dragdrop",
    update: entityreference_dragdrop_update
  });
});

function entityreference_dragdrop_update(event, ui) {
  var items = [];
  jQuery(".entityreference-dragdrop-selected li").each(function(index) {
    items.push(jQuery(this).attr('data-id'));
  });
  
  jQuery("input.entityreference-dragdrop-values").val(items.join(','));
}
