<?php
/**
 * Created by PhpStorm.
 * User: sergei
 * Date: 03.01.16
 * Time: 18:29
 */

namespace Drupal\entityreference_dragdrop\Plugin\Field\FieldWidget;


use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Plugin implementation of the 'entityreference_dragdrop' widget.
 *
 * @FieldWidget(
 *   id = "entityreference_dragdrop",
 *   label = @Translation("Drag&Drop"),
 *   description = @Translation("A widget allowing use drag&drop to edit the field."),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class EntityReferenceDragDropWidget extends OptionsWidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Out-of-box available view modes for entity item in select lists.
   */
  const VIEW_MODE_TITLE = 'title', // Display only entity title.
    VIEW_MODE_DEFAULT = 'default'; // Display entity using default view mode.

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entity_manager;

  /**
   * EntityReferenceDragDropWidget constructor.
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param array $third_party_settings
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityManagerInterface $entity_manager = NULL) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entity_manager = $entity_manager;
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
      $configuration['third_party_settings'],
      $container->get('entity.manager')
    );
  }

  public static function defaultSettings() {
    return array(
      'view_mode' => 'title',
      'available_entities_label' => t('Available entities'),
      'selected_entities_label' => t('Selected entities'),
    ) + parent::defaultSettings();
  }

  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['view_mode'] = array(
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => $this->viewModeOptions(),
      '#default_value' => $this->getSetting('view_mode'),
    );

    $element['available_entities_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Available entities label'),
      '#default_value' => $this->getSetting('available_entities_label'),
      '#description' => t('Enter a label that will be displayed above block with available entities.')
    );

    $element['selected_entities_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Selected entities label'),
      '#default_value' => $this->getSetting('selected_entities_label'),
      '#description' => t('Enter a label that will be displayed above block with selected entities.')
    );

    return $element;
  }

  public function settingsSummary() {
    $summary = array();
    $view_mode = $this->viewModeOptions()[$this->getSetting('view_mode')];
    $summary[] = $this->t('View mode: @view_mode', array('@view_mode' => $view_mode));
    $summary[] = $this->t('Available entities label: @label', array('@label' => $this->getSetting('available_entities_label')));
    $summary[] = $this->t('Selected entities label: @label', array('@label' => $this->getSetting('selected_entities_label')));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $entity_type = $items->getEntity()->getEntityTypeId();
    $bundle = $items->getEntity()->bundle();
    $entity_id = $items->getEntity()->id() ?: '0';
    $key = $entity_type . '_' . $bundle . '_' .  $field_name . '_' . $entity_id;
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();

    $selected = $this->getSelectedOptions($items);
    $available = $this->getAvailableOptions($items);

    $element['label'] = array(
      '#markup' => '<div class="entityreference-dragdrop-label">'
        . $this->fieldDefinition->getLabel() . '</div>',
    );
    if ($cardinality != -1) {
      $element['message'] = array(
        '#markup' => '<div class="entityreference-dragdrop-message" data-key="' . $key . '">'
          . $this->t('This field cannot hold more than @card values.', array('@card' => $cardinality)) . '</div>',
      );
    }
    $element['available'] = $this->availableOptionsToRenderableArray($available, $key);
    $element['selected'] = $this->selectedOptionsToRenderableArray($selected, $key);

    $element['target_id'] = array(
      '#type' => 'hidden',
      '#default_value' => implode(',', array_keys($selected)),
      '#element_validate' => array('entityreference_dragdrop_element_validate'),
      '#attached' => array(
        'library' => array('entityreference_dragdrop/init'),
        'drupalSettings' => array(
          'entityreference_dragdrop' => array(
            $key => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
          ),
        ),
      ),
      '#attributes' => array(
        'class' => array('entityreference-dragdrop-values'),
        'data-key' => array($key),
      ),
    );

    return $element;
  }

  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return explode(',', $values['target_id']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getSelectedOptions(FieldItemListInterface $items, $delta = 0) {
    // We need to check against a flat list of options.
    $flat_options = OptGroup::flattenOptions($this->getOptions($items->getEntity()));

    $selected_options = array();
    foreach ($items as $item) {
      $id = $item->{$this->column};
      // Keep the value if it actually is in the list of options (needs to be
      // checked against the flat list).
      if (isset($flat_options[$id])) {
        $selected_options[$id] = $flat_options[$id];
      }
    }

    return $selected_options;
  }

  protected function getAvailableOptions(FieldItemListInterface $items) {
    // We need to check against a flat list of options.
    $flat_options = OptGroup::flattenOptions($this->getOptions($items->getEntity()));
    $selected_options = $this->getSelectedOptions($items);

    $available_options = array();
    foreach ($flat_options as $id => $option) {
      if (!in_array($option, $selected_options)) {
        $available_options[$id] = $option;
      }
    }

    return $available_options;
  }

  protected function optionsToRenderableArray(array $options, $key, $list_title, array $classes = array(), array $wrapper_classes = array()) {
    $view_mode = $this->getSetting('view_mode');
    $target_type_id = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');
    $items = array();
    $entities = array();

    if ($view_mode !== static::VIEW_MODE_TITLE) {
      $entities = $this->entity_manager->getStorage($target_type_id)->loadMultiple(array_keys($options));
    }

    foreach ($options as $id => $entity_title) {
      $item = array(
        '#wrapper_attributes' => array(
          'data-key' => $key,
          'data-id' => $id,
        ),
      );
      if ($view_mode !== static::VIEW_MODE_TITLE) {
        $item += $this->entity_manager->getViewBuilder($target_type_id)->view($entities[$id]);
      }
      else {
        $item += array(
          '#markup' => $options[$id],
        );
      }
      $items[] = $item;
    }

    return array(
      '#theme' => 'item_list__entityreference_dragdrop_option_list',
      '#items' => $items,
      '#title' => $list_title,
      '#attributes' => array(
        'data-key' => $key,
        'class' => array_merge(array('entityreference-dragdrop'), $classes),
      ),
      '#wrapper_attributes' => array(
        'class' => array_merge(array('entityreference-dragdrop-container'), $wrapper_classes),
      ),
    );
  }

  protected function selectedOptionsToRenderableArray(array $options, $key) {
    return $this->optionsToRenderableArray(
      $options,
      $key,
      $this->getSetting('selected_entities_label'),
      array('entityreference-dragdrop-selected'),
      array('entityreference-dragdrop-container-selected')
    );
  }

  protected function availableOptionsToRenderableArray(array $options, $key) {
    return $this->optionsToRenderableArray(
      $options,
      $key,
      $this->getSetting('available_entities_label'),
      array('entityreference-dragdrop-available'),
      array('entityreference-dragdrop-container-available')
    );
  }

  protected function viewModeOptions() {
    $target_type_id = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');
    $view_modes = $this->entity_manager->getViewModes($target_type_id);
    $options = array(
      static::VIEW_MODE_TITLE => $this->t('Title'),
    );
    foreach ($view_modes as $view_mode) {
      $options[$view_mode['id']] = $view_mode['label'];
    }

    return $options;
  }
}
