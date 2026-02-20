<?php
// ─── EmailJS 
define('EMAILJS_PUBLIC_KEY',       'm-AvAiAdUDsgBbz6D');
define('EMAILJS_SERVICE_ID',       'service_vr6ygvx');
define('EMAILJS_BOOKING_TEMPLATE', 'template_6f0xj0q');    // sent to patient on booking
define('EMAILJS_CONFIRM_TEMPLATE', 'template_y0hswec');    // sent to patient on confirmation

// ─── OpenAI 
// Get from https://platform.openai.com/api-keys wala pa for now 
define('OPENAI_API_KEY', 'YOUR_OPENAI_API_KEY');
define('OPENAI_MODEL',   'gpt-3.5-turbo');

define('GOOGLE_CLIENT_ID',     '901503175288-na0f91f6bppnfbthdl6cn7fbg8e5m0bi.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_LOGIN',    'http://localhost/TeleCare/auth/google-callback.php');
define('GOOGLE_REDIRECT_REGISTER', 'http://localhost/TeleCare/auth/google-register-callback.php');