<?php

declare(strict_types=1);

namespace Drupal\Tests\do_analytics\Unit;

use Drupal\Core\Template\Attribute;
use Drupal\do_analytics\AnalyticsAttributes;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the data attributes built for the analytics tracker.
 */
#[CoversClass(AnalyticsAttributes::class)]
#[Group('do_analytics')]
class AnalyticsAttributesTest extends UnitTestCase {

  /**
   * Tests that machine names are converted into component names.
   */
  #[DataProvider('dataProviderName')]
  public function testName(string $machine_name, string $expected): void {
    $this->assertSame($expected, AnalyticsAttributes::name($machine_name));
  }

  /**
   * Data provider for testName.
   */
  public static function dataProviderName(): \Iterator {
    yield 'civictheme bundle' => ['civictheme_promo_card', 'promo_card'];
    yield 'civictheme view mode' => ['civictheme_navigation_card', 'navigation_card'];
    yield 'project bundle' => ['image_list', 'image_list'];
    yield 'prefix only' => ['civictheme_', ''];
    yield 'prefix not at the start' => ['my_civictheme_card', 'my_civictheme_card'];
    yield 'empty' => ['', ''];
  }

  /**
   * Tests that values are reduced to plain text that fits a parameter.
   */
  #[DataProvider('dataProviderText')]
  public function testText(string $value, string $expected): void {
    $this->assertSame($expected, AnalyticsAttributes::text($value));
  }

  /**
   * Data provider for testText.
   */
  public static function dataProviderText(): \Iterator {
    yield 'plain' => ['Senior delivery', 'Senior delivery'];
    yield 'markup' => ['<strong>Senior</strong> <em>delivery</em>', 'Senior delivery'];
    yield 'entities' => ['Build &amp; support', 'Build & support'];
    yield 'whitespace' => ["  Senior\n\t delivery  ", 'Senior delivery'];
    yield 'empty' => ['', ''];
    yield 'too long' => [str_repeat('a', 150), str_repeat('a', 100)];
    yield 'multibyte at the limit' => [str_repeat('ä', 101), str_repeat('ä', 100)];
  }

  /**
   * Tests the attributes that mark an element as a component.
   *
   * @param string $name
   *   The bundle or view mode machine name.
   * @param string|null $id
   *   The entity identifier, or NULL when unsaved.
   * @param string $title
   *   The component title.
   * @param array<string, string> $expected
   *   The expected attributes.
   */
  #[DataProvider('dataProviderComponent')]
  public function testComponent(string $name, ?string $id, string $title, array $expected): void {
    $this->assertSame($expected, AnalyticsAttributes::component($name, $id, $title));
  }

  /**
   * Data provider for testComponent.
   */
  public static function dataProviderComponent(): \Iterator {
    yield 'all values' => [
      'civictheme_promo_card',
      'paragraph:12',
      'Senior delivery',
      [
        'data-analytics-component' => 'promo_card',
        'data-analytics-component-id' => 'paragraph:12',
        'data-analytics-component-title' => 'Senior delivery',
      ],
    ];
    yield 'unsaved' => [
      'civictheme_promo_card',
      NULL,
      'Senior delivery',
      [
        'data-analytics-component' => 'promo_card',
        'data-analytics-component-title' => 'Senior delivery',
      ],
    ];
    yield 'no title' => [
      'civictheme_content',
      'paragraph:5',
      '',
      [
        'data-analytics-component' => 'content',
        'data-analytics-component-id' => 'paragraph:5',
      ],
    ];
    yield 'title of markup only' => [
      'civictheme_content',
      'paragraph:5',
      '<br>',
      [
        'data-analytics-component' => 'content',
        'data-analytics-component-id' => 'paragraph:5',
      ],
    ];
  }

  /**
   * Tests that attributes are added to an array, which stays an array.
   */
  public function testMergeIntoArray(): void {
    $variables = ['attributes' => ['class' => ['card'], 'id' => 'card-1']];

    AnalyticsAttributes::merge($variables, ['data-analytics-component' => 'promo_card']);

    $this->assertSame([
      'data-analytics-component' => 'promo_card',
      'class' => ['card'],
      'id' => 'card-1',
    ], $variables['attributes']);
  }

  /**
   * Tests that attributes are added to an Attribute object in place.
   */
  public function testMergeIntoAttributeObject(): void {
    $attribute = new Attribute(['class' => ['card']]);
    $variables = ['attributes' => $attribute];

    AnalyticsAttributes::merge($variables, ['data-analytics-component' => 'promo_card']);

    $this->assertSame($attribute, $variables['attributes']);
    $this->assertSame('promo_card', $attribute->offsetGet('data-analytics-component')?->value());
    $this->assertTrue($attribute->hasClass('card'));
  }

  /**
   * Tests that attributes are added when the variables carry none.
   *
   * @param array $variables
   *   The template variables.
   */
  #[DataProvider('dataProviderMergeIntoMissingAttributes')]
  public function testMergeIntoMissingAttributes(array $variables): void {
    AnalyticsAttributes::merge($variables, ['data-analytics-block' => 'drevops_content']);

    $this->assertSame(['data-analytics-block' => 'drevops_content'], $variables['attributes']);
  }

  /**
   * Data provider for testMergeIntoMissingAttributes.
   */
  public static function dataProviderMergeIntoMissingAttributes(): \Iterator {
    yield 'absent' => [[]];
    yield 'null' => [['attributes' => NULL]];
    yield 'not an array' => [['attributes' => 'class="card"']];
  }

  /**
   * Tests that merging nothing leaves the variables untouched.
   */
  public function testMergeNothing(): void {
    $variables = ['title' => 'Card'];

    AnalyticsAttributes::merge($variables, []);

    $this->assertSame(['title' => 'Card'], $variables);
  }

}
