<?php
/**
 * Clears Zend OP cache.
 */
if (function_exists("opcache_reset")) {
    opcache_reset();
}
