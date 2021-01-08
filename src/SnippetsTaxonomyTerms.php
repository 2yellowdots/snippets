<?php

namespace Drupal\snippets;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\Entity\Term;


/**
 * Defines adding a snippets taxonomy architecture.
 */
class SnippetsTaxonomyTerms {

  use StringTranslationTrait;

  /**
   * Load the taxonomy array with the term of each vocabulary
   *
   */
  public function getTaxonomy() {
    // if the vocabulary name does not exist in the taxonomy array,
    // then add it
    if (!array_key_exists($this->vocabulary_name, $this->taxonomy)) {
      // query the db table taxonomy_term for a specific vocabulary_name
      $this->query_taxonomy();
      // load the termsfor the vocabulary_name
      $this->load_taxonomy_terms();
      // add the terms found to the taxonomy array
      $this->build_vocabulary();
    }
  }

  /**
   * Load the taxonomy array with the term of each vocabulary
   *
   */
  public function getVocabulary() {
    // query the db table taxonomy_term for a specific vocabulary_name
    $this->load_taxonomy_term();
  }

  /**
   * query the db for taxonomy vocabulary
   * @return [type] [description]
   */
  protected function query_taxonomy() {
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', $this->vocabulary_name);
    $this->tids = $query->execute();
  }

  /**
   * load a vocabulary based on the tid value
   *
   */
  protected function load_taxonomy_term() {
    $this->term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($this->tid);
    // $title = $term->name->value;
  }

  /**
   * load a taxonomy terms based on the term.tid
   * @return [type] [description]
   */
  protected function load_taxonomy_terms() {
    $this->terms = \Drupal\taxonomy\Entity\Term::loadMultiple($this->tids);
  }

  /**
   * add the vocabulary to the taxonomy array
   * @return [type] [description]
   */
  protected function build_vocabulary() {
    // get the terms
    foreach ($this->terms as $key => $term) {
      // notification category
      if ($this->vocabulary_name == 'notification_types') {
        $category = $term->field_notification_category->target_id;
        $this->taxonomy[$this->vocabulary_name][$category][$key] = $term->name->value;
      }
      else {
        $this->taxonomy[$this->vocabulary_name][$key] = $term->name->value;
      }
    }
  }

  /**
   * load the taxonomy term and return a single value
   *
   */
  public function load_taxonomy_term_by_tid() {
    // load the taxonomy term by the tid
    $this->term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($this->tid);
    // get the field name
    $field_name = $this->field_name;
    $this->term_value = $this->term->$field_name->value;
  }

  /**
   * Get the taxonomy term object by the name
   * @return [type] [description]
   */
  public function taxonomyTermByName() {
    $this->terms = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->loadByProperties(['name' => $this->term_name]);
    $this->term = reset($this->terms);
  }

  /**
   * if a vocabulary name is not in the taxonomy array, then add it
   * @param  [String] $vocabulary [description]
   * @return [type]             [description]
   */
  public function checkTermExistsInTaxonomyList($vocabulary) {
    if (!in_array($vocabulary, $this->taxonomy)) {
      $this->vocabulary_name = $vocabulary;
      $this->getTaxonomy();
    }
  }

}
