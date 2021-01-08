<?php

namespace Drupal\snippets;

// use Drupal\Component\Utility\Unicode;
// use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
// use Drupal\Core\Entity\EntityManagerInterface;
// use Drupal\Core\Form\FormStateInterface;
// use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
// use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigManagerInterface;
// use Drupal\Core\Template\Attribute;

use Drupal\node\NodeInterface;
use \Drupal\node\Entity\Node;
use Psr\Log\LoggerInterface;
// use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;


/**
 * Defines the queries for the snippets
 */
class SnippetsPathAlias {

  use StringTranslationTrait;

  /**
   * [__construct description]
   */
  public function __construct() {
    $this->field_condition = 'path';
    $this->fields = 'alias';
    $this->path = '';
    $this->table = 'path_alias';
    $this->table_initials = 'pa';
  }

  /**
   * Get the nids array
   *
   */
  public function getTable() {
    return $this->table;
  }

  /**
   * Set the table string
   *
   */
  public function setTable($table) {
    $this->table = $table;
  }

  /**
   * Set the path string
   * @param [Array] $nids [description]
   */
  public function setPath($path) {
    $this->path = $path;
  }

  /**
   * Set the fields array
   *
   */
  public function setFields($fields) {
    $this->fields = $fields;
  }

  /**
   * Get the query value
   *
   */
  public function getQuery() {
    return $this->query;
  }

  /**
   * Set the query value
   * @param [Array] $snippets_nid_arr [description]
   */
  public function setQuery($query) {
    $this->query = $query;
  }

  /**
   * Get the result of the query
   *
   */
  public function getResult() {
    return $this->result;
  }

  /**
   * intiialise the query
   *
   */
  public function initialiseQuery() {
    // set the entity query for node
    $this->database = \Drupal::database();
    // set the entity query for node
    $this->query = $this->database->select($this->table, $this->table_initials);
    $this->query->addField($this->table_initials, $this->fields);
  }

  /**
   * define the elements of the query
   *
   */
  public function conditionQuery() {
    // set the entity query for node
    $this->query->condition($this->table_initials.'.'.$this->field_condition, $this->path);
  }

  /**
   * define the elements of the query
   *
   */
  public function getQueryResult() {
    // set the entity query for node
    $this->result = $this->query->execute();
  }


}
