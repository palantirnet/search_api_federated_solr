<?php
/**
 * @file
 * Contains \Drupal\search_api_federated_solr\Plugin\field\field_type\FederatedTerms.
 */

namespace Drupal\search_api_federated_solr\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'federated_terms' field type.
 *
 * @FieldType(
 *   id = "federated_terms",
 *   label = @Translation("Federated terms"),
 *   description = @Translation("Stores the solr search api federated term destination value for taxonomy terms."),
 *   default_widget = "federated_terms_textfield",
 *   default_formatter = "string",
 *   cardinality = -1,
 * )
 */
class FederatedTerms extends FieldItemBase {
  const FEDERATED_TERMS_MAXLENGTH = 255;


  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_length' => static::FEDERATED_TERMS_MAXLENGTH,
      'is_ascii' => FALSE,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => $field_definition->getSetting('is_ascii') === TRUE ? 'varchar_ascii' : 'varchar',
          'length' => static::FEDERATED_TERMS_MAXLENGTH,
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    if ($max_length = $this->getSetting('max_length')) {
      $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', [
        'value' => [
          'Length' => [
            'max' => static::FEDERATED_TERMS_MAXLENGTH,
            'maxMessage' => t('%name: may not be longer than @max characters.', ['%name' => $this->getFieldDefinition()->getLabel(), '@max' => static::FEDERATED_TERMS_MAXLENGTH]),
          ],
        ],
      ]);
    }
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')->setLabel(t('Federated terms destination value'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];

    $element['max_length'] = [
      '#type' => 'number',
      '#title' => t('Maximum length'),
      '#default_value' => $this->getSetting('max_length'),
      '#required' => TRUE,
      '#description' => t('The maximum length of the field in characters.'),
      '#min' => 1,
      '#disabled' => $has_data,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return empty($value);
  }
}
