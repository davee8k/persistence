<?php
declare(strict_types=1);

namespace Persistence\Attr;

use Attribute;

/**
 * Indicates the possible multicolumn key in database
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class UniqueId extends Column
{

}
