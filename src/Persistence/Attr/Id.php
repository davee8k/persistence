<?php declare(strict_types=1);

namespace Persistence\Attr;

use Attribute;

/**
 * Indicates the primary key (autoincrement) in database
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Id extends Column {
}
