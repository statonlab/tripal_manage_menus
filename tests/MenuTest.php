<?php

namespace Tests;

use StatonLab\TripalTestSuite\DBTransaction;
use StatonLab\TripalTestSuite\TripalTestCase;

class MenuTest extends TripalTestCase {
 // use DBTransaction;
  /**
   * Checks to see if the menu is built properly upon installation.
   */
  public function testMenuInstalled() {

    $bundle_ids = db_query('
        SELECT cb.bundle_id 
        FROM {chado_bundle} cb 
        WHERE cb.data_table = :bundle',
      [':bundle' => 'organism'])->fetchAll();

    foreach($bundle_ids as $object) {
      $entities = db_query('
        SELECT te.title
        FROM {tripal_entity} te
        WHERE te.term_id IN (:bundle_id)
        ORDER BY te.title ASC',
        [':bundle_id' => $object->bundle_id])->fetchAll();
    }

    $entity_titles = [];
    foreach($entities as $entity)
      $entity_titles[] = $entity->title;

    $mlid = db_query('
        SELECT ml.mlid
        FROM {menu_links} ml
        WHERE ml.menu_name = :menu_name
        AND   ml.link_title = :link_title',
      [':menu_name' => 'main-menu', ':link_title' => 'Trees'])->fetchField();

    $links = db_query('
        SELECT ml.link_title
        FROM {menu_links} ml
        WHERE ml.menu_name = :menu_name
        AND   ml.plid = :mlid
        ORDER BY ml.link_title ASC',
      [':menu_name' => 'main-menu', ':mlid' => $mlid])->fetchAll();

    $link_titles = [];

    foreach($links as $link)
      $link_titles[] = $link->link_title;

    $entity_link_data = [];

    $i = 0;

    foreach($entity_titles as $entity_title)
    {
      $entity_link_data[] = [
        $entity_title,
        $link_titles[$i],
      ];

      $i++;
    }

    $this->assertEquals($entity_titles, $link_titles);
  }

  /**
   * Checks to see if the menu is rebuilt properly after inserting an entity.
   */
  public function testMenuInsert() {
    // Create a new organism
    $organism_id = db_insert('chado.organism')->fields([
      'species' =>'MyTestSpecies',
      'genus' =>'GenusTest'
    ])->execute();

    $bundle_id = db_query('
        SELECT cb.bundle_id 
        FROM {chado_bundle} cb 
        WHERE cb.data_table = :bundle',
      [':bundle' => 'organism'])->fetchField();

    // Create bundle name
    $bundle_name = 'bio_data_' . $bundle_id;

    // Publish records for the bundle
    $value = array(
      'bundle_name' => $bundle_name
    );
    ob_start();
    tripal_chado_publish_records($value);
    ob_end_clean();
    // Get the entity and save it for later tests
    $entity_id = chado_get_record_entity_by_table('organism', $organism_id);

    $bundle_ids = db_query('
        SELECT cb.bundle_id 
        FROM {chado_bundle} cb 
        WHERE cb.data_table = :bundle',
      [':bundle' => 'organism'])->fetchAll();

    foreach($bundle_ids as $object) {
      $entities = db_query('
        SELECT te.title
        FROM {tripal_entity} te
        WHERE te.term_id IN (:bundle_id)
        ORDER BY te.title ASC',
        [':bundle_id' => $object->bundle_id])->fetchAll();
    }

    $entity_titles = [];
    foreach($entities as $entity)
      $entity_titles[] = $entity->title;

    $mlid = db_query('
        SELECT ml.mlid
        FROM {menu_links} ml
        WHERE ml.menu_name = :menu_name
        AND   ml.link_title = :link_title',
      [':menu_name' => 'main-menu', ':link_title' => 'Trees'])->fetchField();

    $links = db_query('
        SELECT ml.link_title
        FROM {menu_links} ml
        WHERE ml.menu_name = :menu_name
        AND   ml.plid = :mlid
        ORDER BY ml.link_title ASC',
      [':menu_name' => 'main-menu', ':mlid' => $mlid])->fetchAll();

    $link_titles = [];

    foreach($links as $link)
      $link_titles[] = $link->link_title;

    $entity_link_data = [];

    $i = 0;

    foreach($entity_titles as $entity_title)
    {
      $entity_link_data[] = [
        $entity_title,
        $link_titles[$i],
      ];

      $i++;
    }

    $this->assertEquals($entity_titles, $link_titles);

    return $entity_id;
  }

  /**
   * Checks to see if the menu is rebuilt properly after updating an entity.
   *
   * @depends testMenuInsert
   */
  public function testMenuUpdate($entity_id) {

    $entity = entity_load_single('TripalEntity', $entity_id);

    $entity_new_title = 'whatever 2018';

    db_update('tripal_entity')
      ->fields(array(
        'title' => $entity_new_title
      ))
      ->condition('id', $entity->id)
      ->execute();
    $entity->title = $entity_new_title;

    tripal_manage_menus_entity_update($entity, 'TripalEntity');

    $mlid = db_query('
        SELECT ml.mlid
        FROM {menu_links} ml
        WHERE ml.menu_name = :menu_name
        AND   ml.link_title = :link_title',
      [':menu_name' => 'main-menu', ':link_title' => 'Trees'])->fetchField();

    $links = db_query('
        SELECT ml.link_title
        FROM {menu_links} ml
        WHERE ml.link_path = :link
        ORDER BY ml.link_title ASC',
      [':link' => "bio_data/$entity_id"])->fetchObject();

    $this->assertEquals($links->link_title, $entity_new_title);
  }

  /**
   * Checks to see if the menu is rebuilt properly after deleting an entity.
   *
   * @depends testMenuInsert
   */
  public function testMenuDelete($entity_id) {

    entity_delete('TripalEntity', intval($entity_id));

    db_query('DELETE FROM chado.organism
        WHERE genus = :genus
        AND species = :species',
      [':genus' => 'GenusTest', ':species' => 'MyTestSpecies']);

    $bundle_ids = db_query('
        SELECT cb.bundle_id 
        FROM {chado_bundle} cb 
        WHERE cb.data_table = :bundle',
      [':bundle' => 'organism'])->fetchAll();

    foreach($bundle_ids as $object) {
      $entities = db_query('
        SELECT te.title
        FROM {tripal_entity} te
        WHERE te.term_id IN (:bundle_id)
        ORDER BY te.title ASC',
        [':bundle_id' => $object->bundle_id])->fetchAll();
    }

    $entity_titles = [];
    foreach($entities as $entity)
      $entity_titles[] = $entity->title;

    $mlid = db_query('
        SELECT ml.mlid
        FROM {menu_links} ml
        WHERE ml.menu_name = :menu_name
        AND   ml.link_title = :link_title',
      [':menu_name' => 'main-menu', ':link_title' => 'Trees'])->fetchField();

    $links = db_query('
        SELECT ml.link_title
        FROM {menu_links} ml
        WHERE ml.menu_name = :menu_name
        AND   ml.plid = :mlid
        ORDER BY ml.link_title ASC',
      [':menu_name' => 'main-menu', ':mlid' => $mlid])->fetchAll();

    $link_titles = [];

    foreach($links as $link)
      $link_titles[] = $link->link_title;

    $entity_link_data = [];

    $i = 0;

    foreach($entity_titles as $entity_title)
    {
      $entity_link_data[] = [
        $entity_title,
        $link_titles[$i],
      ];

      $i++;
    }

    $this->assertEquals($entity_titles, $link_titles);
  }

  /**
   *
   * Provides the data needed to test whether the menu links from the module
   * match with the organisms on the site.
   *
   * @return $entity_link_data
   *  An array of arrays, where each nested array is comprised of two strings:
   *  the first string being the name of an entity and the second string being
   *  the name of its matching link title
   */
  public function tripal_manage_menus_entityLinkDataProvider() {
    $bundle_ids = db_query('
        SELECT cb.bundle_id 
        FROM {chado_bundle} cb 
        WHERE cb.data_table = :bundle',
      [':bundle' => 'organism'])->fetchAll();

    foreach($bundle_ids as $object) {
      $entities = db_query('
        SELECT te.title
        FROM {tripal_entity} te
        WHERE te.term_id IN (:bundle_id)
        ORDER BY te.title ASC',
        [':bundle_id' => $object->bundle_id])->fetchAll();
    }

    $entity_titles = [];
    foreach($entities as $entity)
      $entity_titles[] = $entity->title;

    $mlid = db_query('
        SELECT ml.mlid
        FROM {menu_links} ml
        WHERE ml.menu_name = :menu_name
        AND   ml.link_title = :link_title',
      [':menu_name' => 'main-menu', ':link_title' => 'Trees'])->fetchField();

    $links = db_query('
        SELECT ml.link_title
        FROM {menu_links} ml
        WHERE ml.menu_name = :menu_name
        AND   ml.plid = :mlid
        ORDER BY ml.link_title ASC',
      [':menu_name' => 'main-menu', ':mlid' => $mlid])->fetchAll();

    $link_titles = [];

    foreach($links as $link)
      $link_titles[] = $link->link_title;

    $entity_link_data = [];

    $i = 0;

    foreach($entity_titles as $entity_title)
    {
      $entity_link_data[] = [
        $entity_title,
        $link_titles[$i],
      ];

      $i++;
    }

    return $entity_link_data;
  }
}
