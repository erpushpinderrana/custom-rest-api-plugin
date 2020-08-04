<?php

namespace Drupal\custom_rest_api\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Restful Custom Text formatter.
 *
 * @FieldFormatter(
 *   id = "restful_text_formatter",
 *   module = "custom_rest_api",
 *   label = @Translation("Restful Text Formatter"),
 *   field_types = {
 *     "body",
 *     "string",
 *     "string_long",
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   }
 * )
 */
class RestfulTextFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $field_value = $items->value;
    $elements['#key'] = 'custom_text_key';
    $elements['#value'] = $field_value;
    return $elements;
  }

}
