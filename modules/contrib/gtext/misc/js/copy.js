(function($){
  var copyButtonClick = function(elem, text){
    console.log(text);

    var targetId  = "_HiddenCopyText_";
    var isInput   = elem.tagName === "INPUT" || elem.tagName === "TEXTAREA";
    var origSelectionStart, origSelectionEnd;
    if(isInput){
      target = elem;
      origSelectionStart = elem.selectionStart;
      origSelectionEnd = elem.selectionEnd;
    } else {
      target = document.getElementById(targetId);
      if (!target) {
        var target = document.createElement("textarea");
        target.style.position = "absolute";
        target.style.left = "-9999px";
        target.id = targetId;
        elem.parentNode.appendChild(target);
      }
      target.textContent = text;
    }
    var currentFocus = document.activeElement;
    target.focus();
    target.setSelectionRange(0, target.value.length);
    var succeed;
    try{
      succeed = document.execCommand("copy");
    }catch(e){
      succeed = false;
    }
    if(currentFocus && typeof currentFocus.focus === "function"){
      currentFocus.focus();
    }
    if(isInput){
      elem.setSelectionRange(origSelectionStart, origSelectionEnd);
    }else{
      target.textContent = "";
    }
    target.remove();
    elem.focus();

    if(succeed){
      elem.classList.add('success');
      setTimeout(function(){
        elem.classList.remove('success');
      }, 1500);
    } else {
      elem.classList.add('error');
      setTimeout(function(){
        elem.classList.remove('error');
      }, 1500);
    }
  }

  Drupal.behaviors.CopyClick = {
    attach: function(){
      $('.gtext-click-copy[data-copy-text]:not(.click-processed)').addClass('click-processed').click(function(){
        copyButtonClick(this, $(this).data('copy-text').replaceAll('\\n', "\n").replaceAll('\\t', "\t"));
      })
    }
  }
})(jQuery)