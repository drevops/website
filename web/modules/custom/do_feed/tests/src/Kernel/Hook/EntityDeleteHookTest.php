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
use Drupal\do_feed\Hook\EntityDeleteHook;

/**
 * Tests for EntityDeleteHook alias cleanup.
 */
#[CoversClass(EntityDeleteHook::class)]
#[Group('do_feed')]
class EntityDeleteHookTest extends KernelTestBase {

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

    ParagraphsType::create([
      'id' => 'civictheme_automated_list',
      'label' => 'Automated List',
    ])->save();

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
   * Tests that alias is removed when paragraph with slug is deleted.
   */
  public function testAliasRemovedOnDelete(): void {
    $paragraph = Paragraph::create([
      'type' => 'civictheme_automated_list',
      'field_c_p_list_feed_slug' => 'blog',
      'field_c_p_list_content_type' => 'civictheme_page',
    ]);
    $paragraph->save();

    $alias_storage = $this->container->get('entity_type.manager')->getStorage('path_alias');
    $aliases = $alias_storage->loadByProperties(['alias' => '/feed/blog']);
    $this->assertCount(1, $aliases);

    $paragraph->delete();

    $alias_storage->resetCache();
    $aliases = $alias_storage->loadByProperties(['alias' => '/feed/blog']);
    $this->assertCount(0, $aliases);
  }

  /**
   * Tests that deleting paragraph without slug causes no error.
   */
  public function testDeleteParagraphWithoutSlugNoError(): void {
    $paragraph = Paragraph::create([
      'type' => 'civictheme_automated_list',
    ]);
    $paragraph->save();

    $alias_storage = $this->container->get('entity_type.manager')->getStorage('path_alias');
    $all_aliases = $alias_storage->loadMultiple();
    $this->assertEmpty($all_aliases);

    // This should not throw any exceptions.
    $paragraph->delete();

    $alias_storage->resetCache();
    $all_aliases = $alias_storage->loadMultiple();
    $this->assertEmpty($all_aliases);
  }

}
