<?php

declare(strict_types=1);

namespace Drupal\ebr\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A content entity potentially providing contextual JS web widgets.
 */
interface WidgetableInterface extends ContentEntityInterface {

  /**
   * The prefix for all widget field names.
   */
  public const WIDGET_FIELD_PREFIX = 'widget_';

  /**
   * Returns all default widget IDs for this entity bundle type.
   *
   * This function is used to provide a default set of configurable fields
   * in field UI. For the real computed set of actually existing widgets,
   * use getWidgetFieldnames() instead!
   *
   * @return string[]
   */
  public static function getDefaultWidgets(): array;

  /**
   * Returns the human readable label of a widget.
   *
   * This function is used to provide a labels for default widgets
   * in field UI.
   */
  public static function getDefaultWidgetLabel(string $widgetId): TranslatableMarkup|string|NULL;

  /**
   * Returns names of available widgets fields for this entity.
   *
   * The array keys are the available widgets IDs, the array values are their
   * corresponding field (or render array) names injected into the entities
   * "content" render array for use in Twig.
   */
  public function getWidgetFieldnames(): array;

  /**
   * Returns the human readable label of a web widget.
   */
  public function getWidgetLabel(string $widgetId): TranslatableMarkup|string|NULL;

  /**
   * Returns values required to build the widgets render array.
   *
   * @return mixed[]
   *  An array of mixed values indexed by the Twig variable name.
   */
  public function getWidgetVariables(string $widgetId): ?array;


  /**
   * Returns a web widget as render array.
   */
  public function getRenderedWidget(string $widgetId, string $viewMode = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE): ?array;
}
