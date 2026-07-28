<?php

declare(strict_types=1);

namespace Drupal\ebr\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\BasicStringFormatter;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ebr\Entity\WidgetableInterface;

/**
 * Plugin implementation of the widget_string formatter.
 */
#[FieldFormatter(
  id: 'widget_string',
  label: new TranslatableMarkup('Web widget source code'),
  field_types: ['widget_string'],
)]
class WidgetStringFormatter extends BasicStringFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    $widgetType = $items->getSetting('widget_type');
    $entity = $items->getEntity();

    foreach ($items as $delta => $item) {
      if (!($entity instanceof WidgetableInterface) || empty($item->value)) {
        continue;
      }
      $label = $entity->getWidgetLabel($widgetType);
      // The text value is the name of the widget theme hook/template.
      $elements[$delta] = [
        '#theme' => $item->value,
        '#widget_type' => $widgetType,
        '#label' => $label instanceof TranslatableMarkup ? $label->render() : (string) ($label ?? ''),
        '#entity' => $entity,
      ];
      foreach ($entity->getWidgetVariables($widgetType) ?? [] as $key => $value) {
        $elements[$delta]['#' . $key] = $value;
      }
    }

    return $elements;
  }

}
