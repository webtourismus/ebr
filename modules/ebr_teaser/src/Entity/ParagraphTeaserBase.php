<?php

declare(strict_types = 1);

namespace Drupal\ebr_teaser\Entity;

use Drupal\ebr\Entity\ActionableInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * The entity bundle class used by linkblocks and icon paragraphs.
 */
class ParagraphTeaserBase extends Paragraph implements ParagraphTeaserableInterface, ActionableInterface {
  use ParagraphTeaserableTrait;
}
