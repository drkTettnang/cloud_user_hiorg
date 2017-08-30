$(document).ready(function() {

   $('#user_hiorg').submit(function(event) {
      event.preventDefault();

      var post = $("#user_hiorg").serialize();

      $.post(OC.filePath('user_hiorg', 'ajax', 'setSettings.php'), post, function(data) {
         if (data === 'true') {
            $('#user_hiorg .msg').text('Finished saving.');
         } else {
            $('#user_hiorg .msg').text('Error!');
         }
      });
   });

});
