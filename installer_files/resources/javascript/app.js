//
// Zurb Foundation stuff
//
;(function ($, window, undefined) {
  'use strict';

  var $doc = $(document),
      Modernizr = window.Modernizr;

  $(document).ready(function() {
    $.fn.foundationAlerts           ? $doc.foundationAlerts() : null;
    $.fn.foundationButtons          ? $doc.foundationButtons() : null;
    $.fn.foundationAccordion        ? $doc.foundationAccordion() : null;
    $.fn.foundationNavigation       ? $doc.foundationNavigation() : null;
    $.fn.foundationTopBar           ? $doc.foundationTopBar() : null;
    $.fn.foundationCustomForms      ? $doc.foundationCustomForms() : null;
    $.fn.foundationMediaQueryViewer ? $doc.foundationMediaQueryViewer() : null;
    $.fn.foundationTabs             ? $doc.foundationTabs({callback : $.foundation.customForms.appendCustomMarkup}) : null;
    $.fn.foundationTooltips         ? $doc.foundationTooltips() : null;
    $.fn.foundationMagellan         ? $doc.foundationMagellan() : null;
    $.fn.foundationClearing         ? $doc.foundationClearing() : null;

    $.fn.placeholder                ? $('input, textarea').placeholder() : null;
  });

  // UNCOMMENT THE LINE YOU WANT BELOW IF YOU WANT IE8 SUPPORT AND ARE USING .block-grids
  // $('.block-grid.two-up>li:nth-child(2n+1)').css({clear: 'both'});
  // $('.block-grid.three-up>li:nth-child(3n+1)').css({clear: 'both'});
  // $('.block-grid.four-up>li:nth-child(4n+1)').css({clear: 'both'});
  // $('.block-grid.five-up>li:nth-child(5n+1)').css({clear: 'both'});

  // Hide address bar on mobile devices (except if #hash present, so we don't mess up deep linking).
  if (Modernizr.touch && !window.location.hash) {
    $(window).load(function () {
      setTimeout(function () {
        window.scrollTo(0, 1);
      }, 0);
    });
  }

})(jQuery, this);

(function ($) {
  // Setup the simple form validation
  $(document).ready(function() {
    $('[data-provides]').each(function(index, element){
      var enablers = $('[data-enables='+$(this).data('provides')+']');

      function handleEnablerChanged() {
        if ($(element).closest('form').data('disabled'))
          return;

        var 
          emptyFound = false,
          emptyLabel = false,
          emptyField = false;

        enablers.each(function(){
          var 
            isCheckbox = $(this).prop("type").toLowerCase() == 'checkbox',
            empty = isCheckbox ? !this.checked : this.value.trim().length == 0;

          if (empty) {
            emptyFound = true;
            emptyField = $(this);

            var label = !isCheckbox ? $('label', $(this).parent()) : $(this).parent();

            if (label.length) {
              emptyLabel = label.data('validation-text');
              if (!emptyLabel || !emptyLabel.length)
                emptyLabel = label.text();
            }

            return false;
          }
        });

        $(element).attr('disabled', emptyFound);

        var hint = $('[data-visual="submit-hint"]');
        if (hint.length) {
          if (emptyFound) {
            hint.text('Please fill the "'+ emptyLabel +'" field.');
            hint.removeClass('hidden');

            hint.off('click');
            hint.on('click', function(){
              emptyField.focus();
            });
          } else
            hint.addClass('hidden');
        }
      }

      enablers.keyup(handleEnablerChanged);
      enablers.change(handleEnablerChanged);
      handleEnablerChanged();

      // Setup the AJAX validation handlers
      $('form[data-validate]').each(function(){
        var form = $(this);

        if (form.data('validated'))
          return true;

        form.data('validated', false);

        form.on('submit', function(){
          $.ajax({
            type: "POST",
            url: form.attr('action'),
            data: form.serialize()+'&validation=1',
            error: function(jqXHR, textStatus, errorThrown) {
              var responseJson = null;

              try {
                responseJson = $.parseJSON(jqXHR.responseText);
              } catch(err) {}

              if (responseJson) {
                $('.alert-box.alert').remove();

                $('div.error').removeClass('error');
                var alertBox = $('<div class="alert-box alert floating"/>').text(responseJson.error).appendTo(form);

                alertBox.animate({'margin-left': '-10'}, 50)
                  .animate({'margin-left': '10'}, 50)
                  .animate({'margin-left': '-10'}, 50)
                  .animate({'margin-left': 0}, 50);

                if (responseJson.field) {
                  $('#'+responseJson.field).closest('div.columns').addClass('error');
                  $('#'+responseJson.field).focus();
                }
              } else if (errorThrown)
                alert('Error sending the AJAX request: '+errorThrown);
              else if (textStatus)
                alert('Error sending the AJAX request: '+textStatus);
              else
                alert('Error sending the AJAX request');
            },
            success: function() {
              form.data('validated', true);
              form[0].submit();
            },
            complete: function() {
              form.data('disabled', false);
              handleEnablerChanged();
            }
          });

          return false;
        })
      });
    });

    //
    // Handle some step-specific events
    //

    $('[data-provides="eula-step-next"]').on('click', function(){
      if (!$('#agree').prop('checked')) {
        alert('You must agree to the License Agreement to continue.');

        return false;
      }
    });

    $('#enable_im').on('change', function(){
      if (this.checked) {
        $('#im_config').removeClass('hide');
        $('#convert_path').focus();
      } else
        $('#im_config').addClass('hide');
    });
    $('.screenshot img').draggable({ containment: "parent" });

    var installButton = $('[data-provides="install-lemonstand"]');
    if (installButton.length) {
      $('<img/>')[0].src = 'installer_files/resources/images/indicator.gif';

      installButton.on('click', function(){
        //installButton.closest('form').submit();

        installButton.addClass('hide');
        $('.progress-indicator').removeClass('hidden');
        return true;
      });
    }
  });
})(jQuery);

