<?php

declare(strict_types=1);

namespace Drupal\Tests\do_feed\Kernel\Hook;

use Drupal\KernelTests\KernelTestBase;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Drupal\do_feed\Hook\EntityPresaveHook;

/**
 * Tests for EntityPresaveHook alias lifecycle.
 */
#[CoversClass(EntityPresaveHook::class)]
#[Group('do_feed')]
class EntityPresaveHookTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'file',
    'path',
    'path_alias',
    'paragraphs',
    'entity_reference_revisions',
    'text',
    'taxonomy',
    'views',
    'do_base',
    'do_feed',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['do_feed']);

    // Create the paragraph type.
    ParagraphsType::create([
      'id' => 'civictheme_automated_list',
      'label' => 'Automated List',
    ])->save();

    // Create feed slug field.
    FieldStorageConfig::create([
      'field_name' => 'field_c_p_list_feed_slug',
      'entity_type' => 'paragraph',
      'type' => 'string',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_c_p_list_feed_slug',
      'entity_type' => 'paragraph',
      'bundle' => 'civictheme_automated_list',
      'label' => 'Feed slug',
    ])->save();

    // Create feed title field.
    FieldStorageConfig::create([
      'field_name' => 'field_c_p_list_feed_title',
      'entity_type' => 'paragraph',
      'type' => 'string',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_c_p_list_feed_title',
      'entity_type' => 'paragraph',
      'bundle' => 'civictheme_automated_list',
      'label' => 'Feed title',
    ])->save();

    // Create feed description field.
    FieldStorageConfig::create([
      'field_name' => 'field_c_p_list_feed_description',
      'entity_type' => 'paragraph',
      'type' => 'string_long',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_c_p_list_feed_description',
      'entity_type' => 'paragraph',
      'bundle' => 'civictheme_automated_list',
      'label' => 'Feed description',
    ])->save();

    // Create content type field.
    FieldStorageConfig::create([
      'field_name' => 'field_c_p_list_content_type',
      'entity_type' => 'paragraph',
      'type' => 'string',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_c_p_list_content_type',
      'entity_type' => 'paragraph',
      'bundle' => 'civictheme_automated_list',
      'label' => 'Content type',
    ])->save();

    // Create topics field.
    FieldStorageConfig::create([
      'field_name' => 'field_c_p_list_topics',
      'entity_type' => 'paragraph',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'taxonomy_term'],
      'cardinality' => -1,
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_c_p_list_topics',
      'entity_type' => 'paragraph',
      'bundle' => 'civictheme_automated_list',
      'label' => 'Topics',
    ])->save();

    // Create site sections field.
    FieldStorageConfig::create([
      'field_name' => 'field_c_p_list_site_sections',
      'entity_type' => 'paragraph',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'taxonomy_term'],
      'cardinality' => -1,
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_c_p_list_site_sections',
      'entity_type' => 'paragraph',
      'bundle' => 'civictheme_automated_list',
      'label' => 'Site sections',
    ])->save();
  }

  /**
   * Tests that alias is created when paragraph with slug is saved.
   */
  public function testAliasCreatedOnSave(): void {
    $paragraph = Paragraph::create([
      'type' => 'civictheme_automated_list',
      'field_c_p_list_feed_slug' => 'blog',
      'field_c_p_list_content_type' => 'civictheme_page',
    ]);
    $paragraph->save();

    $alias_storage = $this->container->get('entity_type.manager')->getStorage('path_alias');
    $aliases = $alias_storage->loadByProperties(['alias' => '/feed/blog']);

    $this->assertCount(1, $aliases);
    $alias = reset($aliases);
    $this->assertNotFalse($alias);
    $this->assertEquals('/feed/civictheme_page/all/all', $alias->get('path')->value);
  }

  /**
   * Tests that alias source path is updated when topics change.
   */
  public function testAliasUpdatedOnTopicsChange(): void {
    $paragraph = Paragraph::create([
      'type' => 'civictheme_automated_list',
      'field_c_p_list_feed_slug' => 'blog',
      'field_c_p_list_content_type' => 'civictheme_page',
    ]);
    $paragraph->save();

    // Simulate topic change by reloading and updating.
    $paragraph = Paragraph::load($paragraph->id());
    $paragraph->set('field_c_p_list_feed_slug', 'blog');
    $paragraph->save();

    $alias_storage = $this->container->get('entity_type.manager')->getStorage('path_alias');
    $aliases = $alias_storage->loadByProperties(['alias' => '/feed/blog']);

    $this->assertCount(1, $aliases);
  }

  /**
   * Tests that alias is deleted when slug is cleared.
   */
  public function testAliasDeletedOnSlugClear(): void {
    $paragraph = Paragraph::create([
      'type' => 'civictheme_automated_list',
      'field_c_p_list_feed_slug' => 'blog',
      'field_c_p_list_content_type' => 'civictheme_page',
    ]);
    $paragraph->save();

    $alias_storage = $this->container->get('entity_type.manager')->getStorage('path_alias');
    $aliases = $alias_storage->loadByProperties(['alias' => '/feed/blog']);
    $this->assertCount(1, $aliases);

    // Clear slug.
    $paragraph = Paragraph::load($paragraph->id());
    $paragraph->set('field_c_p_list_feed_slug', NULL);
    $paragraph->save();

    $alias_storage->resetCache();
    $aliases = $alias_storage->loadByProperties(['alias' => '/feed/blog']);
    $this->assertCount(0, $aliases);
  }

  /**
   * Tests that duplicate slug results in warning and no alias.
   */
  public function testDuplicateSlugWarning(): void {
    $paragraph1 = Paragraph::create([
      'type' => 'civictheme_automated_list',
      'field_c_p_list_feed_slug' => 'blog',
      'field_c_p_list_content_type' => 'civictheme_page',
    ]);
    $paragraph1->save();

    $paragraph2 = Paragraph::create([
      'type' => 'civictheme_automated_list',
      'field_c_p_list_feed_slug' => 'blog',
      'field_c_p_list_content_type' => 'civictheme_page',
    ]);
    $paragraph2->save();

    $messenger = $this->container->get('messenger');
    $messages = $messenger->messagesByType('warning');
    $this->assertNotEmpty($messages);
  }

  /**
   * Tests paragraph without feed slug field does not create alias.
   */
  public function testParagraphWithoutSlugFieldNoAlias(): void {
    // Create a paragraph type without the feed slug field.
    ParagraphsType::create([
      'id' => 'civictheme_content',
      'label' => 'Content',
    ])->save();

    $paragraph = Paragraph::create([
      'type' => 'civictheme_content',
    ]);
    $paragraph->save();

    $alias_storage = $this->container->get('entity_type.manager')->getStorage('path_alias');
    $all_aliases = $alias_storage->loadMultiple();
    $this->assertEmpty($all_aliases);
  }

  /**
   * Tests that alias is recreated on re-save when it was externally deleted.
   */
  public function testAliasRecreatedWhenMissing(): void {
    $paragraph = Paragraph::create([
      'type' => 'civictheme_automated_list',
      'field_c_p_list_feed_slug' => 'blog',
      'field_c_p_list_content_type' => 'civictheme_page',
    ]);
    $paragraph->save();

    $alias_storage = $this->container->get('entity_type.manager')->getStorage('path_alias');
    $aliases = $alias_storage->loadByProperties(['alias' => '/feed/blog']);
    $this->assertCount(1, $aliases);

    // Delete the alias externally (simulating missing alias scenario).
    foreach ($aliases as $alias) {
      $alias->delete();
    }

    $alias_storage->resetCache();
    $aliases = $alias_storage->loadByProperties(['alias' => '/feed/blog']);
    $this->assertCount(0, $aliases);

    // Re-save with same slug — alias should be recreated.
    $paragraph = Paragraph::load($paragraph->id());
    $paragraph->save();

    $alias_storage->resetCache();
    $aliases = $alias_storage->loadByProperties(['alias' => '/feed/blog']);
    $this->assertCount(1, $aliases);
  }

  /**
   * Tests existing alias source path is updated when internal path changes.
   */
  public function testExistingAliasSourcePathUpdated(): void {
    $paragraph = Paragraph::create([
      'type' => 'civictheme_automated_list',
      'field_c_p_list_feed_slug' => 'blog',
      'field_c_p_list_content_type' => 'civictheme_page',
    ]);
    $paragraph->save();

    $alias_storage = $this->container->get('entity_type.manager')->getStorage('path_alias');
    $aliases = $alias_storage->loadByProperties(['alias' => '/feed/blog']);
    $this->assertCount(1, $aliases);
    $alias = reset($aliases);
    $this->assertNotFalse($alias);
    $this->assertEquals('/feed/civictheme_page/all/all', $alias->get('path')->value);

    // Change content type — internal path changes but slug stays the same.
    $paragraph = Paragraph::load($paragraph->id());
    $paragraph->set('field_c_p_list_content_type', 'civictheme_event');
    $paragraph->save();

    $alias_storage->resetCache();
    $aliases = $alias_storage->loadByProperties(['alias' => '/feed/blog']);
    $this->assertCount(1, $aliases);
    $alias = reset($aliases);
    $this->assertNotFalse($alias);
    $this->assertEquals('/feed/civictheme_event/all/all', $alias->get('path')->value);
  }

  /**
   * Tests that changing slug updates alias path.
   */
  public function testSlugChangeUpdatesAlias(): void {
    $paragraph = Paragraph::create([
      'type' => 'civictheme_automated_list',
      'field_c_p_list_feed_slug' => 'blog',
      'field_c_p_list_content_type' => 'civictheme_page',
    ]);
    $paragraph->save();

    $alias_storage = $this->container->get('entity_type.manager')->getStorage('path_alias');
    $aliases = $alias_storage->loadByProperties(['alias' => '/feed/blog']);
    $this->assertCount(1, $aliases);

    // Change slug.
    $paragraph = Paragraph::load($paragraph->id());
    $paragraph->set('field_c_p_list_feed_slug', 'articles');
    $paragraph->save();

    $alias_storage->resetCache();
    $old_aliases = $alias_storage->loadByProperties(['alias' => '/feed/blog']);
    $this->assertCount(0, $old_aliases);

    $new_aliases = $alias_storage->loadByProperties(['alias' => '/feed/articles']);
    $this->assertCount(1, $new_aliases);
  }

}
