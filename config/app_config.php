<?php
// ─── EmailJS (client-side, set your keys here) ────────────────────────────
// Get these from https://dashboard.emailjs.com
define('EMAILJS_PUBLIC_KEY',       'm-AvAiAdUDsgBbz6D');
define('EMAILJS_SERVICE_ID',       'service_vr6ygvx');
define('EMAILJS_BOOKING_TEMPLATE', 'template_6f0xj0q');    // sent to patient on booking
define('EMAILJS_CONFIRM_TEMPLATE', 'template_y0hswec');    // sent to patient on confirmation

// ─── OpenAI (server-side) ─────────────────────────────────────────────────
// Get from https://platform.openai.com/api-keys
define('OPENAI_API_KEY', 'YOUR_OPENAI_API_KEY');
define('OPENAI_MODEL',   'gpt-3.5-turbo');