/*===================================================================
Portal Navigation
===========================================================*/
function closeNotification() {
  var notification = document.querySelector('.latest-notification');
  notification.style.display = 'none';
}






$(document).ready(function () {
  // Attach event listeners for collapse events once
  $('[data-bs-toggle="collapse"]').each(function () {
    var $btn = $(this);
    var $icon = $btn.find('i.fa-angle-right');
    var targetCollapse = $btn.data('bs-target');

    $(targetCollapse).on('show.bs.collapse', function () {
      $icon.addClass('rotate');
    });

    $(targetCollapse).on('hide.bs.collapse', function () {
      $icon.removeClass('rotate');
    });
  });

  // Fix for initial state
  $('[data-bs-toggle="collapse"]').each(function () {
    var $btn = $(this);
    var $icon = $btn.find('i.fa-angle-right');
    var targetCollapse = $btn.data('bs-target');

    if ($(targetCollapse).hasClass('show')) {
      $icon.addClass('rotate');
    } else {
      $icon.removeClass('rotate');
    }
  });
});



$(document).ready(function () {
  // Close other collapsibles when one is opened
  $('.collapse').on('show.bs.collapse', function () {
    $('.collapse').not(this).collapse('hide');
  });

  // Automatically open the menu if an active link is inside
  $('.collapse').each(function () {
    if ($(this).find('a.active').length > 0) {
      $(this).collapse('show');
    }
  });
});


$(document).ready(function () {
  $('form').on('submit', function (e) {
    var form = $(this);
    var submitButton = form.find('.button');
    var buttonText = submitButton.html();

    // Trigger jQuery validation
    if (form.valid()) {
      // If the form is valid, disable the button and show loader
      submitButton.prop('disabled', true);

      // Add loader next to the button text
      submitButton.html(buttonText + ' <span class="spinner-border spinner-border-sm loader" role="status" aria-hidden="true"></span>');

      // Allow form submission
      return true;
    } else {
      // If form has errors, enable the button and remove loader
      submitButton.prop('disabled', false);

      // Remove loader and reset button text
      submitButton.html(buttonText);

      // Prevent form submission until validation is cleared
      return false;
    }
  });

  // Listen for any changes in the form inputs
  $('form input, form select, form textarea').on('input change', function () {
    var form = $(this).closest('form');
    var submitButton = form.find('.button');
    var buttonText = 'Processing!'; // Set your button text
    var isFormValid = true;

    // Revalidate the form fields in real-time
    form.find('input[required], select[required], textarea[required]').each(function () {
      if ($(this).val().trim() === '') {
        isFormValid = false;
      }
    });

    // Remove the loader and reset button if form is filled correctly
    if (isFormValid) {
      submitButton.prop('disabled', false); // Enable the button when the form is valid
      submitButton.html(buttonText); // Restore button text without loader
    } else {
      submitButton.prop('disabled', true); // Keep button disabled if the form is invalid
      submitButton.html(buttonText); // Ensure no loader is visible if invalid
    }
  });
});


$(document).ready(function () {
  $('.typing-text').each(function () {
    let element = $(this);
    let cursor = element.next('.typing-cursor'); // Get the cursor element
    let fullText = element.text();
    let index = 0;
    let speed = 100; // Typing speed in milliseconds
    element.text(''); // Clear the text content to start typing effect

    function typeText() {
      if (index < fullText.length) {
        element.append(fullText.charAt(index));
        index++;
        setTimeout(typeText, speed);
      } else {
        cursor.css('animation', 'none'); // Stop cursor blinking when typing is done
      }
    }

    typeText(); // Call the function to start typing for each element
  });
});

