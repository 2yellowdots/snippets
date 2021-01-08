<?php

namespace Drupal\snippets;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigManagerInterface;

use Drupal\node\NodeInterface;
use \Drupal\node\Entity\Node;
use Psr\Log\LoggerInterface;
use Drupal\taxonomy\Entity\Term;


/**
 * Defines the queries for the snippets
 */
class SnippetsQuery {

  use StringTranslationTrait;

  /**
   * [__construct description]
   */
  public function __construct() {
    $this->storage_type = 'node';
  }

  /**
   * Get the nids array
   *
   * @param array $nids [description]
   */
  public function getNids() {
    return $this->nids;
  }

  /**
   * Set the nids array
   *
   * @param [Array] $nids [description]
   */
  public function setNids($nids) {
    $this->nids = $nids;
  }

  /**
   * Set the nodes list object
   *
   * @param [Int] $uid [description]
   */
  public function setNodesList($nodes_list) {
    $this->nodes_list = $nodes_list;
  }

  /**
   * Set the nodes object
   *
   * @param [Int] $uid [description]
   */
  public function setNodes($nodes) {
    $this->nodes = $nodes;
  }

  /**
   * Get the nodes value
   *
   * @param [Array] $nodes [description]
   */
  public function getNodes() {
    return $this->nodes;
  }

  /**
   * Set the snippets_limit value
   *
   * @param [Bool] $snippets_limit [description]
   */
  public function setSnippetsLimit($snippets_limit) {
    $this->snippets_limit = $snippets_limit;
  }

  /**
   * Set the notification_time_back value
   *
   * @param [Array] $snippets_nid_arr [description]
   */
  public function setSnippetsNidArr($snippets_nid_arr = array()) {
    $this->snippets_nid_arr = $snippets_nid_arr;
  }

  /**
   * Get the query value
   *
   * @param [Array] $nodes [description]
   */
  public function getQuery() {
    return $this->query;
  }

  /**
   * Set the entity_type value
   *
   * @param [Str] $entity_type [description]
   */
  public function setEntityType($entity_type) {
    $this->entity_type = $entity_type;
  }

  /**
   * Set the load_node_boolean value
   *
   * @param [Boolean] $load_node_switch [description]
   */
  public function setLoadNodeSwitch($load_node_switch = TRUE) {
    $this->load_node_switch = $load_node_switch;
  }

  /**
   * Set the storage_type value
   *
   * @param [Str] $storage_type [description]
   */
  public function setStorageType($storage_type) {
    $this->storage_type = $storage_type;
  }

  /**
   * Set the uid value
   *
   * @param [Int] $uid [description]
   */
  public function setUid($uid) {
    $this->uid = $uid;
  }

  /**
   * Set the query value
   *
   * @param [Array] $query [description]
   */
  public function setQuery($query) {
    $this->query = $query;
  }

  /**
   * initialise the query
   *
   */
  public function initialiseQuery() {
    // set the entity query for node
    $this->query = \Drupal::entityQuery($this->storage_type);
  }

  /**
   * Execute the query and define the nodes as $this->nodes
   *
   */
  public function loadNodeEntity() {
    // limit the query to a type
    if (!is_null($this->entity_type)) {
      $this->query->condition('type', $this->entity_type);
    }
    // debug the query
    // $this->query->addTag('debug');
    // execute the query
    $nids = $this->query->execute();

    // load the nodes for this query if there is a result
    if (count($nids) > 0) {
      $this->nids = $nids;
      // update the nodes array
      if ($this->load_node_switch) {
        // create the nodes list from the nids array
        $this->loadMultipleNodesFromNids();
        // clean the nodes and remove unnecessary fields
        $this->buildNodes();
      }
    }
  }

  /**
   * Load nodes.nids
   *
   * @return [type] [description]
   */
  public function loadMultipleNodesFromNids() {
    // $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($this->nids);
    $storage = \Drupal::entityTypeManager()->getStorage($this->storage_type);
    // Load actual user data from database.
    $storage->resetCache();
    // load single array
    // $this->account = $storage->load($value);
    // load multiple array
    $this->nodes_list = $storage->loadMultiple($this->nids);
  }

  /**
   * Alter the nodes array with the nids values
   */
  public function buildNodes() {
    // loop through each result from the query
    foreach ($this->nodes_list as $key => $node) {
      $this->node = (array) $node;
      // make the array keys accessible
      $this->changeKeyByRemovingNoise();
      $this->values = $this->node['values'];
      // clean the array of unnecessary keys
      $this->cleanNodeObject();
      $this->nodes[$key] = $this->values;
    }
  }

  /**
   * Replace the space*space so the array can be accessed
   *
   * @return object cleans up the object so is can accessed
   */
  protected function changeKeyByRemovingNoise() {
    foreach ($this->node as $key => $value) {
      $newkey = preg_replace("/[^a-zA-Z]+/", "", $key);
      $this->node[$newkey] = $value;
    }
  }

  /**
   * Remove keys that are not needed
   *
   * @return object minimised object
   */
  protected function cleanNodeObject() {
    // full array of the fields for the challenges content type
    $this->prepareCoreCustomArrays();

    foreach ($this->values as $key => $value) {
    // initialise the get_value variable
      $this->get_value = array();

      // Loop through a multi dimensional array and simplify it
      $this->sortMultiDimensionalArray($value);

      // rewrite the value array by minimising it
      $this->values[$key] = $this->get_value;
    }
  }

  /**
   *  Prepare the core and custom array values
   *  then unset any unnecessary keys from the arrays
   */
  protected function prepareCoreCustomArrays() {
    // full array of the fields for the challenges content type
    $this->setCoreCustomArrayValues();

    // build full array
    $this->custom_keys = array_merge($this->custom_keys, $this->user_keys);
    // define array keys to be keep (core)
    $keep_keys = array('nid','uid','title','created');
    // merge the keep and add keys
    $keep_keys = array_merge($keep_keys, $this->custom_keys);
    // get the difference
    $unset_keys = array_diff($this->core_keys, $keep_keys);
    // minimise the depth of the array for the following keys
    $minimise_depth = array();
    // $minimise_depth = array_merge($minimise_depth, $add_keys);

    $minimise_depth = array_diff($minimise_depth, $unset_keys);

    // loop through the values array and remove the unnecessary keys
    foreach ($unset_keys as $k => $key) {
      unset($this->values[$key]);
    }
  }

  /**
   * Define the values for the custom and core arrays
   */
  protected function setCoreCustomArrayValues() {
    // full array of the fields for the challenges content type
    $this->core_keys = array('vid','nid','title','uid','type','created','changed','body',
      'langcode','revision_timestamp','revision_uid','revision_log','type','uuid','isDefaultRevision',
      'status','promote','sticky','revision_translation_affected','default_langcode');

    $this->custom_keys = array();

    $this->user_keys = array('mail','name');
  }

  /*
   * Loop through a multi dimensional array
   */
  protected function sortMultiDimensionalArray($haystack) {
    foreach ($haystack as $key => $item) {
      // if $item is an array and the number of children is greater to one
      // keep looping through the array
      if (is_array($item) && count($haystack[$key]) > 1) {
        $this->sortMultiDimensionalArray($haystack[$key]);
      }
      // otherwise if count is equal to one then load it in to the get_value array
      elseif (is_array($item) && count($haystack[$key]) == 1) {
        $k = key($item);
        if (is_array($item[$k])) {
          $k2 = key($item[$k]);
          $this->get_value[] = $item[$k][$k2];
        }
        else {
          $this->get_value[] = $item[$k];
        }
      }
      // otherwise not an array
      elseif (!is_array($item)) {
        $this->get_value[] = $item;
      }
    }
  }


}
