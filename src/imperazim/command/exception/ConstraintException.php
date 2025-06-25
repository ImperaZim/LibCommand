<?php

declare(strict_types = 1);

namespace imperazim\command\exception;

/**
* Exception thrown when command constraints are not satisfied
*
* This exception is typically thrown when:
* - Permission checks fail
* - Execution environment requirements aren't met
* - Custom constraint validations fail
*
* @package imperazim\command\exception
*/
class ConstraintException extends \RuntimeException {}