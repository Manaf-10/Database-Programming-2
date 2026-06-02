<?php
require_once __DIR__ . '/../includes/error_page.php';
renderErrorPage(500, 'Server Error', 'Something went wrong while loading the page. Please try again later.');
