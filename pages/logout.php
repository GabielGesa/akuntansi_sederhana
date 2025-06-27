<?php
logoutUser();
setFlashMessage('Anda telah logout', 'info');
redirect('/login');
?>