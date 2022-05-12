<?php

namespace Drupal\fielditem_renderby_view\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;

/**
 * Plugin implementation of the 'field_example_simple_text' formatter.
 *
 * @FieldFormatter(
 *   id = "fielditem_renderby_view_formatter",
 *   module = "fielditem_renderby_view",
 *   label = @Translation("Rendu via une  view"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class fieldFormatterStyleView extends EntityReferenceEntityFormatter {
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $entity_type_id = 'view';
    $args = [
      'none'
    ];
    if (!$items->isEmpty()) {
      $args = [];
      foreach ($items->getValue() as $value) {
        if (!empty($value['target_id']))
          $args[] = $value['target_id'];
      }
    }
    $args = implode(",", $args);
    $currentView = $this->getSetting('view_name');
    $display_view_id = $this->getSetting('display_view_id');
    if (!empty($currentView)) {
      $view = $this->entityTypeManager->getStorage($entity_type_id)->load($currentView);
      /**
       *
       * @var ViewExecutable $viewExecute
       */
      $viewExecute = $view->getExecutable();
      $viewExecute->setDisplay($display_view_id);
      $viewExecute->setArguments([
        $args
      ]);
      $r = $viewExecute->render($display_view_id);
      if ($r)
        return $r;
    }
    return $elements;
  }
  
  protected function loadAllViews($reference_field) {
    $entity_type_id = 'view';
    $views = $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple();
    /**
     * Contient les vues correspondants à l'entity reference.
     *
     * @var array $viewsEntity
     */
    $viewsEntity = [];
    foreach ($views as $k => $view) {
      $base_table = $view->get('base_table');
      if ($base_table == $reference_field) {
        $viewsEntity[$k] = $view->label();
      }
    }
    return $viewsEntity;
  }
  
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = parent::settingsForm($form, $form_state);
    $settings = $this->getFieldSettings();
    $options = [];
    // dump($settings);
    if (!empty($settings['target_type'])) {
      $options = $this->loadAllViews($settings['target_type'] . '_field_data');
    }
    $currentView = $this->getSetting('view_name');
    // dump($currentView);
    $settings['view_name'] = [
      '#type' => 'select',
      '#title' => ' Le nom de la vue ',
      '#default_value' => $currentView,
      '#options' => $options
    ];
    // $settings['view_empty_ids'] = [
    // '#type' => 'textfield',
    // '#title' => ' definisez les ids pour les ',
    // '#default_value' => $currentView,
    // '#options' => $options
    // ];
    /**
     *
     * @deprecated, car on ne sait pas faire ajax.
     */
    if ($currentView) {
      $entity_type_id = 'view';
      $optionsDisplay = [];
      $view = $this->entityTypeManager->getStorage($entity_type_id)->load($currentView);
      if (!empty($view)) {
        foreach ($view->get('display') as $k => $value) {
          $optionsDisplay[$k] = $value['display_title'];
        }
      }
      $settings['display_view_id'] = [
        '#type' => 'select',
        '#title' => ' Le nom de la view ',
        '#default_value' => $this->getSetting('display_view_id'),
        '#options' => $optionsDisplay
      ];
    }
    return $settings;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'view_name' => '',
      'display_view_id' => ''
    ] + parent::defaultSettings();
  }
  
}