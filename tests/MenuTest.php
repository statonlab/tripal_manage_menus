<?php

namespace Tests;

use StatonLab\TripalTestSuite\TripalTestCase;
use Tests\DatabaseSeeders\MenuSeeder;

class MenuTest extends TripalTestCase {
  /**
   * Checks to see if the menu is built properly upon installation.
   *
   * @dataProvider entityLinkDataProvider
   */
  public function testMenuInstalled($organism_title, $menu_link_title) {
    $this->assertEquals($organism_title, $menu_link_title);
  }

  /**
   * Checks to see if the menu is rebuilt properly after inserting an entity.
   *
   * @dataProvider entityLinkDataProvider
   */
  public function testMenuInsert($organism_title, $menu_link_title) {
    // Check to see if they're equal
    $this->assertEquals($organism_title, $menu_link_title);
  }

  /**
   * Checks to see if the menu is rebuilt properly after updating an entity.
   *
   * @dataProvider entityLinkDataProvider
   */
  public function testMenuUpdate() {

  }

  /**
   * Checks to see if the menu is rebuilt properly after deleting an entity.
   *
   * @dataProvider entityLinkDataProvider
   */
  public function testMenuDelete() {

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
  public function entityLinkDataProvider() {
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
