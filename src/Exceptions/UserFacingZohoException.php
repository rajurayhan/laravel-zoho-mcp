<?php

declare(strict_types=1);

namespace LaravelZohoMcp\Exceptions;

/**
 * Recoverable Zoho or configuration errors surfaced to MCP clients as tool errors.
 */
final class UserFacingZohoException extends \RuntimeException {}
