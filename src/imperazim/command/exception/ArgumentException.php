<?php

declare(strict_types = 1);

namespace imperazim\command\exception;

use RuntimeException;

/**
* Exception thrown for argument-related errors in command processing
*
* This exception type is typically thrown when:
* - Argument parsing fails
* - Invalid argument values are detected
* - Argument validation constraints are violated
*
* @package imperazim\command\exception
*/
class ArgumentException extends RuntimeException {}