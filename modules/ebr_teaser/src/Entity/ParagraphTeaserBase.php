<?php

declare(strict_types=1);

namespace Drupal\ebr_teaser\Entity;

use Drupal\ebr\Entity\ActionableInterface;
use Drupal\paragraphs\Entity\Paragraph;

class ParagraphTeaserBase extends Paragraph implements ParagraphTeaserableInterface {
  use ParagraphTeaserableTrait;
}
