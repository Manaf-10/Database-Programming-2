<?php
require_once __DIR__ . '/../includes/error_page.php';
renderErrorPage(400, 'Bad Request', 'The request could not be processed. Please check the address and try again.');
