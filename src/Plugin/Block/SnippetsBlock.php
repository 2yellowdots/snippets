<?php

namespace Drupal\snippets\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Cache\Cache;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeCustomFormatter;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\datetime_range\DateTimeRangeTrait;

use Drupal\snippets\SnippetsQuery;
use Drupal\snippets\SnippetsPathAlias;
use Drupal\snippets\SnippetsTaxonomyTerms;
use Symfony\Component\DependencyInjection\ContainerInterface;



/**
 * Provides a 'Snippets' block.
 *
 * @Block(
 *   id = "snippets_block",
 *   admin_label = @Translation("Snippets block")
 * )
 */
class SnippetsBlock extends BlockBase implements ContainerFactoryPluginInterface {


  /**
   * Snippets query.
   *
   * @var \Drupal\snippets\SnippetsQuery
   */
  protected $SnippetsQuery;

  /**
   * Snippets taxonomy.
   *
   * @var \Drupal\snippets\SnippetsTaxonomyTerms
   */
  protected $SnippetsTaxonomyTerms;

  /**
   * Snippets path alias.
   *
   * @var \Drupal\snippets\SnippetsPathAlias
   */
  protected $SnippetsPathAlias;

  /**
   * Constructs an SnippetsBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The snippets query base.
   * @param \Drupal\snippets\SnippetsQuery $SnippetsQuery
   *   The taxomony terms manager.
   * @param \Drupal\snippets\SnippetsTaxonomyTerms $SnippetsTaxonomyTerms
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              SnippetsQuery $SnippetsQuery,
                              SnippetsTaxonomyTerms $SnippetsTaxonomyTerms,
                              SnippetsPathAlias $SnippetsPathAlias) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->categories = array();
    $this->entity_type = 'article';
    $this->nids = array();
    $this->nodes = array();
    $this->SnippetsQuery = $SnippetsQuery;
    $this->taxonomy = array();
    $this->SnippetsTaxonomyTerms = $SnippetsTaxonomyTerms;
    $this->SnippetsPathAlias = $SnippetsPathAlias;
    $this->storage_type = 'node';
    $this->vocabulary = array();
    $this->vocabulary_name = 'tags';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('SnippetsQuery'),
      $container->get('SnippetsTaxonomyTerms'),
      $container->get('SnippetsPathAlias')
    );
  }

  /**
   * Construct the snippets block using taxonomy
   *
   * @return array Themed
   */
  public function content() {

    // load the taxonomy vocabulary
    $this->getTaxonomy();

    $list = array();
    foreach ($this->taxonomy[$this->vocabulary_name] as $tid => $value) {
      // get the vocabulary details
      $this->SnippetsTaxonomyTerms->tid = $tid;
      $this->SnippetsTaxonomyTerms->getVocabulary();
      $this->term = $this->SnippetsTaxonomyTerms->term->name->value;
      // get the relevant articles for this vocabulary term
      $this->getTaxonomyArticles($tid);
    }

    array_multisort($this->vocabulary, SORT_DESC);

    // return an output for the screen
    return [
      '#theme' => 'snippets_panel',
      '#vocab' => $this->vocabulary,
    ];
  }

  /**
   * Load the articles of the curent ($tid) taxonomy vocabulary value
   *
   * @param  integer $tid Taxonomy vocabulary value
   */
  protected function getTaxonomyArticles($tid = 0) {

    $this->SnippetsQuery->initialiseQuery();
    // set the entity query for node
    $this->SnippetsQuery->setEntityType($this->entity_type);
    // get the query
    $this->query = $this->SnippetsQuery->getQuery();
    // add conditions
    $this->query->condition('field_tags.target_id', $tid);
    // reverse the output order
    $this->query->sort('changed', 'DESC');
    // set the query
    $this->SnippetsQuery->setQuery($this->query);
    // return the nids only
    $this->SnippetsQuery->setLoadNodeSwitch(FALSE);
    // execute the query and set the nids
    $this->SnippetsQuery->loadNodeEntity();
    // return the query to true so all queries return nodes
    $this->SnippetsQuery->setLoadNodeSwitch(TRUE);
    // get the node.nid keys
    $nids = $this->SnippetsQuery->getNids();

    foreach ($nids as $key => $nid) {
      // $valueList .= "key: ".$key." value:".$value." | ";
      if (!in_array($nid, $this->nodes)) {
        // get the articles for the current vocabulary
        $this->getNode($nid, $tid);
      }

    }

  }

  /**
   * Render the text in the panel
   *
   * @param  string $text [description]
   * @return [type]       [description]
   */
  protected function renderSnippetsPanel($name = "", $panel = "") {
    return twig_render_template(drupal_get_path('module', 'snippets') . '/templates/snippets-panel.html.twig', array(
      'name' => $name,
      'panel' => $panel,
      // Needed to prevent notices when Twig debugging is enabled.
      'theme_hook_original' => 'not-applicable',
    ));

  }

  /**
   * Render the text in the panel
   *
   * @param  string $text [description]
   * @return [type]       [description]
   */
  protected function renderSnippetsText($text = "") {
    return twig_render_template(drupal_get_path('module', 'snippets') . '/templates/snippets-panel-text.html.twig', array(
      'text' => $text,
      // Needed to prevent notices when Twig debugging is enabled.
      'theme_hook_original' => 'not-applicable',
    ));

  }

  /**
   * Alter the nodes array with the nids values
   */
  protected function getNode($nid = 0, $tid = 0) {
    // $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($this->nids);
    $storage = \Drupal::entityTypeManager()->getStorage($this->storage_type);
    // Load actual user data from database.
    $storage->resetCache();
    // load single value
    $node = $storage->load($nid);

    // generate the node title url link
    $url = Url::fromRoute('entity.node.canonical', array('node' => $nid));
    $node_link = Link::fromTextAndUrl($node->getTitle(), $url);
    // generate the taxonomy term url link
    $url = Url::fromRoute('entity.taxonomy_term.canonical', array('taxonomy_term' => $tid));
    $term_link = Link::fromTextAndUrl($this->term, $url);
    $readon_link = Link::fromTextAndUrl(t('see more @articles articles...', array('@articles' => $this->term)), $url);
    // body
    $body_value = $node->get('body')->getValue();
    $body = implode(' ', array_slice(explode(' ', $body_value[0]['value']), 0, 30));
    $body = preg_replace("/<[a-zA-Z\/][^>]*|&nbsp;|>/", '', $body);

    // format the date
    $this->getDate($node);

    $this->vocabulary[$tid][$this->timestamp] = array(
      'body' => $body,
      'date' => $this->date_formatted,
      'diff' => $this->time_diff,
      'nid' => $nid,
      'readon' => $readon_link->toRenderable(),
      'tags' => $node->get('field_tags')->target_id,
      'term' => $term_link->toRenderable(),
      'timestamp' => $this->timestamp,
      'title' => $node_link->toRenderable(),
    );

  }

  protected function getLink() {

  }

  /**
   * Format the date for the node
   *
   * @param  object $node [description]
   *
   */
  public function getDate($node) {
    $changed = $node->get('changed')->value;
    $created = $node->get('created')->value;
    $this->timestamp = ($changed > $created) ? $changed : $created;
    $this->date_formatted = \Drupal::service('date.formatter')->format($this->timestamp, 'custom', 'd m Y');

    $this->dateDifference();
  }

  /**
   * Calculate the time difference
   *
   * @return [type] [description]
   */
  public function dateDifference() {
    $request_time = time();
    $this->time_diff = \Drupal::service('date.formatter')->formatTimeDiffSince($this->timestamp, [
      'granularity' => 2,
      'return_as_object' => TRUE,
    ])->toRenderable();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {

    // By default, the block will contain 10 feed items.
    return array(
      'block_count' => 10,
      'feed' => NULL,
    );
  }


  /**
   * Load the types of notifications
   */
  public function types() {
    // set the types as an array
    $this->vocabulary_name = 'tags';
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = $this->content();

    return [
      '#theme' => 'snippets_panels',
      '#content' => $content,
    ];

  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $current_year = date("Y");
    $headline = $this->t('Developer how-to resource @year', [
        '@year' => $current_year
    ]);

    $form['snippets_headline'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Headline'),
        '#description' => $this->t('Write your headline text.'),
        '#default_value' => isset($config['snippets_headline']) ? $config['snippets_headline'] : $headline,
    ];

    $byline = $this->t('Codebales holds an ever growing number of problems with solutions that we have experienced in our day to day code writing. Many with GitHub examples.');

    $form['snippets_byline'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Byline'),
        '#description' => $this->t('Write your byline text.'),
        '#default_value' => isset($config['snippets_byline']) ? $config['snippets_byline'] : $byline,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['snippets_block_settings'] = $form_state->getValue('snippets_block_settings');
    $this->setConfigurationValue('snippets_headline', $form_state->getValue('snippets_headline'));
    $this->setConfigurationValue('snippets_byline', $form_state->getValue('snippets_byline'));

  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state)
  {
    // headline validate a value exists
      if (empty($form_state->getValue('snippets_headline'))) {
          $form_state->setErrorByName('snippets_headline', t('This field is required'));
      }
      if (empty($form_state->getValue('snippets_byline'))) {
          $form_state->setErrorByName('snippets_byline', t('This field is required'));
      }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // return Cache::PERMANENT;
    return 0;
  }

  /**
   * Load the taxonomy array with the term of each vocabulary
   */
  protected function getTaxonomy() {
    // if the vocabulary name does not exist in the taxonomy array,
    // then add it
    $this->SnippetsTaxonomyTerms->vocabulary_name = $this->vocabulary_name;
    $this->SnippetsTaxonomyTerms->taxonomy = $this->taxonomy;
    $this->SnippetsTaxonomyTerms->getTaxonomy();
    $this->taxonomy = $this->SnippetsTaxonomyTerms->taxonomy;
  }

}
