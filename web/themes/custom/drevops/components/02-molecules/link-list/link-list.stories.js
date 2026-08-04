// phpcs:ignoreFile
import Component from './link-list.twig';

const meta = {
  title: 'Molecules/Link list',
  component: Component,
  argTypes: {
    theme: {
      control: { type: 'radio' },
      options: ['light', 'dark'],
    },
    title: {
      control: { type: 'text' },
    },
    links: {
      control: { type: 'object' },
    },
    vertical_spacing: {
      control: { type: 'radio' },
      options: ['top', 'bottom', 'both'],
    },
    modifier_class: {
      control: { type: 'text' },
    },
    attributes: {
      control: { type: 'text' },
    },
  },
};

export default meta;

export const LinkList = {
  parameters: {
    layout: 'fullscreen',
  },
  args: {
    theme: 'light',
    title: 'Open source contributions',
    links: [
      { text: 'Vortex', url: 'https://github.com/drevops/vortex', is_external: true },
      { text: 'Behat Steps', url: 'https://github.com/drevops/behat-steps', is_external: true },
      { text: 'Issue 1234567: Fix the thing', url: 'https://www.drupal.org/project/drupal/issues/1234567', is_external: true },
    ],
    vertical_spacing: 'both',
    modifier_class: '',
    attributes: '',
  },
};
