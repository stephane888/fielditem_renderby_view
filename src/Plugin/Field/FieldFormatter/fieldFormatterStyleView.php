<?php

namespace Drupal\fielditem_renderby_view\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Plugin implementation of the 'field_example_simple_text' formatter.
 *
 * @FieldFormatter(
 *   id = "fielditem_renderby_view_formatter",
 *   module = "fielditem_renderby_view",
 *   label = @Translation("Rendu via une view avec filtre contextuel"),
 *   field_types = {
 *     "entity_reference",
 *     "entity_reference_revisions"
 *   }
 * )
 */
class fieldFormatterStyleView extends FormatterBase {
  
  /**
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'view_name' => null,
      'display_view_id' => null,
      'view_arguments' => [],
      'view_filters' => [],
      'configure_view' => [
        'display_view_id' => null
      ]
    ] + parent::defaultSettings();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    if (!$items->isEmpty()) {
      $args = [];
      $viewId = $this->getSetting('view_name');
      $configure_view = $this->getSetting('configure_view');
      $display_view_id = !empty($configure_view['display_view_id']) ? $configure_view['display_view_id'] : $this->getSetting('display_view_id');
      foreach ($items->getValue() as $value) {
        if (!empty($value['target_id']))
          $args[] = $value['target_id'];
      }
      $args = implode(",", $args);
      /**
       *
       * @var \Drupal\views\ViewExecutable $viewExecute
       */
      $viewExecute = Views::getView($viewId);
      if ($viewExecute) {
        $viewExecute->setDisplay($display_view_id);
        $viewExecute->initHandlers();
        $viewExecute->setArguments([
          $args
        ]);
        $elements = $viewExecute->render($display_view_id);
      }
    }
    return $elements;
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter::settingsForm()
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    /**
     * Information de configuration du champs.
     *
     * @var array $settings
     */
    $settings = $this->getFieldSettings();
    // \Stephane888\Debug\debugLog::kintDebugDrupal($settings,
    // 'fieldFormatterStyleView__settingsForm', true);
    
    $elements['view_name'] = [
      '#title' => $this->t(' Select view'),
      '#type' => 'select',
      '#options' => $this->getViews(),
      '#required' => TRUE,
      '#default_value' => $this->getSetting('view_name'),
      '#ajax' => [
        'callback' => self::class . '::SelectViewAndConfigure',
        'wrapper' => 'creation_site_virtuel_view_name_display_id',
        'effect' => 'fade'
      ]
    ];
    $elements['configure_view'] = [
      '#type' => 'details',
      '#open' => true,
      '#title' => t('Select display and configure view'),
      '#attributes' => [
        'id' => 'creation_site_virtuel_view_name_display_id'
      ],
      '#tree' => true
    ];
    $view_name_display = $this->getSetting('view_name') ? $this->getSetting('view_name') : $form_state->getValue('view_name');
    if (!empty($view_name_display)) {
      $configure_view = $this->getSetting('configure_view');
      $elements['configure_view']['display_view_id'] = [
        '#title' => $this->t(' Select display '),
        '#type' => 'select',
        '#options' => $this->getViewDisplays($view_name_display),
        '#required' => TRUE,
        '#default_value' => $configure_view['display_view_id']
      ];
    }
    return $elements;
  }
  
  /**
   * On charge toutes les vues.
   */
  protected function getViews() {
    $options = [];
    $query = \Drupal::entityQuery('view')->condition('status', TRUE)->accessCheck(TRUE);
    $ids = $query->execute();
    if ($ids) {
      $views = \Drupal::entityTypeManager()->getStorage('view')->loadMultiple($ids);
      
      foreach ($views as $view) {
        /**
         *
         * @var \Drupal\views\Entity\View $view
         */
        // if ($view->id() == 'clothings') {
        // $viewExecutable = $view->getExecutable();
        // \Stephane888\Debug\debugLog::$max_depth = 5;
        // \Stephane888\Debug\debugLog::kintDebugDrupal($viewExecutable,
        // 'getViews', true);
        // }
        // il faudra touver le moyen de charger uniquement les views
        // contextuels.
        $options[$view->id()] = $view->label();
      }
    }
    return $options;
  }
  
  protected function getViewDisplays($view_name) {
    $options = [];
    /**
     *
     * @var \Drupal\views\ViewExecutable $View
     */
    $View = Views::getView($view_name);
    if ($View) {
      $displays = $View->storage->get('display');
      foreach ($displays as $display_id => $v) {
        // $View->setDisplay($display_id);
        $options[$display_id] = $v['display_title'];
      }
    }
    return $options;
  }
  
  /**
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public static function SelectViewAndConfigure($form, FormStateInterface $form_state) {
    return $form['settings']['formatter']['settings_wrapper']['settings']['configure_view'];
  }
}