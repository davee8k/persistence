<?php declare(strict_types=1);

namespace Persistence\Attr;

use Attribute;

/**
 * Call marked function on update
 */
#[Attribute(Attribute::TARGET_METHOD)]
class EventUpdate {
}
