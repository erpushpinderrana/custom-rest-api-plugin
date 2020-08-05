<?php

namespace Drupal\custom_rest_api\Plugin\rest\resource;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Represents entity as resource.
 *
 * @RestResource(
 *   id = "custom_entity_api",
 *   label = @Translation("Custom Entity REST API"),
 *   uri_paths = {
 *     "canonical" = "/api/entity/{type}/{id}",
 *   }
 * )
 */
class APIEntityResource extends ResourceBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Drupal\rest\Plugin\rest\resource\EntityResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, $serializer_formats, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest')
    );
  }

  /**
   * Responds to entity GET requests.
   *
   * @param string $type
   *   The entity type.
   * @param int $id
   *   The entity id.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the entity with its accessible fields.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get($type = 'node', $id = NULL) {

    $entity_data = [];
    try {
      switch ($type) {
        case 'node':
          $entity = $this->entityTypeManager->getStorage($type)->load($id);
          if (!empty($entity) && $entity->get('status')->value == 1) {
            $entity_data = $this->keyValueResourceResponse($entity);
          }
          else {
            return new ModifiedResourceResponse($this->t("No data found"), 200);
          }
          break;

        case 'taxonomy_term':
        case 'paragraph':
        case 'user':
          $entity = $this->entityTypeManager->getStorage($type)->load($id);
          if (!empty($entity)) {
            $entity_data = $this->keyValueResourceResponse($entity);
          }
          else {
            return new ModifiedResourceResponse($this->t("No data found"), 200);
          }
          break;

        default:
          throw new BadRequestHttpException($this->t('Not valid parameters.'));
      }

      if (!empty($entity_data)) {
        return new ModifiedResourceResponse($entity_data, 200);
      }
      else {
        return new ModifiedResourceResponse($this->t("No data found"), 200);
      }

    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, $this->t('Internal Server Error'), $e);
    }
  }

  /**
   * Function to get key value response data.
   */
  public function keyValueResourceResponse($entity) {
    $entity_output = [];
    $bundle = $entity->bundle();
    if (!empty($bundle)) {
      $field_definitions = $entity->getFieldDefinitions();

      $view_mode = 'default';
      $entityType = $entity->getEntityTypeId();
      $storage_id = $entityType . '.' . $entity->bundle() . '.' . $view_mode;

      $display = $this->entityTypeManager
        ->getStorage('entity_view_display')
        ->load($storage_id);
      $display_fields = $display->get('content');

      // Remove unwanted field that we don't want to display.
      $allowed_fields = '/(title|name|body|description|_picture|field_*)/';

      // Filter array to get only required fields.
      $fields = preg_grep($allowed_fields, array_keys($display_fields));

      // Get the final fields definition to work further.
      $fields_definition = array_intersect_key($field_definitions, array_flip($fields));

      // Pass the allowed field definitions along with entity.
      $entity_output = $this->convertEntityToKeyValue($entity, $fields_definition, $view_mode);
    }
    return $entity_output;
  }

  /**
   * Convert Entity fields to key:value combination.
   *
   * @param mixed $entity
   *   Entity specific to given content.
   * @param mixed $fields_definition
   *   Field definition of the content fields.
   * @param mixed $view_mode
   *   Display type of entity.
   *
   * @return mixed
   *   Return entity content.
   */
  public function convertEntityToKeyValue($entity, $fields_definition, $view_mode) {
    $field_data = [];

    if (!empty($fields_definition)) {
      foreach ($fields_definition as $field_name => $field) {
        if (isset($entity->get($field_name)->view($view_mode)['#key'])) {
          $key = $entity->get($field_name)->view($view_mode)['#key'];
          $field_data[$key] = $entity->get($field_name)->view($view_mode)['#value'];
        }
        else {
          $field_data[$field_name] = $this->convertFieldToKeyValue($field, $field_name, $entity);
        }
      }
    }
    return $field_data;
  }

  /**
   * Convert field to key:value combination.
   *
   * @param mixed $field
   *   Field Object.
   * @param mixed $field_name
   *   Field Name.
   * @param mixed $entity
   *   Entity Object.
   *
   * @return mixed
   *   Return entity content.
   */
  public function convertFieldToKeyValue($field, $field_name, $entity) {
    $field_data = [];
    if (!empty($entity->get($field_name)->getIterator())) {
      switch ($field->getType()) {
        case 'string':
        case 'text_with_summary':
        case 'text_long':
        case 'timestamp':
        case 'email':
          if ($field->getFieldStorageDefinition()->getCardinality() == 1) {
            $field_data = $entity->get($field_name)->value;
          }
          else {
            $field_data = [];
            foreach ($entity->get($field_name)->getIterator() as $value) {
              $field_data[] = $value->value;
            }
          }
          break;

        case 'image':
          if (!empty($entity->get($field_name)->entity)) {
            if ($field->getFieldStorageDefinition()->getCardinality() == 1) {
              $field_data['url'] = $entity->get($field_name)->entity->uri->value;
              $field_data['title'] = $entity->get($field_name)->title;
              $field_data['alt'] = $entity->get($field_name)->alt;
              $field_data['height'] = $entity->get($field_name)->height;
              $field_data['width'] = $entity->get($field_name)->width;
            }
            else {
              $field_data = [];
              foreach ($entity->get($field_name)->getIterator() as $key => $image) {
                $field_data[$key]['url'] = $image->entity->uri->value;
                $field_data[$key]['title'] = $image->title;
                $field_data[$key]['alt'] = $image->alt;
                $field_data[$key]['height'] = $image->height;
                $field_data[$key]['width'] = $image->width;
              }
            }
          }
          break;

        case 'link':
          $field_data = [];
          if ($field->getFieldStorageDefinition()->getCardinality() == 1) {
            $field_data['uri'] = $entity->get($field_name)->uri;
            $field_data['title'] = $entity->get($field_name)->title;
          }
          else {
            foreach ($entity->get($field_name)->getIterator() as $key => $link) {
              $field_data[$key]['uri'] = $link->uri;
              $field_data[$key]['title'] = $link->title;
            }
          }
          break;

        case 'list_string':
        case 'list_type':
          if ($field->getFieldStorageDefinition()->getCardinality() == 1) {
            $field_data = $entity->get($field_name)->value;
          }
          else {
            $field_data = [];
            foreach ($entity->get($field_name)->getIterator() as $list) {
              $field_data[] = $list->value;
            }
          }
          break;

        case 'key_value':
          if ($field->getFieldStorageDefinition()->getCardinality() == 1) {
            $field_data[$entity->get($field_name)->key] = $entity->get($field_name)->value;
          }
          else {
            foreach ($entity->get($field_name)->getIterator() as $key_value) {
              $field_data[$key_value->key] = $key_value->value;
            }
          }
          break;

        case 'entity_reference':
          $entity_type = $field->getFieldStorageDefinition()->getSetting('target_type');
          if (!empty($entity->get($field_name)->target_id)) {
            if ($field->getFieldStorageDefinition()->getCardinality() == 1) {
              $field_data = $this->keyValueResourceResponse($this->entityTypeManager->getStorage($entity_type)->load($entity->get($field_name)->target_id));
            }
            else {
              foreach ($entity->get($field_name)->getIterator() as $entity_id) {
                $field_data = $this->keyValueResourceResponse($this->entityTypeManager->getStorage($entity_type)->load($entity_id->target_id));
              }
            }
          }
          break;

        case 'entity_reference_revisions':
          if (!empty($entity->get($field_name)->target_id)) {
            // Iterate through paragraphs.
            $paragraph_fields = [];
            foreach ($entity->get($field_name)->getIterator() as $entity_id) {
              $paragraph = $this->entityTypeManager->getStorage('paragraph')->load($entity_id->target_id);
              $paragraph_fields[] = $this->keyValueResourceResponse($paragraph);
            }
            // Merge the paragraph field array to the parent field data array.
            $field_data = $paragraph_fields;
          }
          break;

        default:
          $field_data = '';
          break;
      }
    }
    return $field_data;
  }

}
