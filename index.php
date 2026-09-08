<?php

/**
 * Marketian Mind - Shared Hosting Entry Point Bridge
 *
 * This file serves as a front-controller fallback bridge for shared hosting
 * environments (such as Hostinger) where the document root points to the repository root.
 * It cleanly delegates request processing to the official Laravel public entry point.
 */

require_once __DIR__ . '/public/index.php';
