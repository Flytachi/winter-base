<?php

declare(strict_types=1);

namespace Flytachi\Winter\Base\DI;

use Attribute;

/**
 * Marks a property for automatic dependency injection.
 *
 * The Container resolves the dependency by the property's declared type.
 * The class must be resolvable — either via Container::bind() or by having
 * a constructor whose parameters are themselves injectable.
 *
 * Example:
 * ```
 *   class UserController extends Controller
 *   {
 *       #[Autowired]
 *       private UserService $userService;
 *   }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Autowired {}
