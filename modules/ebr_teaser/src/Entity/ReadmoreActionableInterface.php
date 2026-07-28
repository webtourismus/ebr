<?php

declare(strict_types=1);

namespace Drupal\ebr_teaser\Entity;

use Drupal\ebr\Entity\ActionableInterface;

/**
 * A "read more" link, a replacement for Drupal core's pseudofield.
 */
interface ReadmoreActionableInterface extends ActionableInterface {
  /**
   * A "read more" button to get more information on this entity.
   */
  public const ACTION_READMORE = 'readmore';
}
