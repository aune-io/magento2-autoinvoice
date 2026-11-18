<?php

/**
 * @codingStandardsIgnoreFile
 * @SuppressWarnings(PHPMD)
 */

// Autoloader: standalone checkout (module's own vendor/), or installed inside a Magento project (vendor/aune-io/<module>)
foreach ([
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../../autoload.php',
] as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;
        break;
    }
}

if (!function_exists('__')) {
    /**
     * Create value-object \Magento\Framework\Phrase
     * 
     * @return \Magento\Framework\Phrase
     */
    function __()
    {
        $argc = func_get_args();
        $text = array_shift($argc);
        if (!empty($argc) && is_array($argc[0])) {
            $argc = $argc[0];
        }
        return new \Magento\Framework\Phrase($text, $argc);
    }
}
