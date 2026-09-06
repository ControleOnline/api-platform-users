<?php
namespace ControleOnline\Service;

/**
 * Test stub: real DomainService lives in a shared package at runtime.
 */
if (!class_exists(DomainService::class, false)) {
    class DomainService
    {
        public function getDomain(): string
        {
            return '';
        }
    }
}
