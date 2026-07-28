<?php

declare(strict_types=1);

namespace Drupal\ebr_popup\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'uuid' widget.
 */
#[FieldWidget(
  id: 'uuid',
  label: new TranslatableMarkup('UUID'),
  field_types: ['uuid'],
)]
class UuidFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state
  ): array {
    $element['value'] = $element + [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#default_value' => $items[$delta]->value ?? NULL,
    ];
    return $element;
  }

}
