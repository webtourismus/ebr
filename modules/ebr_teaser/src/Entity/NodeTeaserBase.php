<?php

declare(strict_types=1);

namespace Drupal\ebr_teaser\Entity;

use Drupal\ebr\Entity\ActionableInterface;
use Drupal\node\Entity\Node;

class NodeTeaserBase extends Node implements TeaserableInterface, ActionableInterface {
  use NodeTeaserableTrait;
  use ReadmoreActionTrait;
}
