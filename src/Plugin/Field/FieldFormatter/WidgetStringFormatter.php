<?php

namespace Drupal\ebr\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\BasicStringFormatter;

/**
 * Plugin implementation of the 'basic_string' formatter.
 *
 * @FieldFormatter(
 *   id = "widget_string",
 *   label = @Translation("Web widget source code"),
 *   field_types = {
 *     "widget_string",
 *   }
 * )
 */
class WidgetStringFormatter extends BasicStringFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $widgetType = $items->getSetting('widget_type');
    $entity = $items->getEntity();

    foreach ($items as $delta => $item) {
      // The text value is the name of the widget theme hook/template.
      $elements[$delta] = [
        '#theme' => $item->value,
        '#widget_type' => $widgetType,
        '#label' => $entity->getWidgetLabel($widgetType)->render(),
        '#entity' => $entity,
      ];
    }

    return $elements;
  }

}
