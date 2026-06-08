<?php

/*
 *---------------------------------------------------------------
 * ROOT INDEX.PHP - PROXY TO PUBLIC/INDEX.PHP
 *---------------------------------------------------------------
 * 
 * This file acts as a proxy to redirect all requests to the
 * actual front controller in the public/ directory.
 * 
 * This ensures FCPATH is correctly set to public/ directory,
 * which is critical for file uploads and asset management.
 * 
 * DO NOT modify this file to define FCPATH here, as it will
 * cause uploads to go to the wrong directory.
 */

// Redirect to the actual front controller in public/ directory
require __DIR__ . '/public/index.php';
