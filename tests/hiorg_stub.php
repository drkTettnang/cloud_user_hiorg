<?php
if ($_POST ['password'] == 'foo') {
   header ( 'Location: ./hiorg_stub2.php?token=cyUqaVAZTxdSUVJNS0pZSsefsZQEkGVVxFDAM-' );
   exit ();
} else {
   echo "Wrong password!";
}
?>