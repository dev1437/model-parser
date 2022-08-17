<?php

namespace Dev1437\ModelParser\Enums;

/**
 * @property ADMIN - Can do anything
 * @property USER - Standard read-only
 */
enum UserRoleEnum: int
{
    case ADMIN = 0;
    case USER = 1;
}
