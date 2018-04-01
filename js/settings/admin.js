$(function() {
   $('#user_hiorg').submit(function(event) {
      event.preventDefault();

      var post = $("#user_hiorg").serialize();

      $.ajax({
          method: 'PUT',
          url: OC.generateUrl('apps/user_hiorg/settings/'),
          data: post,
          success: function(response) {
            if (response && response.status === 'success') {
                $('#user_hiorg .msg').text('Finished saving.');
            } else {
                $('#user_hiorg .msg').text('Error!');
            }
          },
          error: function() {
            $('#user_hiorg .msg').text('Error!');
          }
      });
   });
});
