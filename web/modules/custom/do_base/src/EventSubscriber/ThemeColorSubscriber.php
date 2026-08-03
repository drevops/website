<?php

declare(strict_types=1);

namespace Drupal\do_base\EventSubscriber;

use Drupal\civictheme\CivicthemeColorManager;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Keeps the generated colour stylesheet in step with theme settings.
 */
final class ThemeColorSubscriber implements EventSubscriberInterface {

  public function __construct(protected ClassResolverInterface $classResolver, protected ThemeHandlerInterface $themeHandler) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [ConfigEvents::SAVE => 'onSave'];
  }

  /**
   * Purges the stylesheet when the theme's colours change.
   */
  public function onSave(ConfigCrudEvent $event): void {
    if ($event->getConfig()->getName() !== $this->themeHandler->getDefault() . '.settings') {
      return;
    }

    if (!$event->isChanged('colors')) {
      return;
    }

    // Colours are compiled into a stylesheet that is only ever written while
    // it is missing. A save that is not followed by a purge leaves the old
    // palette on disk, and the site keeps serving colours nothing declares.
    $color_manager = $this->classResolver->getInstanceFromDefinition(CivicthemeColorManager::class);

    if (!$color_manager instanceof CivicthemeColorManager) {
      return;
    }

    $color_manager->invalidateCache();
  }

}
