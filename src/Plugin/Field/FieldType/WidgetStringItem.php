<?php

declare(strict_types = 1);

namespace Drupal\ebr\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem;
use Drupal\ebr\EntityBusinessrules;

/**
 * Renders a web widget by using the computed value as Twig template name.
 *
 * @FieldType(
 *   id = "widget_string",
 *   label = @Translation("Web widget"),
 *   description = @Translation("A computed, contextual web widget."),
 *   default_widget = "string_textarea",
 *   default_formatter = "widget_string",
 * )
 */
class WidgetStringItem extends StringLongItem {

  /**
   * Whether or not the value has been calculated.
   */
  protected bool $isCalculated = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'widget_type' => '',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    $this->ensureCalculated();
    return parent::__get($name);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $this->ensureCalculated();
    return parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $this->ensureCalculated();
    return parent::getValue();
  }

  /**
   * Calculates the value of the field and sets it.
   */
  protected function ensureCalculated() {
    if (!$this->isCalculated) {
      /** @var \Drupal\ebr\Entity\WidgetableInterface $entity */
      $entity = $this->getEntity();
      if (!$entity->isNew()) {
        $widgetType = $this->getSetting('widget_type');
        $datasource = $entity->get(EntityBusinessrules::FIELD_REMOTE_DATASOURCE)->value;
        $remoteId = $entity->get(EntityBusinessrules::FIELD_REMOTE_ID)->value;
        $value = [
          'value' => NULL,
        ];
        if ($widgetType && $datasource && $remoteId) {
          $value = [
            // The value is the name of the twig template containing the widget code.
            'value' => "{$datasource}_{$widgetType}",
          ];
        }
        $this->setValue($value);
      }
      $this->isCalculated = TRUE;
    }
  }

}
