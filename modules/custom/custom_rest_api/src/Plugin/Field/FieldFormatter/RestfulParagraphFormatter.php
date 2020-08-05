<?php

namespace Drupal\custom_rest_api\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\custom_rest_api\Plugin\rest\resource\APIEntityResourceTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Restful paragraph formatter.
 *
 * @FieldFormatter(
 *   id = "restful_paragraph_formatter",
 *   module = "custom_rest_api",
 *   label = @Translation("Restful Paragraph Formatter"),
 *   field_types = {
 *     "entity_reference_revisions",
 *   },
 * )
 */
class RestfulParagraphFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  use APIEntityResourceTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $entity_type_manager);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $entity_output = [];
    if (count($items) > 0) {
      foreach ($items as $delta => $item) {
        $target_id = $item->getValue()['target_id'];

        $entity = $this->entityTypeManager->getStorage('paragraph')->load($target_id);
        if (!empty($entity)) {
          $entity_output[] = $this->keyValueResourceResponse($entity);
        }
      }
    }
    $elements['#key'] = 'custom_paragraph_key';
    $elements['#value'] = $entity_output;
    return $elements;
  }

}
