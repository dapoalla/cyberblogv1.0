<?php
return [
  'site_name' => 'Cyberrose Blog',
  'base_url' => '',
  // Centralized version string for display across the app
  'version' => 'v1.0',
  // Donation addresses (fill in as needed). Only entries with non-empty
  // addresses will appear on the About page.
  'donations' => [
    'USDT_BEP20' => [
      'label' => 'Tether (USDT) — BNB Smart Chain (BEP20)',
      'address' => '0xa4C9677FDBaC8F1eAB0234585d98ED0059b9d5aD',
      'qr' => 'assets/images/buymeacoffee-qr.png',
      'warning' => 'Only send Tether USD (BEP20) assets to this address.',
    ],
    'SMART_WALLET_EVM' => [
      'label' => 'Smart Wallet — ETH, Arbitrum, BNB Chain, Polygon',
      'address' => '0xa4C9677FDBaC8F1eAB0234585d98ED0059b9d5aD',
      'qr' => '',
      'networks' => ['Ethereum', 'Arbitrum', 'BNB Chain', 'Polygon'],
      'warning' => 'Send assets on supported EVM networks only (same address across these).',
    ],
    // Optional networks — add your addresses to enable
    'USDT_TRC20' => [
      'label' => 'Tether (USDT) — TRON (TRC20)',
      'address' => '',
      'qr' => '',
      'warning' => 'Only send TRC20 assets to this address.',
    ],
    'BTC' => [
      'label' => 'Bitcoin (BTC)',
      'address' => '',
      'qr' => '',
      'warning' => '',
    ],
    'ETH' => [
      'label' => 'Ethereum (ETH)',
      'address' => '',
      'qr' => '',
      'warning' => '',
    ],
  ],
  'db' => [
    'host' => 'localhost',
    'name' => '07',
    'user' => '07',
    'pass' => '07',
    'port' => 3306,
  ],
  'security' => [
    'session_name' => 'cr_blog2_sess',
    'csrf_key' => 'e0a2de264f10e995f116778b08ddf2fd',
  ],
  'oauth' => [
    'client_id' => '',
    'client_secret' => '',
    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
    'token_uri' => 'https://oauth2.googleapis.com/token',
    'redirect_uri' => '',
  ],
];
