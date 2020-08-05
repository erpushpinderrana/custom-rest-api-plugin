<?php

namespace Drupal\custom_rest_api\Plugin\rest\resource;

/**
 * @internal
 * Internal functions.
 */
trait APIEntityResourceTrait {

  /**
   * Function to get key value response data.
   *
   * @param mixed $entity
   *   Entity object.
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
